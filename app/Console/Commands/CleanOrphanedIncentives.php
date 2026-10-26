<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Incentive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOrphanedIncentives extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:clean-orphaned-incentives {--force : Jalankan pembersihan langsung tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pembersihan insentif otomatis yang pelanggannya sudah tidak ada di data pelanggan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pemindaian insentif otomatis tanpa pelanggan...');

        $custCodes = Customer::pluck('customer_code')->filter()->toArray();
        $custIds = Customer::pluck('id')->toArray();
        $custNames = Customer::pluck('name')->map(fn($n) => strtolower(trim($n)))->toArray();

        $this->info('Total Pelanggan Terdaftar Saat Ini: ' . count($custIds));

        $pendingAuto = Incentive::where('status', 'pending')
            ->where('type', '!=', 'manual')
            ->get();

        $toDeleteIds = [];
        $totalNominal = 0;

        foreach ($pendingAuto as $inc) {
            $matched = false;

            // 1. Cek reference_id (contoh: booking_cust_1177, survey_cust_1177, dll)
            if (!empty($inc->reference_id)) {
                if (preg_match('/cust_(\d+)/i', $inc->reference_id, $m)) {
                    if (in_array((int)$m[1], $custIds)) {
                        $matched = true;
                    }
                }
                if (!$matched) {
                    foreach ($custCodes as $code) {
                        if (str_contains($inc->reference_id, $code)) {
                            $matched = true;
                            break;
                        }
                    }
                }
            }

            // 2. Cek description untuk kode pelanggan (CUS-xxxx)
            if (!$matched && preg_match('/\((CUS-[^)]+)\)/i', $inc->description, $m)) {
                if (in_array(strtoupper($m[1]), array_map('strtoupper', $custCodes))) {
                    $matched = true;
                }
            }

            // 3. Cek nama pelanggan pada keterangan insentif
            if (!$matched) {
                $cleanName = trim(preg_replace('/^(Fee (Booking|Survey|Pasang|Aktivasi) Pelanggan:?\s*)/i', '', $inc->description));
                $cleanName = strtolower(trim(preg_replace('/\s*\(CUS-[^)]+\)/', '', $cleanName)));
                if (!empty($cleanName) && in_array($cleanName, $custNames)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $toDeleteIds[] = $inc->id;
                $totalNominal += (float) $inc->amount;
            }
        }

        // 2. Pemindaian Potongan Otomatis (Pinalti/Clawback) tanpa Pelanggan
        $pendingDeductions = \App\Models\Deduction::where('status', 'pending')
            ->where('type', '!=', 'manual')
            ->get();

        $toDeleteDedIds = [];
        $totalNominalDed = 0;

        foreach ($pendingDeductions as $ded) {
            $matched = false;

            if (!empty($ded->reference_id)) {
                if (preg_match('/cust_(\d+)/i', $ded->reference_id, $m)) {
                    if (in_array((int)$m[1], $custIds)) {
                        $matched = true;
                    }
                }
                if (!$matched) {
                    foreach ($custCodes as $code) {
                        if (str_contains($ded->reference_id, $code)) {
                            $matched = true;
                            break;
                        }
                    }
                }
            }

            if (!$matched && preg_match('/\((CUS-[^)]+)\)/i', $ded->description, $m)) {
                if (in_array(strtoupper($m[1]), array_map('strtoupper', $custCodes))) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $cleanName = trim(preg_replace('/^(Pinalti Stop Dini[^:]*:\s*(Pemotongan Fee (Booking|Survey|Pasang|Aktivasi)\s*)?)/i', '', $ded->description));
                $cleanName = preg_replace('/\s*\(CUS-[^)]+\).*/i', '', $cleanName);
                $cleanName = strtolower(trim($cleanName));
                if (!empty($cleanName) && in_array($cleanName, $custNames)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $toDeleteDedIds[] = $ded->id;
                $totalNominalDed += (float) $ded->amount;
            }
        }

        $count = count($toDeleteIds);
        $countDed = count($toDeleteDedIds);

        if ($count === 0 && $countDed === 0) {
            $this->info('✓ Semua data insentif dan potongan pending valid. Tidak ada data yatim (orphaned).');
            return 0;
        }

        $this->warn("Ditemukan {$count} data insentif otomatis (Rp " . number_format($totalNominal, 0, ',', '.') . ") dan {$countDed} data potongan pinalti (Rp " . number_format($totalNominalDed, 0, ',', '.') . ") tanpa data pelanggan.");

        if (!$this->option('force') && !$this->confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            $this->info('Operasi dibatalkan oleh pengguna.');
            return 0;
        }

        DB::beginTransaction();
        try {
            foreach (array_chunk($toDeleteIds, 500) as $chunk) {
                Incentive::whereIn('id', $chunk)->delete();
            }

            foreach (array_chunk($toDeleteDedIds, 500) as $chunk) {
                \App\Models\Deduction::whereIn('id', $chunk)->delete();
            }

            DB::commit();
            $this->info("✓ Sukses menghapus {$count} insentif (Rp " . number_format($totalNominal, 0, ',', '.') . ") dan {$countDed} potongan pinalti (Rp " . number_format($totalNominalDed, 0, ',', '.') . ") tanpa data pelanggan.");
            Log::info("[CleanOrphanedIncentives] Berhasil menghapus {$count} insentif dan {$countDed} potongan tanpa pelanggan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal menghapus data: ' . $e->getMessage());
            Log::error('[CleanOrphanedIncentives] Gagal menghapus insentif/potongan: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
