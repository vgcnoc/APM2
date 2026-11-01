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
        $employeesQuery = Employee::with(['area', 'shift', 'specialTeamMembers']);

        if (($scope === 'branch' || $scope === 'maintenance') && $selectedAreaId) {
            $employeesQuery->where('area_id', $selectedAreaId);
        }

        $allEmployees = $employeesQuery->get();

        // 2. Ambil seluruh catatan presensi periode ini
        $attendances = Attendance::with('shift')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
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

        // 3b. Pengaturan Batas Waktu Masuk Lebih Awal (Early Clock-in Limit)
        $clockInEarlyLimitEnabled = Setting::get('attendance_clock_in_early_limit_enabled', '1') === '1';
        $clockInEarlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));

        // 4. Kalkulasi Performa Setiap Karyawan
        $calculatedEmployees = [];

        $isCurrentMonth = ($year === Carbon::now()->year && $month === Carbon::now()->month);
        $asOfDate = $isCurrentMonth ? Carbon::now()->endOfDay() : $endDate;
        $todayStr = Carbon::today()->format('Y-m-d');

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

            // Hitung Jam Masuk dan Jam Pulang Karyawan
            $clockInSecs = [];
            $rawClockInSecs = [];
            $clockOutSecs = [];

            foreach ($empAtts as $att) {
                if (!empty($att->clock_in)) {
                    $cIn = Carbon::parse($att->clock_in);
                    $actualSec = ($cIn->hour * 3600) + ($cIn->minute * 60) + $cIn->second;
                    $rawClockInSecs[] = $actualSec;

                    // Evaluasi batas buka absen masuk
                    $attShift = $att->shift ?: ($emp->shift ?: null);
                    $officialOpenSec = 0;
                    if ($attShift && !empty($attShift->start_time)) {
                        $shiftStart = Carbon::parse(substr($attShift->start_time, 0, 5));
                        $earliestAllowed = $shiftStart->copy()->subMinutes($clockInEarlyLimitMinutes);
                        $officialOpenSec = ($earliestAllowed->hour * 3600) + ($earliestAllowed->minute * 60) + $earliestAllowed->second;
                    }

                    // Aturan: Absen masuk sebelum batas jam yang ditentukan kamera menolak / jika lolos dihitung jam 08.00
                    if ($clockInEarlyLimitEnabled && $officialOpenSec > 0 && $actualSec < $officialOpenSec) {
                        $clockInSecs[] = 8 * 3600; // 08:00:00 WIB penalty rule
                    } else {
                        $clockInSecs[] = $actualSec;
                    }
                }

                if (!empty($att->clock_out)) {
                    $cOut = Carbon::parse($att->clock_out);
                    $outSec = ($cOut->hour * 3600) + ($cOut->minute * 60) + $cOut->second;
                    $clockOutSecs[] = $outSec;
                }
            }

            foreach ($empDuties as $fd) {
                if (!empty($fd->check_in_time)) {
                    $cIn = Carbon::parse($fd->check_in_time);
                    $actualSec = ($cIn->hour * 3600) + ($cIn->minute * 60) + $cIn->second;
                    $rawClockInSecs[] = $actualSec;
                    $clockInSecs[] = $actualSec;
                }
                if (!empty($fd->check_out_time)) {
                    $cOut = Carbon::parse($fd->check_out_time);
                    $outSec = ($cOut->hour * 3600) + ($cOut->minute * 60) + $cOut->second;
                    $clockOutSecs[] = $outSec;
                }
            }

            // Rata-rata jam masuk & jam pulang
            $avgClockIn = !empty($clockInSecs) ? (array_sum($clockInSecs) / count($clockInSecs)) : 86400;
            $avgClockInDisplaySec = !empty($rawClockInSecs) ? (array_sum($rawClockInSecs) / count($rawClockInSecs)) : null;
            $avgClockInFormatted = $avgClockInDisplaySec !== null
                ? sprintf("%02d:%02d WIB", floor($avgClockInDisplaySec / 3600), floor(($avgClockInDisplaySec % 3600) / 60))
                : '-';

            $avgClockOutSec = !empty($clockOutSecs) ? (array_sum($clockOutSecs) / count($clockOutSecs)) : null;
            $avgClockOutFormatted = $avgClockOutSec !== null
                ? sprintf("%02d:%02d WIB", floor($avgClockOutSec / 3600), floor(($avgClockOutSec % 3600) / 60))
                : '-';

            // Jika hari ini hadir dan belum absen pulang
            $hasActiveAttendanceToday = $empAtts->contains(function ($a) use ($todayStr) {
                $d = Carbon::parse($a->date)->format('Y-m-d');
                return $d === $todayStr && !empty($a->clock_in) && empty($a->clock_out);
            });
            if ($avgClockOutFormatted === '-' && $hasActiveAttendanceToday) {
                $avgClockOutFormatted = 'Belum Pulang';
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

            // 1. Identifikasi apakah karyawan adalah Tim Khusus / Tim Serbu / Tim BC-4:
            // - Terdaftar di specialTeamMembers
            // - Memiliki riwayat SpecialAttendance
            // - Posisi atau tipe karyawan mengandung kata 'khusus', 'special', 'serbu', atau 'bc-4'
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

            // Tim Khusus dan Tim Serbu memiliki modul absen dan reward tersendiri,
            // sehingga HARUS TIDAK MASUK di fitur Absen Terbaik (Global, Per Cabang, maupun Maintenance)
            if ($isSpecialTeam) {
                continue;
            }

            // 2. Identifikasi apakah karyawan adalah Maintenance (khusus jabatan Maintenance, bukan Teknisi umum)
            $isMaintenance = (bool) preg_match('/maintenance/i', $position);

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
                'is_maintenance_winner' => false,
                'avg_clock_in' => $avgClockIn,
                'avg_clock_in_formatted' => $avgClockInFormatted,
                'avg_clock_out' => $avgClockOutSec,
                'avg_clock_out_formatted' => $avgClockOutFormatted,
            ];
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

        // Urutkan berdasarkan:
        // 1. Skor tertinggi
        // 2. Kehadiran terbanyak
        // 3. Keterlambatan terendah
        // 4. Pulang cepat terendah
        // 5. Tie-breaker keadilan: Jam masuk rata-rata lebih awal (avg_clock_in)
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
            if ($a['early_leaves'] != $b['early_leaves']) {
                return $a['early_leaves'] <=> $b['early_leaves'];
            }
            return ($a['avg_clock_in'] ?? 86400) <=> ($b['avg_clock_in'] ?? 86400);
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

            // Juara cabang berkompetisi untuk Top 3 Global dengan tie-breaker transparan
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
                if ($a['early_leaves'] != $b['early_leaves']) {
                    return $a['early_leaves'] <=> $b['early_leaves'];
                }
                return ($a['avg_clock_in'] ?? 86400) <=> ($b['avg_clock_in'] ?? 86400);
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
            // "Reward Maintenance: Top 3 Karyawan Maintenance"
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
                $winner['reward_title'] = "Juara #{$rank} Cabang";
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
        $positions = Employee::whereNotNull('position')
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
                'area_id' => $selectedAreaId,
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

        $formattedAttendances = $attendances->map(function ($att) {
            $inStr = '-';
            if (!empty($att->clock_in)) {
                $cIn = Carbon::parse($att->clock_in);
                $inStr = $cIn->format('H:i') . ' WIB';
            }
            $outStr = '-';
            if (!empty($att->clock_out)) {
                $cOut = Carbon::parse($att->clock_out);
                $outStr = $cOut->format('H:i') . ' WIB';
            }
            return [
                'id' => $att->id,
                'date' => $att->date ? Carbon::parse($att->date)->format('Y-m-d') : null,
                'formatted_date' => $att->formatted_date ?: ($att->date ? Carbon::parse($att->date)->translatedFormat('l, d F Y') : '-'),
                'clock_in' => $att->clock_in,
                'clock_out' => $att->clock_out,
                'formatted_clock_in' => $inStr,
                'formatted_clock_out' => $outStr,
                'status' => $att->status,
                'late_minutes' => (int) $att->late_minutes,
                'shift' => $att->shift ? [
                    'name' => $att->shift->name,
                    'start_time' => substr($att->shift->start_time, 0, 5),
                    'end_time' => substr($att->shift->end_time, 0, 5),
                ] : null,
            ];
        });

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
