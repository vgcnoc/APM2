<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FieldDuty;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\SpecialAttendance;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BestAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $department = $request->input('department', 'all'); // 'all', 'maintenance', or specific position
        $scope = $request->input('scope', 'global'); // 'global' or 'branch'
        $selectedAreaId = $request->input('area_id', null);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Ambil seluruh data master
        $areas = Area::orderBy('name')->get();
        $employeesQuery = Employee::with(['area', 'shift']);

        if ($scope === 'branch' && $selectedAreaId) {
            $employeesQuery->where('area_id', $selectedAreaId);
        }

        $allEmployees = $employeesQuery->get();

        // 2. Ambil seluruh catatan presensi periode ini
        $attendances = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('employee_id');

        $workSchedules = WorkSchedule::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('employee_id');

        $specialAttendances = collect();
        if (class_exists(SpecialAttendance::class)) {
            try {
                $specialAttendances = SpecialAttendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get()
                    ->groupBy('employee_id');
            } catch (\Exception $e) {
                $specialAttendances = collect();
            }
        }

        $fieldDuties = collect();
        if (class_exists(FieldDuty::class)) {
            try {
                $fieldDuties = FieldDuty::whereBetween('duty_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('status', 'completed')
                    ->get()
                    ->groupBy('employee_id');
            } catch (\Exception $e) {
                $fieldDuties = collect();
            }
        }

        // 3. Konfigurasi Reward dari Setting
        $rewardsConfig = [
            'global_enabled' => Setting::get('attendance_reward_global_enabled', '1') === '1',
            'branch_enabled' => Setting::get('attendance_reward_branch_enabled', '1') === '1',
            'maintenance_enabled' => Setting::get('attendance_reward_maintenance_enabled', '1') === '1',
            'global' => [
                1 => (float) Setting::get('attendance_reward_global_1', 200000),
                2 => (float) Setting::get('attendance_reward_global_2', 150000),
                3 => (float) Setting::get('attendance_reward_global_3', 100000),
            ],
            'branch' => [
                1 => (float) Setting::get('attendance_reward_branch_1', 200000),
                2 => (float) Setting::get('attendance_reward_branch_2', 150000),
                3 => (float) Setting::get('attendance_reward_branch_3', 100000),
            ],
            'maintenance' => [
                1 => (float) Setting::get('attendance_reward_maint_1', 200000),
                2 => (float) Setting::get('attendance_reward_maint_2', 150000),
                3 => (float) Setting::get('attendance_reward_maint_3', 100000),
            ],
        ];

        // 4. Kalkulasi Performa Setiap Karyawan
        $calculatedEmployees = [];

        $isCurrentMonth = ($year === Carbon::now()->year && $month === Carbon::now()->month);
        $asOfDate = $isCurrentMonth ? Carbon::now()->endOfDay() : $endDate;

        // Hitung perkiraan hari kerja default (Senin - Sabtu) pada periode yang telah berjalan
        $defaultTargetDays = 0;
        $tempDate = $startDate->copy();
        while ($tempDate->lte($asOfDate)) {
            if ($tempDate->dayOfWeek !== 0) { // Bukan hari Minggu
                $defaultTargetDays++;
            }
            $tempDate->addDay();
        }
        $defaultTargetDays = max(1, $defaultTargetDays);

        foreach ($allEmployees as $emp) {
            $empAtts = $attendances->get($emp->id, collect());
            $empSchedules = $workSchedules->get($emp->id, collect());
            $empSpecials = $specialAttendances->get($emp->id, collect());
            $empDuties = $fieldDuties->get($emp->id, collect());

            // Target hari kerja (hanya hitung jadwal kerja aktif hingga tanggal asOfDate)
            if ($empSchedules->count() > 0) {
                $workDays = $empSchedules->filter(function ($s) use ($asOfDate) {
                    $d = Carbon::parse($s->date);
                    return $d->lte($asOfDate) && $s->status !== 'off' && !empty($s->shift_id);
                })->count();
                $targetDays = $workDays > 0 ? $workDays : $defaultTargetDays;
            } else {
                $targetDays = $defaultTargetDays;
            }

            // Kumpulkan tanggal kehadiran unik
            $presentDates = [];
            foreach ($empAtts as $att) {
                if ($att->clock_in) {
                    $d = Carbon::parse($att->date)->format('Y-m-d');
                    $presentDates[$d] = true;
                }
            }
            foreach ($empSpecials as $sp) {
                if ($sp->clock_in) {
                    $d = Carbon::parse($sp->date)->format('Y-m-d');
                    $presentDates[$d] = true;
                }
            }
            foreach ($empDuties as $fd) {
                if ($fd->check_in_time) {
                    $d = Carbon::parse($fd->duty_date)->format('Y-m-d');
                    $presentDates[$d] = true;
                }
            }
            $presents = count($presentDates);

            // Keterlambatan
            $lates = $empAtts->where('status', 'late')->count();
            $lateMinutes = (int) $empAtts->sum('late_minutes');

            // Alpha / Tidak Hadir
            $alphas = $empAtts->where('status', 'absent')->count();
            if ($empSchedules->count() > 0 && $presents < $targetDays) {
                $scheduleAlphas = $empSchedules->where('status', 'alpha')->count();
                if ($scheduleAlphas > $alphas) {
                    $alphas = $scheduleAlphas;
                }
            }

            // Pulang Cepat
            $earlyLeaves = 0;
            foreach ($empAtts as $att) {
                if ($att->clock_out && $att->shift && $att->shift->end_time) {
                    $clockOutTime = Carbon::parse($att->clock_out);
                    $shiftEndTime = Carbon::parse($att->shift->end_time);
                    if ($clockOutTime->lt($shiftEndTime)) {
                        $earlyLeaves++;
                    }
                }
            }

            // FORMULA SKOR:
            // Base Kehadiran = (Hadir / Target Hari) * 100
            // Penalti = keterlambatan + alpha + pulang cepat
            if ($presents === 0) {
                $finalScore = 0;
            } else {
                $baseAttendanceRate = ($targetDays > 0) ? min(100, ($presents / $targetDays) * 100) : 0;
                $penaltyLate = ($lates * 0.25) + ($lateMinutes * 0.01);
                $penaltyAlpha = ($alphas * 2.0);
                $penaltyEarly = ($earlyLeaves * 0.5);

                $finalScore = max(0, round($baseAttendanceRate - $penaltyLate - $penaltyAlpha - $penaltyEarly, 2));
            }

            $branchName = $emp->area ? $emp->area->name : ($emp->branch ?: 'Pusat');
            $position = $emp->position ?: 'Karyawan';
            $isMaintenance = preg_match('/(teknisi|maintenance|noc|jaringan|lapangan)/i', $position);

            $calculatedEmployees[] = [
                'id' => $emp->id,
                'name' => $emp->name,
                'email' => $emp->email,
                'phone' => $emp->phone,
                'photo' => $emp->photo,
                'position' => $position,
                'branch' => $branchName,
                'area_id' => $emp->area_id,
                'target_days' => $targetDays,
                'presents' => $presents,
                'lates' => $lates,
                'late_minutes' => $lateMinutes,
                'alphas' => $alphas,
                'early_leaves' => $earlyLeaves,
                'score' => $finalScore,
                'is_maintenance' => (bool) $isMaintenance,
                'reward_amount' => 0,
                'reward_title' => null,
                'rank' => '-',
                'is_branch_winner' => false,
                'is_global_winner' => false,
            ];
        }

        // Filter departemen / divisi
        $filteredCollection = collect($calculatedEmployees);
        if ($department === 'maintenance') {
            $filteredCollection = $filteredCollection->where('is_maintenance', true);
        } elseif ($department !== 'all') {
            $filteredCollection = $filteredCollection->filter(function ($item) use ($department) {
                return stripos($item['position'], $department) !== false;
            });
        }

        // Urutkan berdasarkan Skor tertinggi, Kehadiran terbanyak, Keterlambatan terendah
        $sortedEmployees = $filteredCollection->sort(function ($a, $b) {
            if ($b['score'] != $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            if ($b['presents'] != $a['presents']) {
                return $b['presents'] <=> $a['presents'];
            }
            return $a['late_minutes'] <=> $b['late_minutes'];
        })->values();

        // 5. PENETAPAN JUARA & REWARD BERDASARKAN SCOPE
        // Syarat mutlak masuk podium: memiliki riwayat kehadiran (presents > 0) dan skor > 0
        $qualifiedEmployees = $sortedEmployees->filter(function ($emp) {
            return $emp['presents'] > 0 && $emp['score'] > 0;
        })->values();

        $podium = [];
        $leaderboard = [];

        if ($scope === 'global') {
            // "Nominasi global: Juara 1 tiap cabang berkompetisi — Top 3 mendapat reward"
            // Cari Juara 1 dari setiap cabang di antara yang berkualifikasi
            $branchWinners = [];
            $byBranch = $qualifiedEmployees->groupBy('branch');

            foreach ($byBranch as $branchName => $branchGroup) {
                $topInBranch = $branchGroup->first();
                if ($topInBranch) {
                    $topInBranch['is_branch_winner'] = true;
                    $branchWinners[] = $topInBranch;
                }
            }

            // Juara cabang berkompetisi untuk Top 3 Global
            $sortedBranchWinners = collect($branchWinners)->sort(function ($a, $b) {
                if ($b['score'] != $a['score']) {
                    return $b['score'] <=> $a['score'];
                }
                return $b['presents'] <=> $a['presents'];
            })->values();

            // Ambil Top 3 Global
            $podiumList = [];
            for ($i = 0; $i < min(3, $sortedBranchWinners->count()); $i++) {
                $winner = $sortedBranchWinners[$i];
                $rank = $i + 1;
                $reward = $rewardsConfig['global'][$rank] ?? 0;
                $winner['rank'] = $rank;
                $winner['reward_amount'] = $reward;
                $winner['reward_title'] = "Juara #{$rank} Global";
                $winner['is_global_winner'] = true;
                $podiumList[] = $winner;
            }

            // Jika belum ada cukup juara cabang, fallback ke qualified employees lain
            if (count($podiumList) < 3 && $qualifiedEmployees->count() > count($podiumList)) {
                $assignedIds = array_column($podiumList, 'id');
                for ($i = 0; $i < $qualifiedEmployees->count() && count($podiumList) < 3; $i++) {
                    $cand = $qualifiedEmployees[$i];
                    if (!in_array($cand['id'], $assignedIds)) {
                        $rank = count($podiumList) + 1;
                        $cand['rank'] = $rank;
                        $cand['reward_amount'] = $rewardsConfig['global'][$rank] ?? 0;
                        $cand['reward_title'] = "Juara #{$rank} Global";
                        $cand['is_global_winner'] = true;
                        $podiumList[] = $cand;
                        $assignedIds[] = $cand['id'];
                    }
                }
            }

            $podium = $podiumList;

            // Seluruh karyawan di-rank untuk tabel
            $rankedLeaderboard = [];
            $podiumMap = collect($podium)->keyBy('id');

            $currentRank = 1;
            foreach ($sortedEmployees as $emp) {
                if ($podiumMap->has($emp['id'])) {
                    $rankedLeaderboard[] = $podiumMap->get($emp['id']);
                } else {
                    $hasAttended = ($emp['presents'] > 0 && $emp['score'] > 0);
                    $emp['rank'] = $hasAttended ? $currentRank : '-';
                    $emp['reward_amount'] = 0;
                    $emp['reward_title'] = null;
                    $rankedLeaderboard[] = $emp;
                }
                if ($emp['presents'] > 0 && $emp['score'] > 0) {
                    $currentRank++;
                }
            }
            $leaderboard = $rankedLeaderboard;

        } else {
            // Scope: 'branch' (Per Cabang)
            $podiumList = [];
            for ($i = 0; $i < min(3, $qualifiedEmployees->count()); $i++) {
                $winner = $qualifiedEmployees[$i];
                $rank = $i + 1;
                $reward = $rewardsConfig['branch'][$rank] ?? 0;
                $winner['rank'] = $rank;
                $winner['reward_amount'] = $reward;
                $winner['reward_title'] = "Juara #{$rank} Cabang";
                $winner['is_branch_winner'] = true;
                $podiumList[] = $winner;
            }
            $podium = $podiumList;

            $rankedLeaderboard = [];
            $podiumMap = collect($podium)->keyBy('id');
            $currentRank = 1;
            foreach ($sortedEmployees as $emp) {
                if ($podiumMap->has($emp['id'])) {
                    $rankedLeaderboard[] = $podiumMap->get($emp['id']);
                } else {
                    $hasAttended = ($emp['presents'] > 0 && $emp['score'] > 0);
                    $emp['rank'] = $hasAttended ? $currentRank : '-';
                    $emp['reward_amount'] = 0;
                    $emp['reward_title'] = null;
                    $rankedLeaderboard[] = $emp;
                }
                if ($emp['presents'] > 0 && $emp['score'] > 0) {
                    $currentRank++;
                }
            }
            $leaderboard = $rankedLeaderboard;
        }

        // 6. Ringkasan Statistik KPI Cards (5 Cards)
        $totalEmployeesCount = $filteredCollection->count();
        $avgScore = $totalEmployeesCount > 0 ? round($filteredCollection->avg('score'), 2) : 0;
        $perfectScoresCount = $filteredCollection->where('score', '>=', 100)->where('presents', '>', 0)->count();
        $totalLates = (int) $filteredCollection->sum('lates');
        $totalAlphas = (int) $filteredCollection->sum('alphas');

        // Daftar posisi unik untuk filter departemen
        $positions = Employee::whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->orderBy('position')
            ->pluck('position')
            ->values();

        return Inertia::render('Attendances/Best', [
            'filters' => [
                'month' => $month,
                'year' => $year,
                'department' => $department,
                'scope' => $scope,
                'area_id' => $selectedAreaId,
            ],
            'kpi' => [
                'total_employees' => $totalEmployeesCount,
                'avg_score' => $avgScore,
                'perfect_score_count' => $perfectScoresCount,
                'total_lates' => $totalLates,
                'total_alphas' => $totalAlphas,
            ],
            'podium' => $podium,
            'leaderboard' => $leaderboard,
            'areas' => $areas,
            'positions' => $positions,
            'rewardsConfig' => $rewardsConfig,
        ]);
    }

    /**
     * Detail riwayat presensi harian karyawan untuk popup modal interaktif
     */
    public function employeeDetail(Request $request, $employeeId)
    {
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $employee = Employee::with(['area', 'shift'])->findOrFail($employeeId);

        $attendances = Attendance::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get();

        $schedules = WorkSchedule::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get();

        $duties = FieldDuty::where('employee_id', $employeeId)
            ->whereBetween('duty_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('duty_date', 'desc')
            ->get();

        return response()->json([
            'employee' => $employee,
            'attendances' => $attendances,
            'schedules' => $schedules,
            'duties' => $duties,
        ]);
    }

    /**
     * Sinkronkan reward juara ke modul Payroll (Incentives)
     */
    public function syncPayroll(Request $request)
    {
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $winners = $request->input('winners', []);

        $validWinners = collect($winners)->filter(function ($w) {
            return !empty($w['id']) && (float) ($w['reward_amount'] ?? 0) > 0 && ($w['presents'] ?? 0) > 0;
        })->values();

        if ($validWinners->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data juara dengan presensi valid untuk disinkronkan ke payroll.');
        }

        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $syncedCount = 0;

        DB::transaction(function () use ($validWinners, $month, $year, $endDate, &$syncedCount) {
            foreach ($validWinners as $winner) {
                $empId = $winner['id'] ?? null;
                $rewardAmount = (float) ($winner['reward_amount'] ?? 0);
                $rewardTitle = $winner['reward_title'] ?? "Juara Absen Terbaik";

                if (!$empId || $rewardAmount <= 0) continue;

                $emp = Employee::find($empId);
                if (!$emp || !$emp->email) continue;

                $user = User::where('email', $emp->email)->first();
                if (!$user) continue;

                $periodName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

                Incentive::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => 'auto',
                        'description' => "Reward Absen Terbaik ({$rewardTitle}) Periode {$periodName}",
                    ],
                    [
                        'amount' => $rewardAmount,
                        'incentive_date' => $endDate->format('Y-m-d'),
                        'status' => 'pending',
                    ]
                );
                $syncedCount++;
            }
        });

        return redirect()->back()->with('success', "Berhasil menyinkronkan {$syncedCount} reward juara ke modul Penggajian (Payroll)!");
    }
}
