<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\SpecialAttendance;
use App\Models\User;
use App\Models\WorkSchedule;
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
        $allMonthAtts = Attendance::with('employee.area')
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $allSpecialAtts = SpecialAttendance::with(['employee', 'team'])
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $allWorkSchedules = WorkSchedule::whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->where('status', 'work')
            ->get();

        $employees = Employee::with('area')
            ->where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })
            ->get();

        $totalDeductionsCount = 0;
        $totalRewardsCount = 0;

        DB::transaction(function () use (
            $employees, $allMonthAtts, $allSpecialAtts, $allWorkSchedules,
            $month, $monthStart, $monthEnd, $todayDate, $cutoffTime,
            $latePenaltyMode, $penaltyPerMinute, $fixedPenalty, $missingOutAmount, $earlyLeaveAmount, $alphaAmount,
            $latePenaltyEnabled, $missingOutPenaltyEnabled, $earlyLeavePenaltyEnabled, $alphaPenaltyEnabled,
            $isRewardEnabled, &$totalDeductionsCount, &$totalRewardsCount
        ) {
            $performance = [];

            foreach ($employees as $employee) {
                if (!$employee->email) continue;
                $linkedUser = User::where('email', $employee->email)->first();
                if (!$linkedUser) continue;

                $empAtts = $allMonthAtts->where('employee_id', $employee->id);
                $empSpecialAtts = $allSpecialAtts->where('employee_id', $employee->id);

                // --- 1. POTONGAN KETERLAMBATAN REGULER ---
                $lateCount = 0;
                $totalLateMinutes = 0;
                if ($latePenaltyEnabled) {
                    $lateRegularAtts = $empAtts->where('status', 'late')->where('late_minutes', '>', 0);
                    $lateCount = $lateRegularAtts->count();
                    $totalLateMinutes = (int) $lateRegularAtts->sum('late_minutes');

                    $lateDeductionAmount = ($latePenaltyModeSlug === 'per_menit')
                        ? ($totalLateMinutes * $penaltyPerMinute)
                        : ($lateCount * $fixedPenalty);

                    if ($lateDeductionAmount > 0) {
                        $desc = "Potongan Terlambat Absensi Periode {$month} ({$lateCount}x telat, total {$totalLateMinutes} menit)";
                        Deduction::updateOrCreate(
                            [
                                'user_id' => $linkedUser->id,
                                'reference_id' => "ATT_LATE_{$employee->id}_{$month}",
                            ],
                            [
                                'type' => 'auto',
                                'description' => $desc,
                                'amount' => $lateDeductionAmount,
                                'deduction_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalDeductionsCount++;
                    }
                }

                // --- 2. POTONGAN LUPA ABSEN PULANG (MISSING CLOCK-OUT) ---
                $missingOutCount = 0;
                if ($missingOutPenaltyEnabled) {
                    $missingOutAtts = $empAtts->filter(function ($att) use ($todayDate, $cutoffTime) {
                        $attDateStr = is_string($att->date) ? substr($att->date, 0, 10) : $att->date->format('Y-m-d');
                        if (empty($att->clock_in) || !empty($att->clock_out)) return false;
                        if ($attDateStr < $todayDate) return true;
                        if ($attDateStr === $todayDate && Carbon::now()->greaterThan(Carbon::parse($attDateStr . ' ' . $cutoffTime))) return true;
                        return false;
                    });
                    $missingOutCount = $missingOutAtts->count();
                    $missingOutDeduction = $missingOutCount * $missingOutAmount;

                    if ($missingOutDeduction > 0) {
                        $desc = "Potongan Lupa Absen Pulang Periode {$month} ({$missingOutCount}x tanpa clock-out)";
                        Deduction::updateOrCreate(
                            [
                                'user_id' => $linkedUser->id,
                                'reference_id' => "ATT_NO_OUT_{$employee->id}_{$month}",
                            ],
                            [
                                'type' => 'auto',
                                'description' => $desc,
                                'amount' => $missingOutDeduction,
                                'deduction_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalDeductionsCount++;
                    }
                }

                // --- 3. POTONGAN PULANG CEPAT (EARLY LEAVE) ---
                $earlyCount = 0;
                if ($earlyLeavePenaltyEnabled) {
                    $earlyAtts = $empAtts->filter(function ($att) {
                        $earlyMins = (int) ($att->early_leave_minutes ?? 0);
                        if ($earlyMins > 0) return true;
                        return str_contains(strtolower($att->notes ?? ''), 'pulang lebih awal');
                    });
                    $earlyCount = $earlyAtts->count();
                    $earlyDeduction = $earlyCount * $earlyLeaveAmount;

                    if ($earlyDeduction > 0) {
                        $desc = "Potongan Pulang Cepat Periode {$month} ({$earlyCount}x pulang sebelum waktu)";
                        Deduction::updateOrCreate(
                            [
                                'user_id' => $linkedUser->id,
                                'reference_id' => "ATT_EARLY_{$employee->id}_{$month}",
                            ],
                            [
                                'type' => 'auto',
                                'description' => $desc,
                                'amount' => $earlyDeduction,
                                'deduction_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalDeductionsCount++;
                    }
                }

                // --- 4. POTONGAN ALPHA / MANGKIR JADWAL KERJA ---
                $alphaCount = 0;
                if ($alphaPenaltyEnabled) {
                    $scheduledDates = $allWorkSchedules->where('employee_id', $employee->id)
                        ->pluck('date')
                        ->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
                        ->unique()
                        ->filter(fn($d) => $d <= $todayDate);

                    $regularAttendedDates = $empAtts->whereNotNull('clock_in')
                        ->pluck('date')
                        ->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
                        ->unique();

                    $specialAttendedDates = $empSpecialAtts->whereNotNull('clock_in')
                        ->pluck('date')
                        ->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
                        ->unique();

                    $alphaDates = $scheduledDates->diff($regularAttendedDates)->diff($specialAttendedDates);
                    $alphaCount = $alphaDates->count();
                    $alphaDeduction = $alphaCount * $alphaAmount;

                    if ($alphaDeduction > 0) {
                        $desc = "Potongan Alpha / Mangkir Jadwal Kerja Periode {$month} ({$alphaCount} hari tidak hadir)";
                        Deduction::updateOrCreate(
                            [
                                'user_id' => $linkedUser->id,
                                'reference_id' => "ATT_ALPHA_{$employee->id}_{$month}",
                            ],
                            [
                                'type' => 'auto',
                                'description' => $desc,
                                'amount' => $alphaDeduction,
                                'deduction_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalDeductionsCount++;
                    }
                }

                // --- 5. POTONGAN TIM KHUSUS (JIKA ANGGOTA TIM KHUSUS) ---
                if ($empSpecialAtts->isNotEmpty()) {
                    $byTeam = $empSpecialAtts->groupBy('special_team_id');
                    foreach ($byTeam as $teamId => $teamAtts) {
                        $team = $teamAtts->first()->team;
                        $sLates = $teamAtts->where('status', 'late')->count();
                        $sNoOuts = $teamAtts->whereNotNull('clock_in')->whereNull('clock_out')->count();
                        $sLateFine = $sLates * (float) ($team?->late_deduction_amount ?? 10000);
                        $sNoOutFine = $sNoOuts * (float) ($team?->no_out_deduction_amount ?? 10000);
                        $totalSpecialFine = $sLateFine + $sNoOutFine;

                        if ($totalSpecialFine > 0) {
                            $teamName = $team?->name ?? 'Tim Khusus';
                            $desc = "Potongan Absen {$teamName} Periode {$month} ({$sLates}x telat, {$sNoOuts}x tanpa absen pulang)";
                            Deduction::updateOrCreate(
                                [
                                    'user_id' => $linkedUser->id,
                                    'reference_id' => "SPECIAL_DEDUCT_{$teamId}_{$employee->id}_{$month}",
                                ],
                                [
                                    'type' => 'auto',
                                    'description' => $desc,
                                    'amount' => $totalSpecialFine,
                                    'deduction_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalDeductionsCount++;
                        }
                    }
                }

                // Tandai absensi sudah disinkronkan ke payroll
                if ($empAtts->isNotEmpty()) {
                    Attendance::whereIn('id', $empAtts->pluck('id'))->update(['is_synced_to_payroll' => true]);
                }
                if ($empSpecialAtts->isNotEmpty()) {
                    SpecialAttendance::whereIn('id', $empSpecialAtts->pluck('id'))->update(['is_synced_to_payroll' => true]);
                }

                // Hitung performa untuk evaluasi Reward
                $totalPresents = $empAtts->whereNotNull('clock_in')->count() + $empSpecialAtts->whereNotNull('clock_in')->count();
                $totalLateMins = $totalLateMinutes + (int) $empSpecialAtts->sum('late_minutes');

                if ($totalPresents > 0) {
                    $performanceScore = ($totalPresents * 100) - ($totalLateMins * 2) - ($missingOutCount * 15) - ($alphaCount * 50);
                    $performance[] = [
                        'user' => $linkedUser,
                        'employee' => $employee,
                        'area_id' => $employee->area_id,
                        'presents' => $totalPresents,
                        'late_minutes' => $totalLateMins,
                        'score' => $performanceScore,
                    ];
                }
            }

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
                        $sorted = $areaPerformers->sortByDesc('score')->values();
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
                    $globalSorted = collect($performance)->sortByDesc('score')->values();
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

                // C. Reward Divisi Maintenance / Lapangan (Top 3)
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
                        return str_contains($pos, 'maint') || str_contains($pos, 'teknisi') || str_contains($pos, 'field');
                    })->sortByDesc('score')->values();

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
                                    'description' => "Reward Absen Maintenance (Juara {$rank} Global) Periode {$month}",
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalRewardsCount++;
                        }
                    }
                }

                // D. Reward Tim Khusus (Top 3 per Grup)
                if (Setting::get('attendance_reward_special_team_enabled', '1') === '1') {
                    $specialNominals = [
                        1 => (float) Setting::get('attendance_reward_special_1', 200000),
                        2 => (float) Setting::get('attendance_reward_special_2', 150000),
                        3 => (float) Setting::get('attendance_reward_special_3', 100000),
                    ];

                    $specialGrouped = collect($performance)->filter(function ($item) {
                        $pos = strtolower($item['employee']->position ?? '');
                        $type = strtolower($item['employee']->employee_type ?? '');
                        return str_contains($pos, 'khusus') || str_contains($pos, 'special') || str_contains($type, 'khusus');
                    })->groupBy(function ($item) {
                        return $item['employee']->position ?: 'Tim Khusus';
                    });

                    foreach ($specialGrouped as $groupName => $groupMembers) {
                        $specialSorted = $groupMembers->sortByDesc('score')->values();
                        for ($rank = 1; $rank <= min(3, $specialSorted->count()); $rank++) {
                            $item = $specialSorted[$rank - 1];
                            $nominal = $specialNominals[$rank] ?? 0;
                            if ($nominal > 0) {
                                Incentive::updateOrCreate(
                                    [
                                        'user_id' => $item['user']->id,
                                        'reference_id' => "REWARD_SPECIAL_{$groupName}_{$rank}_{$month}",
                                    ],
                                    [
                                        'type' => 'auto',
                                        'description' => "Reward Tim Khusus (Juara {$rank} - {$groupName}) Periode {$month}",
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
            }
        });

        $this->info("Berhasil menyinkronkan {$totalDeductionsCount} potongan otomatis & {$totalRewardsCount} reward insentif ke modul Penggajian untuk periode {$month}.");
        return 0;
    }
}
