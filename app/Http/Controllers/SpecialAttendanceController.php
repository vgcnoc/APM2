<?php

namespace App\Http\Controllers;

use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Setting;
use App\Models\SpecialAttendance;
use App\Models\SpecialTeam;
use App\Models\SpecialTeamMember;
use App\Models\User;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SpecialAttendanceController extends Controller
{
    /**
     * Tampilan Utama Absen Khusus
     */
    public function index(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendance_tab_special')) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman Absen Tim Khusus.');
        }

        // 1. Pastikan Ke-3 Tim Khusus Utama (Grup Tim Khusus, Tim Serbu, Tim BC-4) terdaftar
        $bc4Check = SpecialTeam::where('name', 'like', '%bc-4%')
            ->orWhere('name', 'like', '%bc 4%')
            ->orWhere('name', 'like', '%bc4%')
            ->orWhere('code', 'BC-04')
            ->first();
        if (!$bc4Check) {
            SpecialTeam::create([
                'name' => 'TIM BC-4',
                'code' => 'BC-04',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim operasional BC-4 tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
        }

        $serbuCheck = SpecialTeam::where('name', 'like', '%serbu%')->orWhere('code', 'TS-01')->first();
        if (!$serbuCheck) {
            SpecialTeam::create([
                'name' => 'TIM SERBU',
                'code' => 'TS-01',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim serbu maintenance dan insidental tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
        }

        $khususCheck = SpecialTeam::where('name', 'like', '%khusus%')->orWhere('code', 'TK-01')->first();
        if (!$khususCheck) {
            SpecialTeam::create([
                'name' => 'GRUP TIM KHUSUS',
                'code' => 'TK-01',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim khusus operasional dan lapangan tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
        }

        $teams = SpecialTeam::withCount('members')->where('is_active', true)->get();

        // Scope Perizinan
        $canViewAll = $user && ($user->hasRole('admin') || $user->role === 'admin' || $user->can('attendances_view_all'));
        $canViewArea = $user && !$canViewAll && $user->can('attendances_view_area');
        $canManageSpecial = $user && ($user->hasRole('admin') || $user->role === 'admin' || $user->can('attendances_special_manage'));

        // 2. Karyawan Aktif (Login User / Switcher)
        $currentEmployee = null;
        if ($user) {
            $currentEmployee = Employee::with(['area'])->where('email', $user->email)->first();
        }
        if (($canViewAll || $canViewArea) && $request->filled('employee_id')) {
            $selected = Employee::with(['area'])->find($request->employee_id);
            if ($selected) {
                if ($canViewAll || in_array($selected->area_id, $user->getAccessibleAreaIds())) {
                    $currentEmployee = $selected;
                }
            }
        }
        if (!$currentEmployee && ($canViewAll || $canViewArea)) {
            $currentEmployee = Employee::with(['area'])->first();
        }

        // Tentukan Tim Aktif
        $currentTeam = null;
        if ($request->filled('team_id')) {
            $currentTeam = SpecialTeam::with('employees')->find($request->team_id);
        }
        // Jika tidak ada parameter team_id dan karyawan ini merupakan anggota tim tertentu, utamakan timnya
        if (!$currentTeam && $currentEmployee) {
            $mySpecialMember = SpecialTeamMember::where('employee_id', $currentEmployee->id)
                ->whereHas('team', fn($q) => $q->where('is_active', true))
                ->first();
            if ($mySpecialMember) {
                $currentTeam = SpecialTeam::with('employees')->find($mySpecialMember->special_team_id);
            }
        }
        if (!$currentTeam) {
            $currentTeam = SpecialTeam::with('employees')->first();
        }
        if (!$currentTeam) {
            $currentTeam = SpecialTeam::create([
                'name' => 'GRUP TIM KHUSUS',
                'code' => 'TK-01',
                'start_time' => '08:30:00',
                'end_time' => '18:00:00',
                'tolerance_minutes' => 15,
                'without_radius' => true,
                'late_deduction_amount' => 10000,
                'no_out_deduction_amount' => 10000,
                'alpha_deduction_amount' => 35000,
                'description' => 'Grup absensi tim khusus tanpa batasan radius kantor.',
                'is_active' => true,
            ]);
            $teams = SpecialTeam::withCount('members')->where('is_active', true)->get();
        }

        $isCurrentEmployeeMember = false;
        if ($currentEmployee && $currentTeam) {
            $isCurrentEmployeeMember = SpecialTeamMember::where('special_team_id', $currentTeam->id)
                ->where('employee_id', $currentEmployee->id)
                ->exists();
        }

        // 3. Absensi Hari Ini untuk Karyawan Aktif (mendukung sesi lintas hari jika grup khusus berakhir lewat 00:00)
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay()->format('Y-m-d');
        $todayAttendance = null;
        $activeSpecialDate = $today;
        if ($currentEmployee && $currentTeam) {
            $todayAttendance = SpecialAttendance::where('special_team_id', $currentTeam->id)
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();

            if (!$todayAttendance || !$todayAttendance->clock_in) {
                $yesterdayOpen = SpecialAttendance::where('special_team_id', $currentTeam->id)
                    ->where('employee_id', $currentEmployee->id)
                    ->where('date', $yesterday)
                    ->whereNotNull('clock_in')
                    ->whereNull('clock_out')
                    ->first();

                if ($yesterdayOpen) {
                    $yDeadline = AttendanceCalculatorService::resolveSpecialCheckoutDeadline($yesterday, $currentTeam);
                    if ($now->lessThanOrEqualTo($yDeadline)) {
                        $todayAttendance = $yesterdayOpen;
                        $activeSpecialDate = $yesterday;
                    }
                }
            }
        }

        $activeSpecialDeadline = AttendanceCalculatorService::resolveSpecialCheckoutDeadline($activeSpecialDate, $currentTeam);
        $checkoutDeadlineLabel = $activeSpecialDeadline->format('Y-m-d') !== $now->format('Y-m-d')
            ? $activeSpecialDeadline->translatedFormat('d M Y, H:i') . ' WIB (Lintas Hari)'
            : $activeSpecialDeadline->format('H:i') . ' WIB';

        // Jendela Waktu Buka Absen Masuk (Batas Jam Masuk Lebih Awal / Early Clock-in Limit)
        $clockInEarlyLimitEnabled = Setting::get('attendance_clock_in_early_limit_enabled', '1') === '1';
        $clockInEarlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));
        $canBypassEarlyLimit = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_early_restriction') || $user->can('attendances_manage'));

        $clockInWindowInfo = null;
        if ($currentTeam && !empty($currentTeam->start_time)) {
            $scheduledStart = Carbon::parse($today . ' ' . $currentTeam->start_time);
            $earliestAllowed = $scheduledStart->copy()->subMinutes($clockInEarlyLimitMinutes);
            $isOpen = !$clockInEarlyLimitEnabled || $canBypassEarlyLimit || $now->greaterThanOrEqualTo($earliestAllowed);
            $minutesUntilOpen = $now->lessThan($earliestAllowed) ? (int) ceil($now->diffInRealMinutes($earliestAllowed, false)) : 0;

            $clockInWindowInfo = [
                'enabled' => $clockInEarlyLimitEnabled,
                'early_limit_minutes' => $clockInEarlyLimitMinutes,
                'is_restricted' => $clockInEarlyLimitEnabled && !$isOpen,
                'is_open' => $isOpen,
                'earliest_time' => $earliestAllowed->format('H:i'),
                'earliest_full_label' => $earliestAllowed->format('H:i') . ' WIB',
                'minutes_until_open' => max(0, $minutesUntilOpen),
                'shift_start' => substr($currentTeam->start_time, 0, 5),
                'shift_end' => substr($currentTeam->end_time, 0, 5),
            ];
        }

        // Jendela Waktu Buka Absen Pulang (Batas Jam Pulang Lebih Awal / Early Clock-Out Restriction)
        $clockOutEarlyRestrictionEnabled = Setting::get('attendance_clock_out_early_restriction_enabled', '1') === '1';
        $clockOutEarlyToleranceMinutes = (int) Setting::get('attendance_clock_out_early_tolerance_minutes', 0);
        $clockOutWindowInfo = null;
        if ($currentTeam && !empty($currentTeam->end_time)) {
            $scheduledEnd = Carbon::parse($activeSpecialDate . ' ' . $currentTeam->end_time);
            if ($currentTeam->start_time && $currentTeam->end_time && substr($currentTeam->end_time, 0, 5) < substr($currentTeam->start_time, 0, 5)) {
                $scheduledEnd->addDay();
            }
            $earliestAllowedOut = $scheduledEnd->copy()->subMinutes(max(0, $clockOutEarlyToleranceMinutes));
            $isOutOpen = !$clockOutEarlyRestrictionEnabled || $canBypassEarlyLimit || $now->greaterThanOrEqualTo($earliestAllowedOut);
            $minutesUntilOutOpen = $now->lessThan($earliestAllowedOut) ? (int) ceil($now->diffInRealMinutes($earliestAllowedOut, false)) : 0;

            $clockOutWindowInfo = [
                'enabled' => $clockOutEarlyRestrictionEnabled,
                'early_tolerance_minutes' => $clockOutEarlyToleranceMinutes,
                'is_restricted' => $clockOutEarlyRestrictionEnabled && !$isOutOpen && !$canBypassEarlyLimit,
                'is_open' => $isOutOpen,
                'earliest_time' => $earliestAllowedOut->format('H:i'),
                'earliest_full_label' => $earliestAllowedOut->format('H:i') . ' WIB',
                'minutes_until_open' => max(0, $minutesUntilOutOpen),
                'shift_start' => substr($currentTeam->start_time, 0, 5),
                'shift_end' => substr($currentTeam->end_time, 0, 5),
                'team_name' => $currentTeam->name,
            ];
        }

        // 4. Semua Absensi Hari Ini untuk Tim Aktif (Kolom Kanan)
        $todayAttendancesQuery = SpecialAttendance::with('employee')
            ->where('special_team_id', $currentTeam->id)
            ->where('date', $today);

        if ($canViewAll) {
            // Admin / View All: tanpa pembatasan area/karyawan
        } elseif ($canViewArea) {
            $accessibleAreaIds = $user->getAccessibleAreaIds();
            $todayAttendancesQuery->whereHas('employee', function ($q) use ($accessibleAreaIds) {
                $q->whereIn('area_id', $accessibleAreaIds);
            });
        } else {
            $todayAttendancesQuery->where('employee_id', $currentEmployee?->id ?? 0);
        }
        $todayAttendances = $todayAttendancesQuery->orderBy('clock_in', 'desc')->get();

        // 5. Anggota Tim Aktif
        $teamMembers = SpecialTeamMember::with('employee')
            ->where('special_team_id', $currentTeam->id)
            ->get();

        // 6. Data Riwayat (Tab 2): Menampilkan seluruh karyawan absen tim khusus
        $historyQuery = SpecialAttendance::with(['employee', 'team']);

        // Jika difilter per tim khusus tertentu di riwayat
        if ($request->filled('history_team_id') && $request->history_team_id !== 'all') {
            $historyQuery->where('special_team_id', $request->history_team_id);
        }

        if ($canViewAll) {
            // Admin / View All: tanpa pembatasan area/karyawan
        } elseif ($canViewArea) {
            $accessibleAreaIds = $user->getAccessibleAreaIds();
            $historyQuery->whereHas('employee', function ($q) use ($accessibleAreaIds) {
                $q->whereIn('area_id', $accessibleAreaIds);
            });
        } else {
            $historyQuery->where('employee_id', $currentEmployee?->id ?? 0);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $historyQuery->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            // Default 30 hari terakhir
            $historyQuery->where('date', '>=', Carbon::today()->subDays(30)->format('Y-m-d'));
        }

        if ($request->filled('filter_employee_id') && $request->filter_employee_id !== 'all') {
            $historyQuery->where('employee_id', $request->filter_employee_id);
        }

        if ($request->filled('filter_status') && $request->filter_status !== 'all') {
            $historyQuery->where('status', $request->filter_status);
        }

        $history = $historyQuery->orderBy('date', 'desc')->orderBy('clock_in', 'desc')->paginate(20)->withQueryString();

        // 7. Rekap Data Admin Bulanan (Tab 3)
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $monthStart = Carbon::parse($selectedMonth . '-01')->startOfMonth()->format('Y-m-d');
        $monthEnd = Carbon::parse($selectedMonth . '-01')->endOfMonth()->format('Y-m-d');

        $adminAttendances = SpecialAttendance::with('employee')
            ->where('special_team_id', $currentTeam->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy('employee_id');

        $adminMatrix = [];
        foreach ($teamMembers as $member) {
            $emp = $member->employee;
            if (!$emp) continue;

            $memberAtts = $adminAttendances->get($emp->id, collect());
            $presents = $memberAtts->whereNotNull('clock_in')->count();
            $lates = $memberAtts->where('status', 'late')->count();
            $lateMinutes = (int) $memberAtts->sum('late_minutes');
            $noClockOut = $memberAtts->filter(fn($att) => AttendanceCalculatorService::isSpecialMissingClockOut($att, $currentTeam, $now))->count();

            $clockInSecs = [];
            foreach ($memberAtts as $a) {
                if ($a->clock_in) {
                    $t = Carbon::parse($a->clock_in);
                    $clockInSecs[] = ($t->hour * 3600) + ($t->minute * 60) + $t->second;
                }
            }
            $avgClockIn = !empty($clockInSecs) ? (array_sum($clockInSecs) / count($clockInSecs)) : 86400;
            $avgClockInFormatted = !empty($clockInSecs)
                ? sprintf("%02d:%02d WIB", floor($avgClockIn / 3600), floor(($avgClockIn % 3600) / 60))
                : '-';

            $score = ($presents > 0) ? max(0, ($presents * 100) - ($lateMinutes * 2)) : 0;

            $adminMatrix[] = [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'team_id' => $currentTeam->id,
                'team_name' => $currentTeam->name,
                'role' => $member->role,
                'presents' => $presents,
                'lates' => $lates,
                'late_minutes' => $lateMinutes,
                'no_clock_out' => $noClockOut,
                'avg_clock_in' => $avgClockIn,
                'avg_clock_in_formatted' => $avgClockInFormatted,
                'score' => $score,
            ];
        }

        // 8. Leaderboard & Absen Terbaik (Tab 4): Perlombaan Ke-3 Tim (Tim Khusus, Tim Serbu, Tim BC-4)
        $allSpecialTeamMembers = SpecialTeamMember::with(['employee', 'team'])
            ->whereHas('team', fn($q) => $q->where('is_active', true))
            ->get();

        $allSpecialAttendancesMonth = SpecialAttendance::with(['employee', 'team'])
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy('employee_id');

        $competingMatrix = [];
        $processedEmpIds = [];

        foreach ($allSpecialTeamMembers as $member) {
            $emp = $member->employee;
            if (!$emp || in_array($emp->id, $processedEmpIds)) continue;
            $processedEmpIds[] = $emp->id;

            $memberTeam = $member->team ?: $currentTeam;
            $memberAtts = $allSpecialAttendancesMonth->get($emp->id, collect());
            $presents = $memberAtts->whereNotNull('clock_in')->count();
            $lates = $memberAtts->where('status', 'late')->count();
            $lateMinutes = (int) $memberAtts->sum('late_minutes');
            $noClockOut = $memberAtts->filter(fn($att) => AttendanceCalculatorService::isSpecialMissingClockOut($att, $memberTeam, $now))->count();

            // Rata-rata jam absen masuk untuk tie-breaker kedisiplinan
            // Absen yang tidak sesuai aturan (mendahului jam buka resmi) diset ke jam 08:00 (28.800 detik)
            // agar adil dan tidak mengalahkan karyawan yang disiplin menunggu jam buka resmi (misal 07:30).
            $clockInSecs = [];
            foreach ($memberAtts as $a) {
                if ($a->clock_in) {
                    $t = Carbon::parse($a->clock_in);
                    $actualSec = ($t->hour * 3600) + ($t->minute * 60) + $t->second;

                    $officialOpenSec = 0;
                    if ($clockInEarlyLimitEnabled && !empty($memberTeam?->start_time)) {
                        $teamStart = Carbon::parse($memberTeam->start_time);
                        $teamEarliest = $teamStart->copy()->subMinutes($clockInEarlyLimitMinutes);
                        $officialOpenSec = ($teamEarliest->hour * 3600) + ($teamEarliest->minute * 60) + $teamEarliest->second;
                    }

                    if ($officialOpenSec > 0 && $actualSec < $officialOpenSec) {
                        $clockInSecs[] = 8 * 3600; // 08:00:00 WIB
                    } else {
                        $clockInSecs[] = $actualSec;
                    }
                }
            }
            $avgClockIn = !empty($clockInSecs) ? (array_sum($clockInSecs) / count($clockInSecs)) : 86400;
            $avgClockInFormatted = !empty($clockInSecs)
                ? sprintf("%02d:%02d WIB", floor($avgClockIn / 3600), floor(($avgClockIn % 3600) / 60))
                : '-';

            // Poin dasar per kehadiran = 100, penalti menit telat = 2 pts, penalti tanpa pulang = 50 pts
            $score = ($presents > 0) ? max(0, ($presents * 100) - ($lateMinutes * 2) - ($noClockOut * 50)) : 0;

            $competingMatrix[] = [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'team_id' => $memberTeam->id,
                'team_name' => $memberTeam->name,
                'team_code' => $memberTeam->code,
                'role' => $member->role,
                'presents' => $presents,
                'lates' => $lates,
                'late_minutes' => $lateMinutes,
                'no_clock_out' => $noClockOut,
                'avg_clock_in' => $avgClockIn,
                'avg_clock_in_formatted' => $avgClockInFormatted,
                'score' => $score,
            ];
        }

        $leaderboard = collect($competingMatrix)
            ->filter(function ($item) {
                return $item['presents'] > 0 && $item['score'] > 0;
            })
            ->sort(function ($a, $b) {
                if ($b['score'] !== $a['score']) {
                    return $b['score'] <=> $a['score'];
                }
                if ($b['presents'] !== $a['presents']) {
                    return $b['presents'] <=> $a['presents'];
                }
                if ($a['lates'] !== $b['lates']) {
                    return $a['lates'] <=> $b['lates'];
                }
                if ($a['late_minutes'] !== $b['late_minutes']) {
                    return $a['late_minutes'] <=> $b['late_minutes'];
                }
                if ($a['no_clock_out'] !== $b['no_clock_out']) {
                    return $a['no_clock_out'] <=> $b['no_clock_out'];
                }
                return ($a['avg_clock_in'] ?? 86400) <=> ($b['avg_clock_in'] ?? 86400);
            })
            ->values()
            ->map(function ($item, $index) {
                $item['global_rank'] = $index + 1;
                return $item;
            });

        $rewardsConfig = [
            'juara_1' => (int) Setting::get('attendance_reward_special_1', 200000),
            'juara_2' => (int) Setting::get('attendance_reward_special_2', 150000),
            'juara_3' => (int) Setting::get('attendance_reward_special_3', 100000),
        ];

        // 9. Potongan Tim Khusus & Tim Serbu (Tab 5 & 6)
        $allTeamsDeductions = [];
        foreach ($teams as $t) {
            $tAtts = SpecialAttendance::with('employee')
                ->where('special_team_id', $t->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get();

            $tLateCount = $tAtts->where('status', 'late')->count();
            $tLateMins = (int) $tAtts->sum('late_minutes');
            $tNoOutCount = $tAtts->filter(fn($att) => AttendanceCalculatorService::isSpecialMissingClockOut($att, $t, $now))->count();

            $estDeductions = ($tLateCount * (float) $t->late_deduction_amount) + ($tNoOutCount * (float) $t->no_out_deduction_amount);

            $violationRecords = $tAtts->filter(function ($att) use ($t, $now) {
                return $att->status === 'late' || AttendanceCalculatorService::isSpecialMissingClockOut($att, $t, $now);
            })->map(function ($att) use ($t, $now) {
                $isLate = ($att->status === 'late');
                $isNoOut = AttendanceCalculatorService::isSpecialMissingClockOut($att, $t, $now);
                $lateFine = $isLate ? (float) $t->late_deduction_amount : 0;
                $noOutFine = $isNoOut ? (float) $t->no_out_deduction_amount : 0;

                return [
                    'id' => $att->id,
                    'date' => $att->date,
                    'employee_id' => $att->employee_id,
                    'employee_name' => $att->employee ? $att->employee->name : '-',
                    'position' => $att->employee ? $att->employee->position : 'Staff',
                    'clock_in' => $att->clock_in,
                    'clock_out' => $att->clock_out,
                    'status' => $att->status,
                    'late_minutes' => $att->late_minutes ?? 0,
                    'late_fine' => $lateFine,
                    'no_out_fine' => $noOutFine,
                    'total_fine' => $lateFine + $noOutFine,
                ];
            })->values();

            $allTeamsDeductions[$t->id] = [
                'team_id' => $t->id,
                'team_name' => $t->name,
                'late_count' => $tLateCount,
                'late_minutes' => $tLateMins,
                'no_out_count' => $tNoOutCount,
                'total_amount' => $estDeductions,
                'records' => $violationRecords,
            ];
        }

        // Daftar karyawan untuk switcher dan modal kelola anggota grup
        if ($canViewAll || $canManageSpecial) {
            $allEmployees = Employee::where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })->orderBy('name')->get(['id', 'name', 'position', 'branch', 'email']);
        } elseif ($canViewArea) {
            $accessibleAreaIds = $user->getAccessibleAreaIds();
            $allEmployees = Employee::where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })->whereIn('area_id', $accessibleAreaIds)->orderBy('name')->get(['id', 'name', 'position', 'branch', 'email']);
        } else {
            $allEmployees = $currentEmployee ? collect([$currentEmployee]) : collect();
        }

        // Daftar seluruh karyawan tim khusus untuk filter riwayat
        $allSpecialEmployees = Employee::whereHas('specialTeamMembers')
            ->where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'branch', 'email']);

        if ($allSpecialEmployees->isEmpty()) {
            $allSpecialEmployees = $allEmployees;
        }

        return Inertia::render('Attendances/Special', [
            'currentTeam' => $currentTeam,
            'teams' => $teams,
            'currentEmployee' => $currentEmployee,
            'todayAttendance' => $todayAttendance,
            'todayAttendances' => $todayAttendances,
            'teamMembers' => $teamMembers,
            'history' => $history,
            'adminMatrix' => $adminMatrix,
            'leaderboard' => $leaderboard,
            'rewardsConfig' => $rewardsConfig,
            'allTeamsDeductions' => $allTeamsDeductions,
            'allEmployees' => $allEmployees,
            'allSpecialEmployees' => $allSpecialEmployees,
            'isCurrentEmployeeMember' => $isCurrentEmployeeMember,
            'checkoutDeadlineLabel' => $checkoutDeadlineLabel,
            'clockInWindowInfo' => $clockInWindowInfo,
            'clockOutWindowInfo' => $clockOutWindowInfo,
            'filters' => $request->only(['team_id', 'start_date', 'end_date', 'filter_employee_id', 'filter_status', 'month', 'history_team_id']),
        ]);
    }

    /**
     * Absen Masuk Tim Khusus (Webcam / Foto Selfie)
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'special_team_id' => 'required|exists:special_teams,id',
            'employee_id' => 'required|exists:employees,id',
            'photo' => 'required|string', // base64
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $team = SpecialTeam::findOrFail($request->special_team_id);
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        $currentTimeStr = $now->format('H:i:s');

        // Validasi Pembatasan Jam Masuk Lebih Awal (Kamera Menolak Jika Belum Jam Yang Ditentukan)
        $earlyLimitEnabled = Setting::get('attendance_clock_in_early_limit_enabled', '1') === '1';
        $earlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));
        $user = $request->user();
        $canBypassEarlyLimit = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_early_restriction') || $user->can('attendances_manage'));

        if ($earlyLimitEnabled && !$canBypassEarlyLimit && !empty($team->start_time)) {
            $scheduledStartTime = Carbon::parse($today . ' ' . $team->start_time);
            $earliestAllowed = $scheduledStartTime->copy()->subMinutes($earlyLimitMinutes);

            if ($now->lessThan($earliestAllowed)) {
                $openTimeStr = $earliestAllowed->format('H:i');
                $minutesLeft = (int) ceil($now->diffInRealMinutes($earliestAllowed, false));

                if ($minutesLeft >= 60) {
                    $hours = intdiv($minutesLeft, 60);
                    $remMinutes = $minutesLeft % 60;
                    $diffText = $remMinutes > 0 ? "{$hours} jam {$remMinutes} menit" : "{$hours} jam";
                } else {
                    $diffText = "{$minutesLeft} menit";
                }

                $msg = "Presensi Masuk Ditolak: Jam kerja {$team->name} baru dapat diabsen mulai pukul {$openTimeStr} WIB (maksimal {$earlyLimitMinutes} menit sebelum shift). Jam saat ini {$currentTimeStr} WIB. Kamera ditolak sampai {$openTimeStr} WIB (tersisa {$diffText}).";

                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg, 'error' => $msg], 422);
                }

                return back()->with('error', $msg);
            }
        }

        // Evaluasi Keterlambatan
        $scheduledStartTime = Carbon::parse($today . ' ' . $team->start_time);
        $toleranceMinutes = (int) $team->tolerance_minutes;
        $lateThreshold = $scheduledStartTime->copy()->addMinutes($toleranceMinutes);

        $status = 'present';
        $lateMinutes = 0;

        if ($now->greaterThan($lateThreshold)) {
            $status = 'late';
            $lateMinutes = (int) round($scheduledStartTime->diffInMinutes($now));
        }

        // Simpan Foto Selfie
        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'special_attendances',
            "in_{$request->employee_id}_{$today}"
        );

        $attendance = SpecialAttendance::updateOrCreate(
            [
                'special_team_id' => $team->id,
                'employee_id' => $request->employee_id,
                'date' => $today,
            ],
            [
                'clock_in' => $currentTimeStr,
                'photo_in' => $photoPath,
                'lat_in' => $request->latitude,
                'lng_in' => $request->longitude,
                'location_in' => $request->location_name ?: 'Lapangan / Titik Operasional',
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'notes' => $request->notes,
            ]
        );

        return back()->with('success', "Absensi Masuk {$team->name} berhasil dicatat pada jam {$currentTimeStr}" . ($status === 'late' ? " (Terlambat {$lateMinutes} menit)" : " (Tepat Waktu)"));
    }

    /**
     * Absen Pulang Tim Khusus
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'special_team_id' => 'required|exists:special_teams,id',
            'employee_id' => 'required|exists:employees,id',
            'photo' => 'required|string', // base64
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $team = SpecialTeam::findOrFail($request->special_team_id);
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $now = Carbon::now();
        $currentTimeStr = $now->format('H:i:s');

        $attendance = SpecialAttendance::where('special_team_id', $team->id)
            ->where('employee_id', $request->employee_id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            $yesterdayOpen = SpecialAttendance::where('special_team_id', $team->id)
                ->where('employee_id', $request->employee_id)
                ->where('date', $yesterday)
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->first();

            if ($yesterdayOpen) {
                $yDeadline = AttendanceCalculatorService::resolveSpecialCheckoutDeadline($yesterday, $team);
                if ($now->lessThanOrEqualTo($yDeadline) || $yDeadline->format('Y-m-d') === $today) {
                    $attendance = $yesterdayOpen;
                }
            }
        }

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', 'Anda belum melakukan absensi masuk hari ini.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Anda sudah melakukan absensi pulang hari ini.');
        }

        $attDateStr = is_string($attendance->date) ? substr($attendance->date, 0, 10) : $attendance->date->format('Y-m-d');

        // Batas Waktu Terakhir Absen Pulang Tim Khusus
        $autoLock = Setting::get('attendance_checkout_auto_lock', '1') === '1';
        if ($autoLock) {
            $deadlineCarbon = AttendanceCalculatorService::resolveSpecialCheckoutDeadline($attDateStr, $team);
            if ($now->greaterThan($deadlineCarbon)) {
                $deadlineLabel = $deadlineCarbon->format('Y-m-d') !== $attDateStr
                    ? $deadlineCarbon->translatedFormat('d M Y \p\u\k\u\l H:i')
                    : 'pukul ' . $deadlineCarbon->format('H:i');
                return back()->with('error', "Batas waktu terakhir absen pulang Tim Khusus ({$team->name}) telah berakhir pada {$deadlineLabel} WIB. Absensi dikunci otomatis (Lupa Absen Pulang). Silakan hubungi Admin / Koordinator Tim.");
            }
        }

        // Evaluasi Pulang Awal
        $scheduledEndTime = Carbon::parse($attDateStr . ' ' . $team->end_time);
        if ($team->start_time && $team->end_time && substr($team->end_time, 0, 5) < substr($team->start_time, 0, 5)) {
            $scheduledEndTime->addDay();
        }
        $earlyMinutes = 0;
        if ($now->lessThan($scheduledEndTime)) {
            $earlyMinutes = (int) round($now->diffInMinutes($scheduledEndTime));
        }

        // Validasi Pembatasan Jam Absen Pulang Lebih Awal (Kamera & Server Menolak Jika Jam Belum Selesai)
        $clockOutEarlyRestrictionEnabled = Setting::get('attendance_clock_out_early_restriction_enabled', '1') === '1';
        $clockOutEarlyToleranceMinutes = (int) Setting::get('attendance_clock_out_early_tolerance_minutes', 0);
        $user = $request->user();
        $canBypassEarlyLimit = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_early_restriction') || $user->can('attendances_manage'));

        if ($clockOutEarlyRestrictionEnabled && !$canBypassEarlyLimit && !empty($team->end_time)) {
            $earliestAllowedOut = $scheduledEndTime->copy()->subMinutes(max(0, $clockOutEarlyToleranceMinutes));
            if ($now->lessThan($earliestAllowedOut)) {
                $openTimeStr = $earliestAllowedOut->format('H:i');
                $minutesLeft = (int) ceil($now->diffInRealMinutes($earliestAllowedOut, false));
                if ($minutesLeft >= 60) {
                    $hours = intdiv($minutesLeft, 60);
                    $remMinutes = $minutesLeft % 60;
                    $diffText = $remMinutes > 0 ? "{$hours} jam {$remMinutes} menit" : "{$hours} jam";
                } else {
                    $diffText = "{$minutesLeft} menit";
                }
                $currentTimeStr = $now->format('H:i:s');
                $shiftEndStr = substr($team->end_time, 0, 5);
                $shiftStartStr = substr($team->start_time, 0, 5);
                $msg = "Presensi Pulang Ditolak: Jam operasional Tim ({$team->name}: {$shiftStartStr} - {$shiftEndStr} WIB) belum selesai. Presensi pulang baru dapat diabsen mulai pukul {$openTimeStr} WIB. Jam saat ini {$currentTimeStr} WIB. Kamera ditolak sampai {$openTimeStr} WIB (tersisa {$diffText}).";

                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg, 'error' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        // Simpan Foto Selfie Pulang
        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'special_attendances',
            "out_{$request->employee_id}_{$attDateStr}"
        );

        $notesCombined = $attendance->notes;
        if ($request->notes) {
            $notesCombined = $notesCombined ? "{$notesCombined} | Pulang: {$request->notes}" : $request->notes;
        }

        $attendance->update([
            'clock_out' => $currentTimeStr,
            'photo_out' => $photoPath,
            'lat_out' => $request->latitude,
            'lng_out' => $request->longitude,
            'location_out' => $request->location_name ?: 'Lapangan / Titik Operasional',
            'early_leave_minutes' => $earlyMinutes,
            'notes' => $notesCombined,
        ]);

        return back()->with('success', "Absensi Pulang {$team->name} berhasil dicatat pada jam {$currentTimeStr}. Terima kasih atas kerja keras Anda!");
    }

    /**
     * Buat Grup Tim Khusus Baru (+ Grup Baru)
     */
    public function storeTeam(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendances_special_create_group') && !$user->can('attendances_special_manage'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat grup tim khusus baru.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_time' => 'required',
            'end_time' => 'required',
            'tolerance_minutes' => 'required|integer|min:0',
            'without_radius' => 'nullable|boolean',
            'late_deduction_amount' => 'required|numeric|min:0',
            'no_out_deduction_amount' => 'nullable|numeric|min:0',
            'alpha_deduction_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:employees,id',
        ]);

        $startTime = strlen($request->start_time) === 5 ? $request->start_time . ':00' : $request->start_time;
        $endTime = strlen($request->end_time) === 5 ? $request->end_time . ':00' : $request->end_time;

        $team = SpecialTeam::create([
            'name' => strtoupper($request->name),
            'code' => $request->code ?: ('TK-' . rand(10, 99)),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'tolerance_minutes' => $request->tolerance_minutes,
            'without_radius' => $request->boolean('without_radius', true),
            'late_deduction_amount' => $request->late_deduction_amount,
            'no_out_deduction_amount' => $request->input('no_out_deduction_amount', 10000),
            'alpha_deduction_amount' => $request->input('alpha_deduction_amount', 35000),
            'description' => $request->description,
            'is_active' => true,
        ]);

        if ($request->has('member_ids') && is_array($request->member_ids)) {
            foreach ($request->member_ids as $empId) {
                SpecialTeamMember::firstOrCreate([
                    'special_team_id' => $team->id,
                    'employee_id' => $empId,
                ], [
                    'role' => 'Anggota',
                    'is_leader' => false,
                ]);
            }
        }

        return redirect()->route('special-attendances.index', ['team_id' => $team->id])
            ->with('success', "Grup '{$team->name}' berhasil dibuat!");
    }

    /**
     * Perbarui Konfigurasi & Anggota Tim (Kelola)
     */
    public function updateTeam(Request $request, SpecialTeam $team)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendances_special_manage'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola konfigurasi dan anggota tim khusus.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'start_time' => 'required',
            'end_time' => 'required',
            'tolerance_minutes' => 'required|integer|min:0',
            'without_radius' => 'nullable|boolean',
            'late_deduction_amount' => 'required|numeric|min:0',
            'no_out_deduction_amount' => 'nullable|numeric|min:0',
            'alpha_deduction_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:employees,id',
        ]);

        $startTime = strlen($request->start_time) === 5 ? $request->start_time . ':00' : $request->start_time;
        $endTime = strlen($request->end_time) === 5 ? $request->end_time . ':00' : $request->end_time;

        $team->update([
            'name' => strtoupper($request->name),
            'code' => $request->code ?: $team->code,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'tolerance_minutes' => $request->tolerance_minutes,
            'without_radius' => $request->boolean('without_radius', true),
            'late_deduction_amount' => $request->late_deduction_amount,
            'no_out_deduction_amount' => $request->input('no_out_deduction_amount', 10000),
            'alpha_deduction_amount' => $request->input('alpha_deduction_amount', 35000),
            'description' => $request->description,
        ]);

        if ($request->has('member_ids')) {
            SpecialTeamMember::where('special_team_id', $team->id)->delete();
            foreach ($request->member_ids as $empId) {
                SpecialTeamMember::create([
                    'special_team_id' => $team->id,
                    'employee_id' => $empId,
                    'role' => 'Anggota',
                    'is_leader' => false,
                ]);
            }
        }

        return back()->with('success', "Konfigurasi dan anggota grup '{$team->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Grup Tim Khusus
     */
    public function destroyTeam(SpecialTeam $team)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendances_special_manage'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus grup tim khusus.');
        }

        $name = $team->name;
        $team->delete();
        return redirect()->route('special-attendances.index')->with('success', "Grup '{$name}' berhasil dihapus.");
    }

    /**
     * Admin Tambah / Koreksi Absensi Manual
     */
    public function adminStoreAttendance(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendances_special_tab_admin') && !$user->can('attendances_special_manage'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data absensi admin tim khusus.');
        }

        $request->validate([
            'special_team_id' => 'required|exists:special_teams,id',
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'clock_in' => 'nullable',
            'clock_out' => 'nullable',
            'status' => 'required|string',
            'late_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        SpecialAttendance::updateOrCreate(
            [
                'special_team_id' => $request->special_team_id,
                'employee_id' => $request->employee_id,
                'date' => $request->date,
            ],
            [
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'status' => $request->status,
                'late_minutes' => $request->late_minutes ?? 0,
                'notes' => $request->notes ?: 'Koreksi / Input Manual Admin',
            ]
        );

        return back()->with('success', 'Data absensi karyawan berhasil diperbarui oleh Admin.');
    }

    /**
     * Admin Hapus Rekap Absensi
     */
    public function adminDeleteAttendance(SpecialAttendance $attendance)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendances_special_tab_admin') && !$user->can('attendances_special_manage'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data absensi admin tim khusus.');
        }

        $attendance->delete();
        return back()->with('success', 'Catatan absensi berhasil dihapus.');
    }

    /**
     * Sinkronkan Potongan & Reward Tim Khusus ke Payroll
     */
    public function syncToPayroll(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && $user->role !== 'admin' && !$user->can('attendances_sync_payroll'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyinkronkan data tim khusus ke modul Payroll.');
        }

        $request->validate([
            'special_team_id' => 'nullable',
            'month' => 'required|date_format:Y-m',
        ]);

        $month = $request->month;
        $monthStart = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');
        $isSyncAll = empty($request->special_team_id) || $request->special_team_id === 'all';

        if ($isSyncAll) {
            $targetTeams = SpecialTeam::where('is_active', true)->get();
        } else {
            $targetTeams = SpecialTeam::where('id', $request->special_team_id)->get();
        }

        $totalSyncedDeductions = 0;
        $totalSyncedRewards = 0;

        DB::transaction(function () use ($targetTeams, $isSyncAll, $month, $monthStart, $monthEnd, &$totalSyncedDeductions, &$totalSyncedRewards) {
            $nowSync = Carbon::now();

            // 1. Sinkronkan Potongan Keterlambatan & Tanpa Pulang per Tim
            foreach ($targetTeams as $team) {
                $attendances = SpecialAttendance::with('employee')
                    ->where('special_team_id', $team->id)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->get()
                    ->groupBy('employee_id');

                foreach ($attendances as $empId => $atts) {
                    $emp = $atts->first()->employee;
                    if (!$emp || !$emp->email) continue;
                    $u = User::where('email', $emp->email)->first();
                    if (!$u) continue;

                    $presents = $atts->whereNotNull('clock_in')->count();
                    $lates = $atts->where('status', 'late')->count();
                    $noOutCount = $atts->filter(fn($att) => AttendanceCalculatorService::isSpecialMissingClockOut($att, $team, $nowSync))->count();

                    $totalDeduction = ($lates * (float) $team->late_deduction_amount) + ($noOutCount * (float) $team->no_out_deduction_amount);

                    if ($totalDeduction > 0) {
                        $latestIncident = $atts->filter(fn($att) => $att->status === 'late' || AttendanceCalculatorService::isSpecialMissingClockOut($att, $team, $nowSync))->sortByDesc('date')->first();
                        $incidentDate = $latestIncident 
                            ? Carbon::parse($latestIncident->date)->format('Y-m-d') 
                            : min(Carbon::today('Asia/Jakarta')->format('Y-m-d'), $monthEnd->format('Y-m-d'));

                        $desc = "Potongan Absen {$team->name} Periode {$month} ({$lates}x telat, {$noOutCount}x tanpa absen pulang)";
                        Deduction::updateOrCreate(
                            [
                                'user_id' => $u->id,
                                'type' => 'auto',
                                'description' => $desc,
                            ],
                            [
                                'amount' => $totalDeduction,
                                'deduction_date' => $incidentDate,
                                'status' => 'pending',
                            ]
                        );
                        $totalSyncedDeductions++;
                    }
                }
            }

            // 2. Reward Absen Terbaik Tim Khusus (Top 3 Global Seluruh Tim Khusus)
            // Selalu dievaluasi secara GLOBAL (Top 3 se-tim khusus), BUKAN per tim / grup!
            $isMonthCompleted = Carbon::parse($month . '-01')->endOfMonth()->endOfDay()->isPast();
            if ($isSyncAll && $isMonthCompleted && Setting::get('attendance_reward_special_team_enabled', '1') === '1') {
                $nominals = [
                    1 => (float) Setting::get('attendance_reward_special_1', 200000),
                    2 => (float) Setting::get('attendance_reward_special_2', 150000),
                    3 => (float) Setting::get('attendance_reward_special_3', 100000),
                ];

                $allMembers = SpecialTeamMember::with(['employee', 'team'])
                    ->whereHas('team', fn($q) => $q->where('is_active', true))
                    ->get();

                $allMonthAtts = SpecialAttendance::with(['employee', 'team'])
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->get()
                    ->groupBy('employee_id');

                $performance = [];
                $processedEmpIds = [];

                foreach ($allMembers as $member) {
                    $emp = $member->employee;
                    if (!$emp || in_array($emp->id, $processedEmpIds) || !$emp->email) continue;
                    $processedEmpIds[] = $emp->id;

                    $u = User::where('email', $emp->email)->first();
                    if (!$u) continue;

                    $mTeam = $member->team;
                    $mAtts = $allMonthAtts->get($emp->id, collect());
                    $presents = $mAtts->whereNotNull('clock_in')->count();
                    $lateMinutes = (int) $mAtts->sum('late_minutes');
                    $lates = $mAtts->where('status', 'late')->count();
                    $noOut = $mAtts->filter(fn($att) => AttendanceCalculatorService::isSpecialMissingClockOut($att, $mTeam, $nowSync))->count();

                    $score = ($presents > 0) ? max(0, ($presents * 100) - ($lateMinutes * 2) - ($noOut * 50)) : 0;

                    $clockInSecs = [];
                    foreach ($mAtts as $a) {
                        if ($a->clock_in) {
                            $t = Carbon::parse($a->clock_in);
                            $actualSec = ($t->hour * 3600) + ($t->minute * 60) + $t->second;

                            $officialOpenSec = 0;
                            if ($clockInEarlyLimitEnabled && !empty($mTeam?->start_time)) {
                                $teamStart = Carbon::parse($mTeam->start_time);
                                $teamEarliest = $teamStart->copy()->subMinutes($clockInEarlyLimitMinutes);
                                $officialOpenSec = ($teamEarliest->hour * 3600) + ($teamEarliest->minute * 60) + $teamEarliest->second;
                            }

                            if ($officialOpenSec > 0 && $actualSec < $officialOpenSec) {
                                $clockInSecs[] = 8 * 3600; // 08:00:00 WIB
                            } else {
                                $clockInSecs[] = $actualSec;
                            }
                        }
                    }
                    $avgClockIn = !empty($clockInSecs) ? (array_sum($clockInSecs) / count($clockInSecs)) : 86400;

                    if ($presents > 0 && $score > 0) {
                        $performance[] = [
                            'user' => $u,
                            'employee' => $emp,
                            'team_name' => $mTeam?->name ?? 'Tim Khusus',
                            'score' => $score,
                            'presents' => $presents,
                            'lates' => $lates,
                            'late_minutes' => $lateMinutes,
                            'avg_clock_in' => $avgClockIn,
                        ];
                    }
                }

                $sorted = collect($performance)->sort(function ($a, $b) {
                    if ($b['score'] !== $a['score']) {
                        return $b['score'] <=> $a['score'];
                    }
                    if ($b['presents'] !== $a['presents']) {
                        return $b['presents'] <=> $a['presents'];
                    }
                    if ($a['lates'] !== $b['lates']) {
                        return $a['lates'] <=> $b['lates'];
                    }
                    if ($a['late_minutes'] !== $b['late_minutes']) {
                        return $a['late_minutes'] <=> $b['late_minutes'];
                    }
                    return ($a['avg_clock_in'] ?? 86400) <=> ($b['avg_clock_in'] ?? 86400);
                })->values();

                // Bersihkan reward format per tim lama agar tidak dobel
                Incentive::where('reference_id', 'like', "REWARD_SPECIAL_%_{$month}")
                    ->where('reference_id', 'not like', "REWARD_SPECIAL_GLOBAL_%")
                    ->delete();

                for ($rank = 1; $rank <= min(3, $sorted->count()); $rank++) {
                    $winner = $sorted[$rank - 1];
                    $amt = $nominals[$rank] ?? 0;
                    if ($amt > 0) {
                        Incentive::updateOrCreate(
                            [
                                'user_id' => $winner['user']->id,
                                'reference_id' => "REWARD_SPECIAL_GLOBAL_{$rank}_{$month}",
                            ],
                            [
                                'type' => 'auto',
                                'description' => "Reward Absen Terbaik Tim Khusus (Juara {$rank} Global - {$winner['employee']->name}) Periode {$month}",
                                'amount' => $amt,
                                'incentive_date' => $monthEnd->format('Y-m-d'),
                                'status' => 'pending',
                            ]
                        );
                        $totalSyncedRewards++;
                    }
                }
            }
        });

        $label = $isSyncAll ? 'Seluruh Tim Khusus (3 Tim)' : ($targetTeams->first()?->name ?? 'Tim Khusus');
        $isMonthCompleted = Carbon::parse($month . '-01')->endOfMonth()->endOfDay()->isPast();
        $rewardNote = $totalSyncedRewards > 0 ? " & {$totalSyncedRewards} reward absen terbaik." : ($isMonthCompleted ? "." : " (Reward tim khusus dievaluasi setelah 1 bulan selesai).");
        return back()->with('success', "Berhasil menyinkronkan data {$label} ke Penggajian: {$totalSyncedDeductions} potongan{$rewardNote}");
    }

    /**
     * Simpan string base64 gambar selfie ke storage disk
     */
    private function saveBase64Photo(string $base64Data, string $folder, string $filename): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            $data = $base64Data;
            $type = 'jpg';
        }

        $decoded = base64_decode($data);
        if ($decoded === false) {
            throw new \Exception('Gagal memproses gambar selfie.');
        }

        $filePath = "{$folder}/{$filename}_" . time() . ".{$type}";
        Storage::disk('public')->put($filePath, $decoded);

        return $filePath;
    }
}
