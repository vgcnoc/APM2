<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\User;
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
    protected $description = 'Otomatis kalkulasi dan generate reward kehadiran terbaik (Cabang, Global, Maintenance, Tim Khusus) ke modul Penggajian (Payroll)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== Cron Auto Generate Reward Absen Terbaik ===");

        $isRewardEnabled = Setting::get('attendance_reward_enabled', '1') === '1';
        $isAutoGenerateEnabled = Setting::get('attendance_reward_auto_generate_enabled', '1') === '1';
        $autoDate = (int) Setting::get('attendance_reward_auto_date', 1);

        $today = Carbon::today();
        $now = Carbon::now();

        if (!$this->option('force')) {
            if (!$isRewardEnabled) {
                $this->warn("Fitur Reward Absen dinonaktifkan di pengaturan sistem.");
                return 0;
            }

            if (!$isAutoGenerateEnabled) {
                $this->warn("Auto Generate Reward dinonaktifkan di pengaturan sistem.");
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

        $this->info("Memproses data absensi untuk periode: {$month} ({$monthStart->format('d M Y')} s/d {$monthEnd->format('d M Y')})");

        $allMonthAtts = Attendance::with('employee')
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get()
            ->groupBy('employee_id');

        if ($allMonthAtts->isEmpty()) {
            $this->warn("Tidak ada catatan absensi yang ditemukan untuk periode {$month}.");
            return 0;
        }

        $performance = [];
        foreach ($allMonthAtts as $empId => $atts) {
            $emp = $atts->first()->employee;
            if (!$emp || !$emp->email) continue;
            $usr = User::where('email', $emp->email)->first();
            if (!$usr) continue;

            $presents = $atts->whereNotNull('clock_in')->count();
            $lates = $atts->where('status', 'late')->count();
            $lateMins = (int) $atts->sum('late_minutes');

            if ($presents > 0) {
                $score = ($presents * 100) - ($lateMins * 2);
                $performance[] = [
                    'user' => $usr,
                    'employee' => $emp,
                    'area_id' => $emp->area_id,
                    'presents' => $presents,
                    'lates' => $lates,
                    'late_minutes' => $lateMins,
                    'score' => $score,
                ];
            }
        }

        if (empty($performance)) {
            $this->warn("Tidak ada kandidat karyawan dengan kehadiran valid pada periode ini.");
            return 0;
        }

        $totalRewarded = 0;

        DB::transaction(function () use ($performance, $month, $monthEnd, &$totalRewarded) {
            // 1. REWARD PER CABANG
            if (Setting::get('attendance_reward_branch_enabled', '1') === '1') {
                $byArea = collect($performance)->groupBy('area_id');
                $branchNominals = [
                    1 => (float) Setting::get('attendance_reward_branch_1', 200000),
                    2 => (float) Setting::get('attendance_reward_branch_2', 150000),
                    3 => (float) Setting::get('attendance_reward_branch_3', 100000),
                ];

                foreach ($byArea as $areaId => $areaPerformers) {
                    $sorted = $areaPerformers->sortByDesc('score')->values();
                    for ($rank = 1; $rank <= min(3, $sorted->count()); $rank++) {
                        $item = $sorted[$rank - 1];
                        $nominal = $branchNominals[$rank] ?? 0;
                        if ($nominal > 0) {
                            Incentive::updateOrCreate(
                                [
                                    'user_id' => $item['user']->id,
                                    'type' => 'auto',
                                    'description' => "Reward Absen Terbaik (Juara {$rank} Cabang) Periode {$month}",
                                ],
                                [
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalRewarded++;
                        }
                    }
                }
            }

            // 2. REWARD GLOBAL PERUSAHAAN
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
                                'type' => 'auto',
                                'description' => "Reward Absen Terbaik (Juara {$rank} Global) Periode {$month}",
                            ],
                            [
                                'amount' => $nominal,
                                'incentive_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalRewarded++;
                    }
                }
            }

            // 3. REWARD MAINTENANCE (TOP 3 GLOBAL)
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
                    $isSpecial = str_contains($pos, 'khusus') || str_contains($pos, 'special') || str_contains($pos, 'serbu') || str_contains($type, 'khusus') || (method_exists($emp, 'specialTeamMembers') && $emp->specialTeamMembers && $emp->specialTeamMembers->isNotEmpty());
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
                                'type' => 'auto',
                                'description' => "Reward Absen Maintenance (Juara {$rank} Global) Periode {$month}",
                            ],
                            [
                                'amount' => $nominal,
                                'incentive_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalRewarded++;
                    }
                }
            }

            // 4. REWARD TIM KHUSUS (TOP 3 PER GRUP)
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
                                    'type' => 'auto',
                                    'description' => "Reward Tim Khusus (Juara {$rank} - {$groupName}) Periode {$month}",
                                ],
                                [
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $totalRewarded++;
                        }
                    }
                }
            }
        });

        $this->info("Berhasil men-generate {$totalRewarded} insentif reward absen ke modul Penggajian untuk periode {$month}.");
        return 0;
    }
}
