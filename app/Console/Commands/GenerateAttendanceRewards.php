<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\SpecialAttendance;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAttendanceRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-rewards 
                            {--month= : Periode bulan dalam format YYYY-MM (default: bulan lalu)} 
                            {--force : Paksa generate tanpa cek tanggal dan konfigurasi aktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis kalkulasi dan sinkronisasi potongan & reward kehadiran terbaik ke modul Penggajian (Payroll)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== Cron Auto Generate Potongan & Reward Presensi ===");

        $isRewardEnabled = Setting::get('attendance_reward_enabled', '1') === '1';
        $isAutoGenerateEnabled = Setting::get('attendance_reward_auto_generate_enabled', '1') === '1';
        $autoDate = (int) Setting::get('attendance_reward_auto_date', 1);

        $today = Carbon::today();
        $now = Carbon::now();

        if (!$this->option('force')) {
            if (!$isAutoGenerateEnabled) {
                $this->warn("Auto Generate Presensi & Reward dinonaktifkan di pengaturan sistem.");
                return 0;
            }

            if ($now->day !== $autoDate) {
                $this->info("Hari ini tanggal {$now->day}, jadwal auto generate adalah tanggal {$autoDate}. Melewati proses.");
                return 0;
            }

            $autoHour = (int) Setting::get('attendance_reward_auto_hour', 0);
            if ($now->hour !== $autoHour) {
                $this->info("Saat ini jam {$now->hour}, jadwal auto generate adalah jam {$autoHour}. Melewati proses.");
                return 0;
            }
        }

        // Tentukan periode bulan (default: bulan sebelumnya)
        $month = $this->option('month') ?: $today->copy()->subMonth()->format('Y-m');
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth();
        $todayDate = $today->format('Y-m-d');
        if (!$this->option('force') && $monthEnd->endOfDay()->isFuture()) {
            $this->warn("Periode {$month} belum selesai (bulan berjalan). Reward absen terbaik hanya dievaluasi setelah 1 bulan penuh selesai.");
            return 0;
        }

        $cutoffTime = Setting::get('attendance_checkout_deadline_time', '23:30');

        $this->info("Memproses sinkronisasi data presensi & jadwal untuk periode: {$month} ({$monthStart->format('d M Y')} s/d {$monthEnd->format('d M Y')})");

        // Ambil konfigurasi denda & potongan
        $latePenaltyMode = Setting::get('attendance_late_mode', Setting::get('attendance_late_penalty_mode', 'Per Kejadian'));
        $latePenaltyModeSlug = str_contains(strtolower($latePenaltyMode), 'menit') ? 'per_menit' : 'per_kejadian';
        $penaltyPerMinute = (float) Setting::get('attendance_late_per_minute_amount', 1000);
        $fixedPenalty = (float) Setting::get('attendance_late_amount', 10000);
        $missingOutAmount = (float) Setting::get('attendance_missing_out_amount', 10000);
        $earlyLeaveAmount = (float) Setting::get('attendance_early_leave_amount', 10000);
        $alphaAmount = (float) Setting::get('attendance_alpha_amount', 35000);

        $latePenaltyEnabled = Setting::get('attendance_late_enabled', Setting::get('attendance_late_penalty_enabled', '1')) === '1';
        $missingOutPenaltyEnabled = Setting::get('attendance_missing_out_enabled', Setting::get('attendance_missing_out_penalty_enabled', '1')) === '1';
        $earlyLeavePenaltyEnabled = Setting::get('attendance_early_leave_enabled', Setting::get('attendance_early_leave_penalty_enabled', '1')) === '1';
        $alphaPenaltyEnabled = Setting::get('attendance_alpha_enabled', Setting::get('attendance_alpha_penalty_enabled', '1')) === '1';

        // Ambil data absensi reguler, khusus, dan jadwal kerja
        $attSettings = AttendanceCalculatorService::getAttendanceSettings();
        $shiftsById = Shift::all()->keyBy('id');
        $nowEval = Carbon::now('Asia/Jakarta');

        $allMonthAtts = Attendance::with(['employee.area', 'shift'])
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $allSpecialAtts = SpecialAttendance::with(['employee', 'team'])
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $allWorkSchedules = WorkSchedule::whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->where('status', 'work')
            ->get();

        $employees = Employee::with('area')
            ->excludeTokoAndDemo()
            ->where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })
            ->get();

        // Sinkronisasi seluruh potongan absensi otomatis per tanggal dan per kejadian
        $syncRes = AttendanceCalculatorService::syncMonthlyToPayroll($month);
        $totalDeductionsCount = $syncRes['synced_deductions'] ?? 0;
        $totalRewardsCount = 0;

        DB::transaction(function () use (
            $employees, $allMonthAtts, $allSpecialAtts, $allWorkSchedules,
            $month, $monthStart, $monthEnd,
            $isRewardEnabled, &$totalRewardsCount,
            $attSettings, $shiftsById
        ) {
            $performance = [];

            foreach ($employees as $employee) {
                if (!$employee->email || $employee->isExemptFromAttendanceAndPayroll()) continue;
                $linkedUser = User::where('email', $employee->email)->first();
                if (!$linkedUser || $linkedUser->isExemptFromAttendanceAndPayroll()) continue;

                $empAtts = $allMonthAtts->where('employee_id', $employee->id);
                $empSpecialAtts = $allSpecialAtts->where('employee_id', $employee->id);

                // Seluruh potongan absensi telah disinkronkan per tanggal dan per kejadian oleh AttendanceCalculatorService::syncMonthlyToPayroll

                // Tandai absensi sudah disinkronkan ke payroll
                if ($empAtts->isNotEmpty()) {
                    Attendance::whereIn('id', $empAtts->pluck('id'))->update(['is_synced_to_payroll' => true]);
                }
                if ($empSpecialAtts->isNotEmpty()) {
                    SpecialAttendance::whereIn('id', $empSpecialAtts->pluck('id'))->update(['is_synced_to_payroll' => true]);
                }

                // Hitung performa untuk evaluasi Reward secara adil & cerdas berbasis jam & hari kerja individu
                $empSchedules = $allWorkSchedules->where('employee_id', $employee->id);
                $perf = AttendanceCalculatorService::calculateEmployeeBestAttendance(
                    $employee,
                    $empAtts,
                    $empSchedules,
                    $monthStart,
                    $monthEnd,
                    $monthEnd,
                    $empSpecialAtts,
                    null,
                    $shiftsById,
                    $attSettings
                );

                if ($perf['presents'] > 0 && $perf['score'] > 0) {
                    $performance[] = array_merge($perf, [
                        'user' => $linkedUser,
                        'employee' => $employee,
                        'area_id' => $employee->area_id,
                    ]);
                }
            }

            // Fungsi sort tie-breaker adil & cerdas untuk Absen Terbaik
            $sortFn = function ($a, $b) {
                if ($b['score'] != $a['score']) {
                    return $b['score'] <=> $a['score'];
                }
                if ($b['presents'] != $a['presents']) {
                    return $b['presents'] <=> $a['presents'];
                }
                if ($a['late_minutes'] != $b['late_minutes']) {
                    return $a['late_minutes'] <=> $b['late_minutes'];
                }
                if ($a['lates'] != $b['lates']) {
                    return $a['lates'] <=> $b['lates'];
                }
                if ($a['early_leaves'] != $b['early_leaves']) {
                    return $a['early_leaves'] <=> $b['early_leaves'];
                }
                return ($b['avg_early_minutes'] ?? 0) <=> ($a['avg_early_minutes'] ?? 0);
            };

            // --- 6. PROSES REWARD ABSEN TERBAIK OTOMATIS ---
            if ($isRewardEnabled && !empty($performance)) {
                // A. Reward Per Cabang (Top 3 per Cabang)
                if (Setting::get('attendance_reward_branch_enabled', '1') === '1') {
                    $byArea = collect($performance)->groupBy('area_id');
                    $branchNominals = [
                        1 => (float) Setting::get('attendance_reward_branch_1', 200000),
                        2 => (float) Setting::get('attendance_reward_branch_2', 150000),
                        3 => (float) Setting::get('attendance_reward_branch_3', 100000),
                    ];

                    foreach ($byArea as $areaId => $areaPerformers) {
                        if (!$areaId) continue;
                        $sorted = $areaPerformers->sort($sortFn)->values();
                        for ($rank = 1; $rank <= min(3, $sorted->count()); $rank++) {
                            $item = $sorted[$rank - 1];
                            $nominal = $branchNominals[$rank] ?? 0;
                            if ($nominal > 0) {
                                Incentive::updateOrCreate(
                                    [
                                        'user_id' => $item['user']->id,
                                        'reference_id' => "REWARD_BRANCH_{$areaId}_{$rank}_{$month}",
                                    ],
                                    [
                                        'type' => 'auto',
                                        'description' => "Reward Absen Terbaik (Juara {$rank} Cabang) Periode {$month}",
                                        'amount' => $nominal,
                                        'incentive_date' => $monthEnd->format('Y-m-d'),
                                        'status' => 'pending',
                                    ]
                                );
                                $totalRewardsCount++;
                            }
                        }
                    }
                }

                // B. Reward Global Seluruh Perusahaan (Top 3)
                if (Setting::get('attendance_reward_global_enabled', '1') === '1') {
                    $globalSorted = collect($performance)->sort($sortFn)->values();
                    $globalNominals = [
                        1 => (float) Setting::get('attendance_reward_global_1', 200000),
                        2 => (float) Setting::get('attendance_reward_global_2', 150000),
                        3 => (float) Setting::get('attendance_reward_global_3', 100000),
                    ];

                    for ($rank = 1; $rank <= min(3, $globalSorted->count()); $rank++) {
                        $item = $globalSorted[$rank - 1];
                        $nominal = $globalNominals[$rank] ?? 0;
                        if ($nominal > 0) {
                            Incentive::updateOrCreate(
                                [
                                    'user_id' => $item['user']->id,
                                    'reference_id' => "REWARD_GLOBAL_{$rank}_{$month}",
                                ],
                                [
                                    'type' => 'auto',
                                    'description' => "Reward Absen Terbaik (Juara {$rank} Global) Periode {$month}",
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalRewardsCount++;
                        }
                    }
                }

                // C. Reward Divisi Maintenance (Top 3 khusus jabatan Maintenance)
                if (Setting::get('attendance_reward_maintenance_enabled', '1') === '1') {
                    $maintNominals = [
                        1 => (float) Setting::get('attendance_reward_maint_1', 200000),
                        2 => (float) Setting::get('attendance_reward_maint_2', 150000),
                        3 => (float) Setting::get('attendance_reward_maint_3', 100000),
                    ];

                    $maintSorted = collect($performance)->filter(function ($item) {
                        $emp = $item['employee'];
                        $pos = strtolower($emp->position ?? '');
                        $type = strtolower($emp->employee_type ?? '');
                        $isSpecial = str_contains($pos, 'khusus') || str_contains($pos, 'special') || str_contains($pos, 'serbu') || str_contains($type, 'khusus');
                        if ($isSpecial) return false;
                        return str_contains($pos, 'maint');
                    })->sort($sortFn)->values();

                    for ($rank = 1; $rank <= min(3, $maintSorted->count()); $rank++) {
                        $item = $maintSorted[$rank - 1];
                        $nominal = $maintNominals[$rank] ?? 0;
                        if ($nominal > 0) {
                            Incentive::updateOrCreate(
                                [
                                    'user_id' => $item['user']->id,
                                    'reference_id' => "REWARD_MAINT_{$rank}_{$month}",
                                ],
                                [
                                    'type' => 'auto',
                                    'description' => "Reward Absen Terbaik (Juara {$rank} Maintenance) Periode {$month}",
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalRewardsCount++;
                        }
                    }
                }

                // D. Reward Tim Khusus (Top 3 Global Gabungan Seluruh Tim Khusus)
                if (Setting::get('attendance_reward_special_team_enabled', '1') === '1') {
                    $specialNominals = [
                        1 => (float) Setting::get('attendance_reward_special_1', 200000),
                        2 => (float) Setting::get('attendance_reward_special_2', 150000),
                        3 => (float) Setting::get('attendance_reward_special_3', 100000),
                    ];

                    // Bersihkan insentif format per-tim terdahulu agar tidak dobel
                    Incentive::where('reference_id', 'like', "REWARD_SPECIAL_%_{$month}")
                        ->where('reference_id', 'not like', "REWARD_SPECIAL_GLOBAL_%")
                        ->delete();

                    // Urutkan seluruh anggota tim khusus (global gabungan seluruh tim: Tim Khusus, Tim Serbu, Tim BC-4)
                    $specialSorted = collect($performance)->filter(function ($item) {
                        $emp = $item['employee'];
                        $pos = strtolower($emp->position ?? '');
                        $type = strtolower($emp->employee_type ?? '');
                        $isSpecial = preg_match('/(khusus|special|serbu|bc-?4)/i', $pos . ' ' . $type);
                        if (!$isSpecial && method_exists($emp, 'specialTeamMembers')) {
                            $isSpecial = $emp->specialTeamMembers()->exists();
                        }
                        return (bool) $isSpecial;
                    })->sortByDesc('score')->values();

                    for ($rank = 1; $rank <= min(3, $specialSorted->count()); $rank++) {
                        $item = $specialSorted[$rank - 1];
                        $nominal = $specialNominals[$rank] ?? 0;
                        if ($nominal > 0) {
                            $teamLabel = $item['employee']->position ?: 'Tim Khusus';
                            Incentive::updateOrCreate(
                                [
                                    'user_id' => $item['user']->id,
                                    'reference_id' => "REWARD_SPECIAL_GLOBAL_{$rank}_{$month}",
                                ],
                                [
                                    'type' => 'auto',
                                    'description' => "Reward Absen Terbaik Tim Khusus (Juara {$rank} Global - {$item['employee']->name}) Periode {$month}",
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalRewardsCount++;
                        }
                    }
                }
            }
        });

        $this->info("Berhasil menyinkronkan {$totalDeductionsCount} potongan otomatis & {$totalRewardsCount} reward insentif ke modul Penggajian untuk periode {$month}.");
        return 0;
    }
}
