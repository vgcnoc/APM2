<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FieldDuty;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\SpecialAttendance;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BestAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendance_tab_best')) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman Papan Peringkat Absensi Terbaik.');
        }

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $department = $request->input('department', 'all'); // 'all', 'maintenance', or specific position
        $scope = $request->input('scope', 'global'); // 'global', 'branch', or 'maintenance'
        if (!in_array($scope, ['global', 'branch', 'maintenance'])) {
            $scope = 'global';
        }
        $selectedAreaId = $request->input('area_id', null);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Ambil seluruh data master
        $areas = Area::orderBy('name')->get();
        $shiftsById = Shift::all()->keyBy('id');
        $employeesQuery = Employee::with(['area', 'shift', 'specialTeamMembers'])->activeOnly();

        if ($scope === 'branch' && $selectedAreaId) {
            $employeesQuery->where('area_id', $selectedAreaId);
        }

        $allEmployees = $employeesQuery->get();

        // 2. Ambil seluruh catatan presensi periode ini
        $attendances = Attendance::with('shift')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('employee_id');

        $workSchedules = WorkSchedule::with('shift')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
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
                $fieldDuties = FieldDuty::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
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

        // 4. Kalkulasi Performa Setiap Karyawan Secara Cerdas & Adil Berbasis Jam & Hari Kerja Individu
        $calculatedEmployees = [];

        $isCurrentMonth = ($year === Carbon::now()->year && $month === Carbon::now()->month);
        $asOfDate = $isCurrentMonth ? Carbon::now()->endOfDay() : $endDate;
        $attSettings = AttendanceCalculatorService::getAttendanceSettings();

        foreach ($allEmployees as $emp) {
            $position = $emp->position ?: 'Karyawan';

            // Kecualikan Tim Khusus / Tim Serbu / BC-4 (memiliki modul absen dan reward tersendiri)
            $isSpecialTeam = false;
            if (method_exists($emp, 'specialTeamMembers') && $emp->specialTeamMembers && $emp->specialTeamMembers->isNotEmpty()) {
                $isSpecialTeam = true;
            }
            if (!$isSpecialTeam && class_exists(SpecialAttendance::class)) {
                if ($specialAttendances->has($emp->id) && $specialAttendances->get($emp->id)->isNotEmpty()) {
                    $isSpecialTeam = true;
                }
            }
            if (!$isSpecialTeam) {
                $isSpecialTeam = (bool) preg_match('/(khusus|special|serbu|bc-?4)/i', $position . ' ' . ($emp->employee_type ?? ''));
            }

            if ($isSpecialTeam) {
                continue;
            }

            $empAtts = $attendances->get($emp->id, collect());
            $empSchedules = $workSchedules->get($emp->id, collect());
            $empSpecials = $specialAttendances->get($emp->id, collect());
            $empDuties = $fieldDuties->get($emp->id, collect());

            // Panggil mesin kalkulasi cerdas yang mengevaluasi shift, hari kerja, dan toleransi
            $perf = AttendanceCalculatorService::calculateEmployeeBestAttendance(
                $emp,
                $empAtts,
                $empSchedules,
                $startDate,
                $endDate,
                $asOfDate,
                $empSpecials,
                $empDuties,
                $shiftsById,
                $attSettings
            );

            $empDept = (string) ($emp->department ?? '');
            $isMaintenance = (bool) preg_match('/maintenance/i', $position . ' ' . $empDept);
            $perf['is_maintenance'] = $isMaintenance;

            $calculatedEmployees[] = $perf;
        }

        // Filter departemen / divisi & scope
        $filteredCollection = collect($calculatedEmployees);
        if ($scope === 'maintenance') {
            $filteredCollection = $filteredCollection->where('is_maintenance', true);
        } elseif ($department === 'maintenance') {
            $filteredCollection = $filteredCollection->where('is_maintenance', true);
        } elseif ($department !== 'all') {
            $filteredCollection = $filteredCollection->filter(function ($item) use ($department) {
                return stripos($item['position'], $department) !== false;
            });
        }

        // Urutkan berdasarkan kriteria keadilan berlapis:
        // 1. Skor tertinggi
        // 2. Kehadiran terbanyak
        // 3. Menit keterlambatan terendah
        // 4. Frekuensi keterlambatan terendah
        // 5. Frekuensi pulang cepat terendah
        // 6. Tie-breaker keadilan shift: Rata-rata kedisiplinan hadir lebih awal relatif terhadap shift kerja (avg_early_minutes)
        $sortedEmployees = $filteredCollection->sort(function ($a, $b) {
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
        })->values();

        // 5. PENETAPAN JUARA & REWARD BERDASARKAN SCOPE
        $qualifiedEmployees = $sortedEmployees->filter(function ($emp) {
            return $emp['presents'] > 0 && $emp['score'] > 0;
        })->values();

        $podium = [];
        $leaderboard = [];

        if ($scope === 'global') {
            // "Nominasi global: Juara 1 tiap cabang berkompetisi — Top 3 mendapat reward"
            $branchWinners = [];
            $byBranch = $qualifiedEmployees->groupBy('branch');

            foreach ($byBranch as $branchName => $branchGroup) {
                $topInBranch = $branchGroup->first();
                if ($topInBranch) {
                    $topInBranch['is_branch_winner'] = true;
                    $branchWinners[] = $topInBranch;
                }
            }

            // Juara cabang berkompetisi untuk Top 3 Global dengan kriteria tie-breaker transparan
            $sortedBranchWinners = collect($branchWinners)->sort(function ($a, $b) {
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

        } elseif ($scope === 'maintenance') {
            $podiumList = [];
            for ($i = 0; $i < min(3, $qualifiedEmployees->count()); $i++) {
                $winner = $qualifiedEmployees[$i];
                $rank = $i + 1;
                $reward = $rewardsConfig['maintenance'][$rank] ?? 0;
                $winner['rank'] = $rank;
                $winner['reward_amount'] = $reward;
                $winner['reward_title'] = "Juara #{$rank} Maintenance";
                $winner['is_maintenance_winner'] = true;
                $podiumList[] = $winner;
            }
            $podium = $podiumList;

        } else {
            // Scope: 'branch' (Per Cabang)
            $podiumList = [];
            for ($i = 0; $i < min(3, $qualifiedEmployees->count()); $i++) {
                $winner = $qualifiedEmployees[$i];
                $rank = $i + 1;
                $reward = $rewardsConfig['branch'][$rank] ?? 0;
                $winner['rank'] = $rank;
                $winner['reward_amount'] = $reward;
                $branchTitle = "Juara #{$rank} Cabang";
                if ($selectedAreaId) {
                    $matchedArea = $areas->firstWhere('id', $selectedAreaId);
                    if ($matchedArea) {
                        $branchTitle = "Juara #{$rank} Cabang {$matchedArea->name}";
                    }
                }
                $winner['reward_title'] = $branchTitle;
                $winner['is_branch_winner'] = true;
                $podiumList[] = $winner;
            }
            $podium = $podiumList;
        }

        // Klasemen Seluruh Karyawan (Leaderboard Table)
        $rankedLeaderboard = [];
        $podiumMap = collect($podium)->keyBy('id');
        $podiumIds = $podiumMap->keys()->all();

        // 1. Tempatkan Juara Podium (Rank 1, 2, 3) di posisi teratas
        foreach ($podium as $winner) {
            $rankedLeaderboard[] = $winner;
        }

        // 2. Tempatkan sisa karyawan secara berurutan
        $currentRank = count($podium) + 1;
        foreach ($sortedEmployees as $emp) {
            if (in_array($emp['id'], $podiumIds)) {
                continue;
            }
            $hasAttended = ($emp['presents'] > 0 && $emp['score'] > 0);
            $emp['rank'] = $hasAttended ? $currentRank : '-';
            $emp['reward_amount'] = 0;
            $emp['reward_title'] = null;
            $rankedLeaderboard[] = $emp;
            if ($hasAttended) {
                $currentRank++;
            }
        }
        $leaderboard = $rankedLeaderboard;

        // 6. Ringkasan Statistik KPI Cards (5 Cards)
        $totalEmployeesCount = $filteredCollection->count();
        $avgScore = $totalEmployeesCount > 0 ? round($filteredCollection->avg('score'), 2) : 0;
        $perfectScoresCount = $filteredCollection->where('score', '>=', 100)->where('presents', '>', 0)->count();
        $totalLates = (int) $filteredCollection->sum('lates');
        $totalAlphas = (int) $filteredCollection->sum('alphas');

        // Daftar posisi unik untuk filter departemen (kecualikan tim serbu dan tim khusus)
        $positions = Employee::excludeTokoAndDemo()->whereNotNull('position')
            ->where('position', '!=', '')
            ->where('position', 'NOT LIKE', '%serbu%')
            ->where('position', 'NOT LIKE', '%khusus%')
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
                'area_id' => ($scope === 'branch') ? $selectedAreaId : null,
                'is_month_completed' => $endDate->endOfDay()->isPast(),
                'is_current_month' => $isCurrentMonth,
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
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendance_tab_best')) {
            abort(403, 'Anda tidak memiliki hak akses ke detail presensi karyawan.');
        }

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $employee = Employee::with(['area', 'shift'])->findOrFail($employeeId);

        $schedules = WorkSchedule::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get();

        $attendances = Attendance::with('shift')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get();

        $specialAttendances = SpecialAttendance::with('team')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get();

        $formattedAttendances = $attendances->map(function ($att) use ($employee, $schedules) {
            $dStr = $att->date ? Carbon::parse($att->date)->format('Y-m-d') : '';
            $resolvedShift = AttendanceCalculatorService::resolveEmployeeShiftForDate($employee, $dStr, $att->shift, $schedules);
            $punc = AttendanceCalculatorService::evaluateAttendancePunctuality($att, $resolvedShift);

            $punctualityLabel = 'Tepat Waktu';
            if ($punc['is_late']) {
                $punctualityLabel = "Terlambat {$punc['late_minutes']} menit";
            } elseif ($punc['relative_early_minutes'] > 0) {
                $punctualityLabel = "{$punc['relative_early_minutes']} menit lebih awal";
            }

            return [
                'id' => $att->id,
                'date' => $dStr,
                'formatted_date' => $att->formatted_date ?: ($att->date ? Carbon::parse($att->date)->translatedFormat('l, d F Y') : '-'),
                'clock_in' => $att->clock_in,
                'clock_out' => $att->clock_out,
                'formatted_clock_in' => $punc['clock_in_formatted'],
                'formatted_clock_out' => $punc['clock_out_formatted'],
                'status' => $att->status,
                'is_late' => $punc['is_late'],
                'late_minutes' => $punc['late_minutes'],
                'is_early_leave' => $punc['is_early_leave'],
                'early_leave_minutes' => $punc['early_leave_minutes'],
                'relative_early_minutes' => $punc['relative_early_minutes'],
                'punctuality_label' => $punctualityLabel,
                'shift' => $resolvedShift ? [
                    'name' => $resolvedShift->name,
                    'start_time' => substr($resolvedShift->start_time, 0, 5),
                    'end_time' => substr($resolvedShift->end_time, 0, 5),
                    'formatted_time' => $resolvedShift->formatted_time,
                ] : null,
            ];
        });

        $formattedSpecial = $specialAttendances->map(function ($att) {
            $dStr = $att->date ? Carbon::parse($att->date)->format('Y-m-d') : '';
            $team = $att->team;
            $lateMinutes = (int) ($att->late_minutes ?? 0);
            $isLate = $att->status === 'late' || $lateMinutes > 0;
            $punctualityLabel = $isLate ? "Terlambat {$lateMinutes} menit" : 'Tepat Waktu';

            return [
                'id' => 'special_' . $att->id,
                'date' => $dStr,
                'formatted_date' => Carbon::parse($att->date)->translatedFormat('l, d F Y'),
                'clock_in' => $att->clock_in,
                'clock_out' => $att->clock_out,
                'formatted_clock_in' => $att->clock_in ? substr($att->clock_in, 0, 5) . ' WIB' : '-',
                'formatted_clock_out' => $att->clock_out ? substr($att->clock_out, 0, 5) . ' WIB' : '-',
                'status' => $att->status,
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'is_early_leave' => false,
                'early_leave_minutes' => 0,
                'relative_early_minutes' => 0,
                'punctuality_label' => $punctualityLabel,
                'shift' => $team ? [
                    'name' => $team->name,
                    'start_time' => substr($team->start_time, 0, 5),
                    'end_time' => substr($team->end_time, 0, 5),
                    'formatted_time' => substr($team->start_time, 0, 5) . ' - ' . substr($team->end_time, 0, 5),
                ] : null,
            ];
        });

        $formattedAttendances = $formattedAttendances->concat($formattedSpecial)->sortByDesc('date')->values();

        $duties = FieldDuty::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'employee' => $employee,
            'attendances' => $formattedAttendances,
            'schedules' => $schedules,
            'duties' => $duties,
        ]);
    }

    /**
     * Sinkronkan reward juara ke modul Payroll (Incentives)
     */
    public function syncPayroll(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('attendance_best_btn_sync'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyinkronkan reward juara ke payroll.');
        }

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
        if ($endDate->endOfDay()->isFuture()) {
            return redirect()->back()->with('error', 'Reward Absen Terbaik untuk periode bulan berjalan belum dapat disinkronkan ke payroll karena periode belum berakhir. Reward baru dapat disinkronkan setelah 1 bulan penuh selesai.');
        }

        $syncedCount = 0;

        DB::transaction(function () use ($validWinners, $month, $year, $endDate, &$syncedCount) {
            foreach ($validWinners as $winner) {
                $empId = $winner['id'] ?? null;
                $rewardAmount = (float) ($winner['reward_amount'] ?? 0);
                $rewardTitle = $winner['reward_title'] ?? "Juara Absen Terbaik";

                if (!$empId || $rewardAmount <= 0) continue;

                $emp = Employee::excludeTokoAndDemo()->find($empId);
                if (!$emp || !$emp->email || $emp->isExemptFromAttendanceAndPayroll()) continue;

                $user = User::where('email', $emp->email)->first();
                if (!$user || $user->isExemptFromAttendanceAndPayroll()) continue;

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
