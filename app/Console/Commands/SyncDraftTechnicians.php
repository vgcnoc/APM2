<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\User;
use App\Models\Employee;
use App\Models\TechnicianSchedule;
use App\Services\DataMigrationService;
use Illuminate\Support\Facades\DB;

class SyncDraftTechnicians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migration:sync-draft-technicians {file? : Path atau nama file CSV/Excel draft migrasi} {--type=installation : Tipe jadwal teknisi (installation/survey/all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan dan munculkan seluruh petugas instalasi/survey (termasuk teknisi kedua/helper) dari file draft migrasi ke pelanggan yang sudah terdaftar di sistem';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $scheduleType = $this->option('type') ?: 'installation';

        if (!$filePath) {
            $defaultFiles = [
                base_path('draft_migrasi.csv'),
                base_path('migrasi_customers.csv'),
                base_path('customers.csv'),
                public_path('templates/template_migrasi_customers.csv'),
                public_path('templates/template_migrasi_pelanggan_all_in_one.csv'),
            ];

            foreach ($defaultFiles as $candidate) {
                if (file_exists($candidate)) {
                    $filePath = $candidate;
                    break;
                }
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            $this->error("File draft migrasi tidak ditemukan.");
            $this->line("Silakan sebutkan path file, contoh: php artisan migration:sync-draft-technicians \"C:\\path\\ke\\draft_pelanggan.csv\"");
            return Command::FAILURE;
        }

        $this->info("Membaca file draft: {$filePath}");
        $content = file_get_contents($filePath);
        $migrationService = app(DataMigrationService::class);
        $rows = $migrationService->parseCsv($content);

        if (empty($rows)) {
            $this->error("File draft kosong atau format baris tidak terbaca.");
            return Command::FAILURE;
        }

        $this->info("Memuat data referensi teknisi & karyawan...");
        $usersCache = User::pluck('id', 'email')->mapWithKeys(fn($id, $email) => [strtolower($email) => $id])->toArray();
        $userNamesCache = User::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id])->toArray();
        $userPhonesCache = [];
        foreach (User::select('id', 'phone')->whereNotNull('phone')->get() as $u) {
            $digits = preg_replace('/[^0-9]/', '', $u->phone);
            if ($digits) $userPhonesCache[$digits] = $u->id;
        }
        $affiliateCodesCache = [];
        $employeesIakCache = Employee::whereNotNull('iak_number')->pluck('id', 'iak_number')->mapWithKeys(fn($id, $iak) => [strtolower($iak) => $id])->toArray();
        $allUsersList = User::all();
        $allEmployeesList = Employee::all();

        $this->info("Menyinkronkan data petugas teknisi ke " . count($rows) . " baris draft pelanggan...\n");

        $updatedCount = 0;
        $multiTechCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $customerCode = trim((string)($row['kode_pelanggan'] ?? $row['customer_code'] ?? $row['id_pelanggan'] ?? ''));
                $customerName = trim((string)($row['nama_lengkap'] ?? $row['nama'] ?? $row['name'] ?? ''));
                $customerPhone = trim((string)($row['nomor_wa'] ?? $row['telepon'] ?? $row['phone'] ?? ''));

                $customer = null;
                if (!empty($customerCode)) {
                    $customer = Customer::where('customer_code', $customerCode)->first();
                }
                if (!$customer && !empty($customerPhone)) {
                    $cleanP = preg_replace('/[^0-9]/', '', $customerPhone);
                    if (strlen($cleanP) >= 8) {
                        $customer = Customer::where('phone', 'like', "%{$cleanP}%")->first();
                    }
                }
                if (!$customer && !empty($customerName)) {
                    $customer = Customer::where('name', $customerName)->first();
                }

                if (!$customer) {
                    $skippedCount++;
                    continue;
                }

                $hasChanges = false;

                // 1. INSTALASI TECHNICIANS
                if (in_array($scheduleType, ['installation', 'all'])) {
                    $installTechParts = [];
                    if (!empty($row['teknisi_pasang'])) $installTechParts[] = $row['teknisi_pasang'];
                    elseif (!empty($row['teknisi_instalasi'])) $installTechParts[] = $row['teknisi_instalasi'];
                    elseif (!empty($row['teknisi'])) $installTechParts[] = $row['teknisi'];
                    elseif (!empty($row['teknisi_1'])) $installTechParts[] = $row['teknisi_1'];
                    elseif (!empty($row['teknisi_pasang_1'])) $installTechParts[] = $row['teknisi_pasang_1'];

                    if (!empty($row['teknisi_pasang_2'])) $installTechParts[] = $row['teknisi_pasang_2'];
                    if (!empty($row['teknisi_2'])) $installTechParts[] = $row['teknisi_2'];
                    if (!empty($row['helper'])) $installTechParts[] = $row['helper'];
                    if (!empty($row['helper_pasang'])) $installTechParts[] = $row['helper_pasang'];
                    if (!empty($row['teknisi_pendamping'])) $installTechParts[] = $row['teknisi_pendamping'];
                    if (!empty($row['asisten'])) $installTechParts[] = $row['asisten'];

                    $rawInstallTech = trim(implode(', ', array_filter(array_map('trim', $installTechParts))));
                    $installMatchedNames = [];
                    $installTechIds = DataMigrationService::resolveMultipleTechnicians(
                        $rawInstallTech, $usersCache, $userNamesCache, $userPhonesCache,
                        $affiliateCodesCache, $employeesIakCache, $allUsersList, $allEmployeesList, $installMatchedNames
                    );

                    if (!empty($installTechIds)) {
                        $actDate = $customer->activation_date ? $customer->activation_date->format('Y-m-d') : ($customer->created_at ? $customer->created_at->format('Y-m-d') : date('Y-m-d'));
                        $installDate = !empty($row['tgl_jadwal_pasang']) ? DataMigrationService::parseFlexibleDate($row['tgl_jadwal_pasang'], $actDate) : $actDate;
                        $installTime = !empty($row['jam_jadwal_pasang']) ? substr(trim($row['jam_jadwal_pasang']), 0, 5) : '10:00';
                        $installStatus = !empty($row['status_jadwal_pasang']) ? strtolower(trim($row['status_jadwal_pasang'])) : (in_array($customer->status, ['active', 'terminated']) ? 'done' : 'scheduled');
                        $notes = !empty($row['catatan_jadwal_pasang']) ? $row['catatan_jadwal_pasang'] : 'Jadwal pasang migrasi sistem';

                        // Delete outdated technician schedules not in current draft
                        TechnicianSchedule::where('customer_id', $customer->id)
                            ->where('type', 'installation')
                            ->whereNotIn('technician_id', $installTechIds)
                            ->delete();

                        $existingInstall = TechnicianSchedule::where('customer_id', $customer->id)->where('type', 'installation')->get();

                        foreach ($installTechIds as $tId) {
                            $existingRow = $existingInstall->firstWhere('technician_id', $tId);
                            $payload = [
                                'customer_id' => $customer->id,
                                'technician_id' => $tId,
                                'scheduled_date' => $installDate,
                                'scheduled_time' => $installTime,
                                'type' => 'installation',
                                'status' => $installStatus,
                                'notes' => $notes,
                            ];
                            if ($existingRow) {
                                $existingRow->update($payload);
                            } else {
                                TechnicianSchedule::create($payload);
                            }
                        }

                        $hasChanges = true;
                        if (count($installTechIds) > 1) {
                            $multiTechCount++;
                            $this->line("✔ [{$customer->customer_code}] {$customer->name} -> " . count($installTechIds) . " Petugas Pasang: " . implode(', ', $installMatchedNames));
                        }
                    }
                }

                // 2. SURVEY TECHNICIANS
                if (in_array($scheduleType, ['survey', 'all'])) {
                    $surveyTechParts = [];
                    if (!empty($row['teknisi_survey'])) $surveyTechParts[] = $row['teknisi_survey'];
                    elseif (!empty($row['surveyor'])) $surveyTechParts[] = $row['surveyor'];
                    elseif (!empty($row['petugas_survey'])) $surveyTechParts[] = $row['petugas_survey'];
                    elseif (!empty($row['teknisi_survey_1'])) $surveyTechParts[] = $row['teknisi_survey_1'];
                    elseif (!empty($row['surveyor_1'])) $surveyTechParts[] = $row['surveyor_1'];

                    if (!empty($row['teknisi_survey_2'])) $surveyTechParts[] = $row['teknisi_survey_2'];
                    if (!empty($row['surveyor_2'])) $surveyTechParts[] = $row['surveyor_2'];
                    if (!empty($row['helper_survey'])) $surveyTechParts[] = $row['helper_survey'];
                    if (!empty($row['petugas_survey_2'])) $surveyTechParts[] = $row['petugas_survey_2'];

                    $rawSurveyTech = trim(implode(', ', array_filter(array_map('trim', $surveyTechParts))));
                    $surveyMatchedNames = [];
                    $surveyTechIds = DataMigrationService::resolveMultipleTechnicians(
                        $rawSurveyTech, $usersCache, $userNamesCache, $userPhonesCache,
                        $affiliateCodesCache, $employeesIakCache, $allUsersList, $allEmployeesList, $surveyMatchedNames
                    );

                    if (!empty($surveyTechIds)) {
                        $regDate = $customer->registration_date ? $customer->registration_date->format('Y-m-d') : ($customer->created_at ? $customer->created_at->format('Y-m-d') : date('Y-m-d'));
                        $surveyDate = !empty($row['tgl_jadwal_survey']) ? DataMigrationService::parseFlexibleDate($row['tgl_jadwal_survey'], $regDate) : $regDate;
                        $surveyTime = !empty($row['jam_jadwal_survey']) ? substr(trim($row['jam_jadwal_survey']), 0, 5) : '09:00';
                        $surveyStatus = !empty($row['status_jadwal_survey']) ? strtolower(trim($row['status_jadwal_survey'])) : (in_array($customer->status, ['survey', 'installing', 'active']) ? 'done' : 'scheduled');
                        $notes = !empty($row['catatan_jadwal_survey']) ? $row['catatan_jadwal_survey'] : 'Jadwal survey migrasi sistem';

                        TechnicianSchedule::where('customer_id', $customer->id)
                            ->where('type', 'survey')
                            ->whereNotIn('technician_id', $surveyTechIds)
                            ->delete();

                        $existingSurvey = TechnicianSchedule::where('customer_id', $customer->id)->where('type', 'survey')->get();

                        foreach ($surveyTechIds as $tId) {
                            $existingRow = $existingSurvey->firstWhere('technician_id', $tId);
                            $payload = [
                                'customer_id' => $customer->id,
                                'technician_id' => $tId,
                                'scheduled_date' => $surveyDate,
                                'scheduled_time' => $surveyTime,
                                'type' => 'survey',
                                'status' => $surveyStatus,
                                'notes' => $notes,
                            ];
                            if ($existingRow) {
                                $existingRow->update($payload);
                            } else {
                                TechnicianSchedule::create($payload);
                            }
                        }

                        $hasChanges = true;
                    }
                }

                if ($hasChanges) {
                    $updatedCount++;
                }
            }

            DB::commit();
            $this->info("\n=== SINKRONISASI SELESAI ===");
            $this->info("Total Pelanggan Diperbarui: {$updatedCount} pelanggan");
            $this->info("Pelanggan dengan Multi-Petugas (2+ teknisi): {$multiTechCount} pelanggan");
            if ($skippedCount > 0) {
                $this->warn("Baris tidak ditemukan di database: {$skippedCount} baris");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan saat sinkronisasi: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

