<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\SpecialAttendance;
use App\Models\SpecialTeam;
use App\Models\SpecialTeamMember;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Halaman Utama Absensi & Monitoring Kehadiran
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && $user->role !== 'admin') {
            // Karyawan yang terdaftar dalam Tim Khusus dialihkan langsung ke Absen Tim Khusus
            $userEmployee = Employee::where('email', $user->email)->first();
            if ($userEmployee) {
                $specialTeamMember = SpecialTeamMember::with('team')
                    ->where('employee_id', $userEmployee->id)
                    ->whereHas('team', fn($q) => $q->where('is_active', true))
                    ->first();
                if ($specialTeamMember && $specialTeamMember->team) {
                    return redirect()->route('special-attendances.index', ['team_id' => $specialTeamMember->special_team_id]);
                }
            }

            if (!$user->can('attendance_tab_reguler')) {
                if ($user->can('attendance_tab_field')) {
                    return redirect()->route('field-duties.index');
                }
                if ($user->can('attendance_tab_special')) {
                    return redirect()->route('special-attendances.index');
                }
                if ($user->can('attendance_tab_best')) {
                    return redirect()->route('best-attendances.index');
                }
                abort(403, 'Anda tidak memiliki hak akses ke halaman absensi reguler.');
            }
        }
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // 1. Tentukan Karyawan Aktif
        $currentEmployee = null;
        $isUserExempt = $user && $user->isExemptFromAttendanceAndPayroll();
        if ($user && !$isUserExempt) {
            $currentEmployee = Employee::with(['area', 'shift'])
                ->excludeTokoAndDemo()
                ->where('email', $user->email)
                ->first();
        }

        $canViewAll = $user && ($user->hasRole('admin') || $user->can('attendances_view_all'));
        $canViewArea = $user && $user->can('attendances_view_area');

        // Jika ada filter / switcher employee_id (hanya jika memiliki hak akses melihat karyawan lain)
        if (($canViewAll || $canViewArea) && $request->filled('employee_id')) {
            $selected = Employee::with(['area', 'shift'])->excludeTokoAndDemo()->find($request->employee_id);
            if ($selected) {
                if ($canViewAll || in_array($selected->area_id, $user->getAccessibleAreaIds())) {
                    $currentEmployee = $selected;
                }
            }
        }

        // Fallback jika belum ada employee sama sekali untuk user ini (misal Admin login)
        if (!$currentEmployee && ($canViewAll || $canViewArea) && !$isUserExempt) {
            // Prioritaskan karyawan yang memiliki jadwal kerja hari ini, atau karyawan pertama
            $scheduledTodayEmpId = WorkSchedule::where('date', $today)
                ->whereNotNull('shift_id')
                ->where('status', '!=', 'off')
                ->whereHas('employee', fn($q) => $q->excludeTokoAndDemo())
                ->value('employee_id');

            if ($scheduledTodayEmpId) {
                $currentEmployee = Employee::with(['area', 'shift'])->excludeTokoAndDemo()->find($scheduledTodayEmpId);
            }

            if (!$currentEmployee) {
                $currentEmployee = Employee::with(['area', 'shift'])->excludeTokoAndDemo()->first();
            }
        }

        $employeeQuery = Employee::with(['area', 'shift'])->excludeTokoAndDemo()->orderBy('name');
        if (!$canViewAll) {
            if ($canViewArea) {
                $employeeQuery->whereIn('area_id', $user->getAccessibleAreaIds());
            } else {
                $employeeQuery->where('email', $user ? $user->email : '');
            }
        }
        $allEmployees = $employeeQuery->get(['id', 'name', 'position', 'branch', 'area_id', 'shift_id', 'email', 'iak_number']);

        // 2. Ambil Master Shifts & Areas
        $allShifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        $areas = Area::orderBy('name')->get();
        $positions = Position::orderBy('name')->get(['id', 'name']);

        // 3. Ambil Jadwal Shift Hari Ini & Data Absensi Aktif (termasuk sesi Shift Malam / Lintas Hari dari kemarin)
        $todaySchedule = null;
        $shiftToday = null;
        $todayAttendance = null;
        $activeBaseDate = $today;
        $isCrossMidnightSession = false;
        $yesterday = $now->copy()->subDay()->format('Y-m-d');

        if ($currentEmployee) {
            $todaySchedule = WorkSchedule::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();

            if ($todaySchedule) {
                if ($todaySchedule->status === 'off' || empty($todaySchedule->shift_id)) {
                    $shiftToday = null;
                } else {
                    $shiftToday = $todaySchedule->shift;
                }
            } else {
                // Fallback otomatis ke master shift karyawan atau shift standar divisi
                $shiftToday = $currentEmployee->shift ?: $this->resolveDefaultShiftForEmployee($currentEmployee);
            }

            $todayAttendance = Attendance::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();

            // Jika hari ini belum absen masuk, cek apakah ada sesi kemarin yang batas pulangnya masih aktif (Shift Malam / Shift Sore Lintas Hari)
            if (!$todayAttendance || !$todayAttendance->clock_in) {
                $yesterdayOpen = Attendance::with('shift')
                    ->where('employee_id', $currentEmployee->id)
                    ->where('date', $yesterday)
                    ->whereNotNull('clock_in')
                    ->whereNull('clock_out')
                    ->first();

                if ($yesterdayOpen) {
                    $yesterdayDeadline = AttendanceCalculatorService::resolveCheckoutDeadline($yesterdayOpen);
                    $autoLockCheckout = Setting::get('attendance_checkout_auto_lock', '1') === '1';
                    if ($now->lessThanOrEqualTo($yesterdayDeadline) || (!$autoLockCheckout && $yesterdayDeadline->format('Y-m-d') === $today)) {
                        $todayAttendance = $yesterdayOpen;
                        $shiftToday = $yesterdayOpen->shift ?: $shiftToday;
                        $activeBaseDate = $yesterday;
                        $isCrossMidnightSession = true;
                    }
                } else {
                    // Cek jika ada Shift Malam kemarin yang baru mulai <= 4 jam lalu dan karyawan terlambat masuk lewat 00:00
                    $yesterdayAttAny = Attendance::where('employee_id', $currentEmployee->id)
                        ->where('date', $yesterday)
                        ->first();

                    if (!$yesterdayAttAny) {
                        $yesterdaySchedule = WorkSchedule::with('shift')
                            ->where('employee_id', $currentEmployee->id)
                            ->where('date', $yesterday)
                            ->first();

                        $yesterdayShift = null;
                        if ($yesterdaySchedule) {
                            if ($yesterdaySchedule->status !== 'off' && !empty($yesterdaySchedule->shift_id)) {
                                $yesterdayShift = $yesterdaySchedule->shift;
                            }
                        } else {
                            $yesterdayShift = $currentEmployee->shift ?: $this->resolveDefaultShiftForEmployee($currentEmployee);
                        }

                        if ($yesterdayShift && $yesterdayShift->isCrossMidnight()) {
                            $yWin = AttendanceCalculatorService::resolveShiftWindow($yesterday, $yesterdayShift);
                            if ($yWin && $now->greaterThanOrEqualTo($yWin['start']) && $now->lessThanOrEqualTo($yWin['end'])) {
                                $closerToYesterday = true;
                                if ($shiftToday) {
                                    $tWin = AttendanceCalculatorService::resolveShiftWindow($today, $shiftToday);
                                    if ($tWin && abs($now->diffInMinutes($tWin['start'])) < abs($now->diffInMinutes($yWin['start']))) {
                                        $closerToYesterday = false;
                                    }
                                }
                                if ($closerToYesterday) {
                                    $todaySchedule = $yesterdaySchedule ?: $todaySchedule;
                                    $shiftToday = $yesterdayShift;
                                    $activeBaseDate = $yesterday;
                                    $isCrossMidnightSession = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        $effectiveShiftForDeadline = $todayAttendance?->shift ?: $shiftToday;
        $activeCheckoutDeadline = AttendanceCalculatorService::resolveCheckoutDeadlineForShift($activeBaseDate, $effectiveShiftForDeadline);
        $checkoutDeadlineInfo = [
            'mode' => Setting::get('attendance_checkout_deadline_mode', 'shift_relative'),
            'deadline_hours' => max(1, (int) Setting::get('attendance_checkout_deadline_hours', 4)),
            'deadline_formatted' => $activeCheckoutDeadline->format('H:i'),
            'deadline_full_label' => $activeCheckoutDeadline->format('Y-m-d') !== $now->format('Y-m-d')
                ? $activeCheckoutDeadline->translatedFormat('d M Y, H:i') . ' WIB (Lintas Hari)'
                : $activeCheckoutDeadline->format('H:i') . ' WIB',
            'is_cross_midnight_session' => $isCrossMidnightSession,
            'session_date_label' => $isCrossMidnightSession
                ? Carbon::parse($activeBaseDate)->translatedFormat('d M Y')
                : null,
        ];

        // 4.1 Jendela Waktu Buka Absen Masuk (Early Clock-in Limit)
        $clockInEarlyLimitEnabled = Setting::get('attendance_clock_in_early_limit_enabled', '1') === '1';
        $clockInEarlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));
        $canBypassEarlyLimit = (bool) ($user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_early_restriction') || $user->can('attendances_manage')));

        $clockInWindow = AttendanceCalculatorService::isClockInWindowOpen(
            $activeBaseDate,
            $shiftToday,
            $now,
            $clockInEarlyLimitMinutes,
            $clockInEarlyLimitEnabled
        );

        $clockInWindowInfo = [
            'enabled' => $clockInEarlyLimitEnabled,
            'early_limit_minutes' => $clockInEarlyLimitMinutes,
            'is_restricted' => $clockInWindow['is_restricted'] && !$canBypassEarlyLimit,
            'is_open' => $clockInWindow['is_open'] || $canBypassEarlyLimit,
            'earliest_time' => $clockInWindow['earliest_time'] ? $clockInWindow['earliest_time']->format('H:i') : null,
            'earliest_full_label' => $clockInWindow['earliest_time'] ? $clockInWindow['earliest_time']->format('H:i') . ' WIB' : null,
            'minutes_until_open' => $clockInWindow['minutes_until_open'],
            'shift_start' => $clockInWindow['shift_start'] ? $clockInWindow['shift_start']->format('H:i') : null,
            'shift_end' => $clockInWindow['shift_end'] ? $clockInWindow['shift_end']->format('H:i') : null,
            'shift_name' => $shiftToday?->name,
        ];

        // Jendela Waktu Buka Absen Pulang (Batas Jam Pulang Lebih Awal / Early Clock-out Restriction)
        $clockOutEarlyRestrictionEnabled = Setting::get('attendance_clock_out_early_restriction_enabled', '1') === '1';
        $clockOutEarlyToleranceMinutes = (int) Setting::get('attendance_clock_out_early_tolerance_minutes', 0);
        $activeShiftForOut = $todayAttendance?->shift ?: ($shiftToday ?: $currentEmployee?->shift);
        $activeDateForOut = $todayAttendance ? (is_string($todayAttendance->date) ? substr($todayAttendance->date, 0, 10) : $todayAttendance->date->format('Y-m-d')) : $activeBaseDate;

        $clockOutWindow = AttendanceCalculatorService::isClockOutWindowOpen(
            $activeDateForOut,
            $activeShiftForOut,
            $now,
            $clockOutEarlyToleranceMinutes,
            $clockOutEarlyRestrictionEnabled
        );

        $clockOutWindowInfo = [
            'enabled' => $clockOutEarlyRestrictionEnabled,
            'early_tolerance_minutes' => $clockOutEarlyToleranceMinutes,
            'is_restricted' => $clockOutWindow['is_restricted'] && !$canBypassEarlyLimit,
            'is_open' => $clockOutWindow['is_open'] || $canBypassEarlyLimit,
            'earliest_time' => $clockOutWindow['earliest_time'] ? $clockOutWindow['earliest_time']->format('H:i') : null,
            'earliest_full_label' => $clockOutWindow['earliest_time'] ? $clockOutWindow['earliest_time']->format('H:i') . ' WIB' : null,
            'minutes_until_open' => $clockOutWindow['minutes_until_open'],
            'shift_start' => $clockOutWindow['shift_start'] ? $clockOutWindow['shift_start']->format('H:i') : null,
            'shift_end' => $clockOutWindow['shift_end'] ? $clockOutWindow['shift_end']->format('H:i') : null,
            'shift_name' => $activeShiftForOut?->name,
        ];

        // 5. Jadwal Shift Minggu Ini (Senin s/d Minggu)
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weeklySchedules = [];
        $dayNamesIndo = [
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            6 => 'Sab',
            0 => 'Min',
        ];

        if ($currentEmployee) {
            $endOfWeek = $startOfWeek->copy()->addDays(6);
            $weekSchedules = WorkSchedule::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });

            $weekAttendances = Attendance::where('employee_id', $currentEmployee->id)
                ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });

            $empDefaultShift = $currentEmployee->shift ?: $this->resolveDefaultShiftForEmployee($currentEmployee);

            for ($i = 0; $i < 7; $i++) {
                $dayObj = $startOfWeek->copy()->addDays($i);
                $dateStr = $dayObj->format('Y-m-d');
                $sched = $weekSchedules->get($dateStr);
                $att = $weekAttendances->get($dateStr);

                $shiftLabel = '-';
                $shiftColor = 'gray';
                $shiftTime = '';

                if ($sched) {
                    if ($sched->status === 'off' || empty($sched->shift_id)) {
                        $shiftLabel = 'LIBUR';
                        $shiftColor = 'rose';
                    } else {
                        $shiftLabel = $sched->shift?->name ?: 'Shift';
                        $shiftColor = $sched->shift?->color ?: 'blue';
                        $shiftTime = $sched->shift?->formatted_time ?? '';
                    }
                } elseif ($empDefaultShift) {
                    $pos = strtolower(trim($currentEmployee->position ?? ''));
                    $isSundayOff = ($dayObj->dayOfWeek === 0) && (str_contains($pos, 'admin') || str_contains($pos, 'finance') || str_contains($pos, 'manager'));
                    if ($isSundayOff) {
                        $shiftLabel = 'LIBUR';
                        $shiftColor = 'rose';
                    } else {
                        $shiftLabel = $empDefaultShift->name;
                        $shiftColor = $empDefaultShift->color ?: 'blue';
                        $shiftTime = $empDefaultShift->formatted_time ?? '';
                    }
                }

                $weeklySchedules[] = [
                    'date' => $dateStr,
                    'day_name' => $dayNamesIndo[$dayObj->dayOfWeek],
                    'day_number' => (int) $dayObj->format('d'),
                    'is_today' => $dayObj->isToday(),
                    'is_sunday' => $dayObj->dayOfWeek === 0,
                    'shift_label' => $shiftLabel,
                    'shift_color' => $shiftColor,
                    'shift_time' => $shiftTime,
                    'has_attended' => !empty($att?->clock_in),
                    'attendance_status' => $att?->status ?? null,
                ];
            }
        }

        // 6. Informasi Area & Koordinat Kantor
        $officeLocation = null;
        if ($currentEmployee && $currentEmployee->area) {
            $officeLocation = [
                'id' => $currentEmployee->area->id,
                'name' => $currentEmployee->area->name,
                'address' => $currentEmployee->area->address,
                'latitude' => $currentEmployee->area->latitude ? (float) $currentEmployee->area->latitude : null,
                'longitude' => $currentEmployee->area->longitude ? (float) $currentEmployee->area->longitude : null,
                'radius_meters' => $currentEmployee->area->radius_meters ? (int) $currentEmployee->area->radius_meters : 200,
            ];
        }

        // 7. Data Monitoring Kehadiran Seluruh Karyawan Hari Ini (Admin Tab)
        $monitoringQuery = Attendance::with(['employee.area', 'area', 'shift'])
            ->where('date', $today)
            ->whereHas('employee', fn($q) => $q->excludeTokoAndDemo());
        if ($request->filled('area_id')) {
            $monitoringQuery->where(function ($q) use ($request) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('employee', function ($eq) use ($request) {
                      $eq->where('area_id', $request->area_id);
                  });
            });
        }
        $todayAllAttendances = $monitoringQuery->latest()->get();

        $totalEmployeesCount = Employee::excludeTokoAndDemo()->count();
        $presentCount = $todayAllAttendances->whereNotNull('clock_in')->count();
        $lateCount = $todayAllAttendances->where('status', 'late')->count();
        $onTimeCount = $todayAllAttendances->where('status', 'present')->where('late_minutes', '<=', 5)->count();
        $fieldDutyCount = $todayAllAttendances->where('attendance_type', 'dinas_luar')->count();

        // 8. Rekapitulasi Kehadiran Bulanan (Tab Rekap & Sinkronisasi Gaji)
        $selectedMonth = $request->get('month', $now->format('Y-m'));
        $monthStart = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($selectedMonth . '-01')->endOfMonth();

        $monthAttendances = Attendance::with(['employee.area', 'shift'])
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        // Rekap per Karyawan
        $monthlyRecap = $allEmployees->map(function ($emp) use ($monthAttendances, $monthStart, $monthEnd) {
            $empAtts = $monthAttendances->where('employee_id', $emp->id);
            $totalPresent = $empAtts->whereNotNull('clock_in')->count();
            $totalLate = $empAtts->where('status', 'late')->count();
            $totalLateMinutes = $empAtts->sum('late_minutes');
            $totalFieldDuty = $empAtts->where('attendance_type', 'dinas_luar')->count();
            $totalSynced = $empAtts->where('is_synced_to_payroll', true)->count();

            // Cek apakah karyawan terhubung ke user di sistem payroll
            $linkedUser = User::where('email', $emp->email)->first();

            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'iak_number' => $emp->iak_number,
                'position' => $emp->position,
                'area_name' => $emp->area?->name ?? ($emp->branch ?? '-'),
                'has_user' => !empty($linkedUser),
                'user_id' => $linkedUser?->id,
                'total_present' => $totalPresent,
                'total_late' => $totalLate,
                'total_late_minutes' => $totalLateMinutes,
                'total_field_duty' => $totalFieldDuty,
                'is_all_synced' => $totalLate > 0 ? ($totalSynced >= $totalLate) : true,
            ];
        });

        $isCurrentEmployeeSpecialTeam = false;
        $currentEmployeeSpecialTeam = null;
        if ($currentEmployee) {
            $specialMember = SpecialTeamMember::with('team')
                ->where('employee_id', $currentEmployee->id)
                ->whereHas('team', fn($q) => $q->where('is_active', true))
                ->first();
            if ($specialMember && $specialMember->team) {
                $isCurrentEmployeeSpecialTeam = true;
                $currentEmployeeSpecialTeam = $specialMember->team;
            }
        }

        return Inertia::render('Attendances/Index', [
            'todayDateStr' => $now->translatedFormat('l, d F Y'),
            'todayIso' => $today,
            'currentEmployee' => $currentEmployee,
            'isCurrentEmployeeSpecialTeam' => $isCurrentEmployeeSpecialTeam,
            'currentEmployeeSpecialTeam' => $currentEmployeeSpecialTeam ? [
                'id' => $currentEmployeeSpecialTeam->id,
                'name' => $currentEmployeeSpecialTeam->name,
                'code' => $currentEmployeeSpecialTeam->code,
                'start_time' => $currentEmployeeSpecialTeam->start_time,
                'end_time' => $currentEmployeeSpecialTeam->end_time,
            ] : null,
            'allEmployees' => $allEmployees,
            'allShifts' => $allShifts,
            'todaySchedule' => $todaySchedule,
            'shiftToday' => $shiftToday,
            'todayAttendance' => $todayAttendance,
            'weeklySchedules' => $weeklySchedules,
            'officeLocation' => $officeLocation,
            'monitoring' => [
                'attendances' => $todayAllAttendances,
                'totalEmployees' => $totalEmployeesCount,
                'presentCount' => $presentCount,
                'onTimeCount' => $onTimeCount,
                'lateCount' => $lateCount,
                'fieldDutyCount' => $fieldDutyCount,
                'absentCount' => max(0, $totalEmployeesCount - $presentCount),
            ],
            'monthlyRecap' => [
                'month' => $selectedMonth,
                'monthLabel' => $monthStart->translatedFormat('F Y'),
                'records' => $monthlyRecap,
                'totalRecords' => $monthlyRecap->count(),
            ],
            'areas' => $areas,
            'positions' => $positions,
            'canBypassAreaLock' => $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_area_restriction')),
            'checkoutDeadlineInfo' => $checkoutDeadlineInfo,
            'clockInWindowInfo' => $clockInWindowInfo,
            'clockOutWindowInfo' => $clockOutWindowInfo,
            'settings' => [
                'early_limit_enabled' => $clockInEarlyLimitEnabled,
                'early_limit_minutes' => $clockInEarlyLimitMinutes,
                'require_photo' => Setting::get('attendance_regular_require_photo', '1') === '1',
                'require_gps' => Setting::get('attendance_regular_require_gps', '1') === '1',
                'strict_radius' => Setting::get('attendance_regular_strict_radius', '1') === '1',
                'default_radius' => (int) Setting::get('attendance_regular_default_radius', 200),
                'allow_any_area' => Setting::get('attendance_regular_allow_any_area', '1') === '1',
                'restrict_employee_area' => Setting::get('attendance_regular_restrict_employee_area', '1') === '1',
                'allow_wfh' => Setting::get('attendance_regular_allow_wfh', '0') === '1',
                'allow_field' => Setting::get('attendance_regular_allow_field', '1') === '1',
                'allow_overtime' => Setting::get('attendance_regular_allow_overtime', '1') === '1',
                'deadline_mode' => Setting::get('attendance_checkout_deadline_mode', 'shift_relative'),
                'deadline_hours' => max(1, (int) Setting::get('attendance_checkout_deadline_hours', 4)),
                'deadline_time' => Setting::get('attendance_checkout_deadline_time', '23:30'),
                'min_work_hours' => (int) Setting::get('attendance_checkout_min_work_hours', 4),
            ],
            'filters' => [
                'area_id' => $request->area_id ?? '',
                'month' => $selectedMonth,
                'tab' => $request->tab ?? 'self',
            ]
        ]);
    }

    /**
     * Catat Absen Masuk (Clock In)
     */
    public function clockIn(Request $request)
    {
        $requirePhoto = Setting::get('attendance_regular_require_photo', '1') === '1';
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'photo' => $requirePhoto ? 'required|string' : 'nullable|string', // base64 photo
            'shift_id' => 'nullable|exists:shifts,id',
            'area_id' => 'nullable|exists:areas,id',
            'attendance_type' => 'nullable|string|in:wfo,dinas_luar,wfh,lembur',
            'duty_location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = Employee::with(['area', 'shift'])->findOrFail($request->employee_id);

        if ($employee->isExemptFromAttendanceAndPayroll() || ($request->user() && $request->user()->isExemptFromAttendanceAndPayroll())) {
            return back()->with('error', 'Akun Toko dan Demo tidak diberlakukan absensi.');
        }

        // Karyawan Tim Khusus harus absen lewat menu Absen Tim Khusus
        $specialCheck = SpecialTeamMember::with('team')
            ->where('employee_id', $employee->id)
            ->whereHas('team', fn($q) => $q->where('is_active', true))
            ->first();
        if ($specialCheck && $specialCheck->team) {
            return back()->with('error', "Karyawan {$employee->name} terdaftar dalam Absen Tim Khusus ({$specialCheck->team->name}). Silakan gunakan halaman Absen Tim Khusus.");
        }
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $now = Carbon::now();
        $timeStr = $now->format('H:i:s');

        // Cek apakah masih ada sesi absen masuk kemarin (Shift Malam / Lintas Hari) yang belum di-absen pulang dan batas pulangnya masih aktif
        $yesterdayOpen = Attendance::with('shift')
            ->where('employee_id', $employee->id)
            ->where('date', $yesterday)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if ($yesterdayOpen && $now->lessThanOrEqualTo(AttendanceCalculatorService::resolveCheckoutDeadline($yesterdayOpen))) {
            return back()->with('error', 'Anda masih memiliki sesi presensi shift sebelumnya yang belum di-absen pulang. Silakan lakukan absen pulang terlebih dahulu.');
        }

        // Cek apakah sudah absen masuk hari ini
        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $today)->first();
        if ($attendance && $attendance->clock_in) {
            return back()->with('error', 'Anda sudah melakukan absen masuk hari ini.');
        }

        $targetDate = $today;

        // Ambil jadwal shift hari ini atau gunakan shift yang dipilih
        $schedule = WorkSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $user = auth()->user();
        $canSelectShift = $user && ($user->hasRole('admin') || $user->can('attendance_select_shift') || $user->can('attendance_btn_set_shift_holiday') || $user->can('work_schedules_manage'));
        $requestedShiftId = $canSelectShift ? $request->shift_id : null;
        $shiftId = $requestedShiftId ?: ($schedule?->shift_id ?: ($employee->shift_id ?: $this->resolveDefaultShiftForEmployee($employee)?->id));
        $shift = $shiftId ? Shift::find($shiftId) : null;
        if (!$shift) {
            $shift = $this->resolveDefaultShiftForEmployee($employee);
        }

        // Deteksi cerdas: jika karyawan Shift Malam (contoh 23:00 - 08:00) baru datang terlambat sedikit lewat pukul 00:00 dini hari
        $yesterdayAnyAtt = Attendance::where('employee_id', $employee->id)->where('date', $yesterday)->first();
        if (!$yesterdayAnyAtt) {
            $yesterdaySchedule = WorkSchedule::with('shift')
                ->where('employee_id', $employee->id)
                ->where('date', $yesterday)
                ->first();

            $yesterdayShift = null;
            if ($yesterdaySchedule) {
                if ($yesterdaySchedule->status !== 'off' && !empty($yesterdaySchedule->shift_id)) {
                    $yesterdayShift = $yesterdaySchedule->shift;
                }
            } else {
                $yesterdayShift = $employee->shift ?: $this->resolveDefaultShiftForEmployee($employee);
            }

            $matchesYesterdayShift = !$requestedShiftId || ($yesterdayShift && (int) $requestedShiftId === (int) $yesterdayShift->id);

            if ($matchesYesterdayShift && $yesterdayShift && $yesterdayShift->isCrossMidnight()) {
                $yWin = AttendanceCalculatorService::resolveShiftWindow($yesterday, $yesterdayShift);
                if ($yWin && $now->greaterThanOrEqualTo($yWin['start']) && $now->lessThanOrEqualTo($yWin['end'])) {
                    $todayScheduledShift = ($schedule && $schedule->status !== 'off' && !empty($schedule->shift_id))
                        ? $schedule->shift
                        : (!$schedule ? ($employee->shift ?: $this->resolveDefaultShiftForEmployee($employee)) : null);

                    $closerToYesterday = true;
                    if ($todayScheduledShift) {
                        $tWin = AttendanceCalculatorService::resolveShiftWindow($today, $todayScheduledShift);
                        if ($tWin && abs($now->diffInMinutes($tWin['start'])) < abs($now->diffInMinutes($yWin['start']))) {
                            $closerToYesterday = false;
                        }
                    }
                    if ($closerToYesterday) {
                        $targetDate = $yesterday;
                        $schedule = $yesterdaySchedule;
                        $shift = $yesterdayShift;
                    }
                }
            }
        }

        // Cek apakah dijadwalkan Libur (OFF)
        $isHolidayOff = ($schedule && $schedule->status === 'off');

        // Auto-sinkronisasi ke Jadwal Kerja / Roster jika belum ada jadwal pada tanggal sesi ini
        if (!$schedule && $shift) {
            $schedule = WorkSchedule::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $targetDate,
                ],
                [
                    'shift_id' => $shift->id,
                    'status' => 'work',
                    'notes' => 'Sinkronisasi otomatis saat presensi masuk',
                ]
            );
        }

        // Tipe Kehadiran & Validasi
        $attendanceType = $request->attendance_type ?: 'wfo';
        if ($isHolidayOff) {
            $attendanceType = 'lembur'; // Masuk di hari libur otomatis diakui sebagai Lembur
        }

        // Jika role user tidak memiliki izin memilih tipe kehadiran, otomatis paksa ke 'wfo'
        $user = $request->user();
        if ($user && method_exists($user, 'can') && !$user->can('attendance_select_type') && !$isHolidayOff) {
            $attendanceType = 'wfo';
        }

        // ==============================================================
        // VALIDASI PEMBATASAN JAM ABSEN MASUK LEBIH AWAL (EARLY CLOCK-IN)
        // ==============================================================
        $earlyLimitEnabled = Setting::get('attendance_clock_in_early_limit_enabled', '1') === '1';
        $earlyLimitMinutes = max(1, (int) Setting::get('attendance_clock_in_early_limit_minutes', 60));
        $canBypassEarlyLimit = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_early_restriction') || $user->can('attendances_manage'));

        if ($earlyLimitEnabled && $shift && !empty($shift->start_time) && !$isHolidayOff && $attendanceType !== 'lembur' && !$canBypassEarlyLimit) {
            $clockInWindow = AttendanceCalculatorService::isClockInWindowOpen(
                $targetDate,
                $shift,
                $now,
                $earlyLimitMinutes,
                $earlyLimitEnabled
            );

            if (!$clockInWindow['is_open']) {
                $openTimeStr = $clockInWindow['earliest_time'] ? $clockInWindow['earliest_time']->format('H:i') : '--:--';
                $shiftStartStr = $clockInWindow['shift_start'] ? $clockInWindow['shift_start']->format('H:i') : substr($shift->start_time, 0, 5);
                $shiftEndStr = $clockInWindow['shift_end'] ? $clockInWindow['shift_end']->format('H:i') : substr($shift->end_time, 0, 5);
                $minutesLeft = $clockInWindow['minutes_until_open'];

                if ($minutesLeft >= 60) {
                    $hours = intdiv($minutesLeft, 60);
                    $remMinutes = $minutesLeft % 60;
                    $diffText = $remMinutes > 0 ? "{$hours} jam {$remMinutes} menit" : "{$hours} jam";
                } else {
                    $diffText = "{$minutesLeft} menit";
                }

                $currentTimeStr = $now->format('H:i:s');
                $msg = "Presensi Masuk Ditolak: Jam shift Anda ({$shift->name}: {$shiftStartStr} - {$shiftEndStr} WIB) baru dapat diabsen mulai pukul {$openTimeStr} WIB (maksimal {$earlyLimitMinutes} menit sebelum shift dimulai). Jam saat ini {$currentTimeStr} WIB. Kamera ditolak sampai {$openTimeStr} WIB (tersisa {$diffText}).";

                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg, 'error' => $msg], 422);
                }

                return back()->with('error', $msg);
            }
        }

        // ==============================================================
        // PENENTUAN AREA PRESENSI & VALIDASI KUNCI AREA REGULER KARYAWAN
        // ==============================================================
        $restrictEmployeeArea = Setting::get('attendance_regular_restrict_employee_area', '1') === '1';
        $user = auth()->user();
        $canBypassAreaLock = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_area_restriction'));

        // Ambil daftar area yang diizinkan untuk karyawan ini
        $allowedAreaIds = $employee->getAllowedAttendanceAreaIds();
        $isAreaRestricted = $restrictEmployeeArea && !$canBypassAreaLock && !empty($allowedAreaIds);

        $allowAnyArea = Setting::get('attendance_regular_allow_any_area', '1') === '1';
        $targetArea = null;

        if ($request->filled('area_id')) {
            $reqArea = Area::find($request->area_id);
            if ($reqArea) {
                $targetArea = $reqArea;
            }
        }

        if (!$targetArea && $request->latitude && $request->longitude) {
            // Auto-detect area kantor terdekat yang dalam radius
            $officeQuery = Area::whereNotNull('latitude')->whereNotNull('longitude');
            if ($isAreaRestricted) {
                $officeQuery->whereIn('id', $allowedAreaIds);
            }
            $allOfficeAreas = $officeQuery->get();
            $closestArea = null;
            $minDistance = null;

            foreach ($allOfficeAreas as $oArea) {
                $distMeters = $this->calculateDistanceMeters(
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $oArea->latitude,
                    (float) $oArea->longitude
                );
                $maxRad = (int) ($oArea->radius_meters ?: Setting::get('attendance_regular_default_radius', 200));

                if ($distMeters <= $maxRad) {
                    $targetArea = $oArea;
                    break;
                }

                if ($minDistance === null || $distMeters < $minDistance) {
                    $minDistance = $distMeters;
                    $closestArea = $oArea;
                }
            }

            if (!$targetArea) {
                $targetArea = $closestArea ?: $employee->area;
            }
        }

        if (!$targetArea) {
            $targetArea = $employee->area;
        }

        // Validasi Kunci Absensi Area Karyawan (Strict Lock)
        // Contoh: Karyawan A hanya di BC 1, Karyawan B hanya di BC 2
        if ($attendanceType === 'wfo' && $isAreaRestricted) {
            if ($targetArea && !$employee->canAttendInArea($targetArea->id)) {
                $allowedNames = Area::whereIn('id', $allowedAreaIds)->pluck('name')->implode(', ');
                return back()->with('error', "Presensi Masuk Ditolak: Anda hanya diizinkan melakukan presensi reguler di Area: {$allowedNames}. Area '{$targetArea->name}' bukan merupakan area penugasan absensi Anda.");
            }
        }

        $distanceCalculated = $request->distance ? (int) $request->distance : null;
        if ($attendanceType === 'wfo') {
            $strictRadius = Setting::get('attendance_regular_strict_radius', '1') === '1';
            $requireGps = Setting::get('attendance_regular_require_gps', '1') === '1';

            if ($targetArea && $targetArea->latitude && $targetArea->longitude) {
                if ($requireGps && (!$request->latitude || !$request->longitude)) {
                    return back()->with('error', "Titik koordinat GPS wajib diaktifkan untuk absensi reguler di area '{$targetArea->name}'. Pastikan GPS dan izin lokasi aktif di browser Anda.");
                }

                if ($request->latitude && $request->longitude) {
                    $distanceCalculated = $this->calculateDistanceMeters(
                        (float) $request->latitude,
                        (float) $request->longitude,
                        (float) $targetArea->latitude,
                        (float) $targetArea->longitude
                    );

                    $allowedRadius = (int) ($targetArea->radius_meters ?: Setting::get('attendance_regular_default_radius', 200));

                    if ($strictRadius && $distanceCalculated > $allowedRadius) {
                        return back()->with('error', "Presensi Masuk Ditolak: Anda berada di luar radius kantor cabang area '{$targetArea->name}'. Jarak Anda saat ini: {$distanceCalculated} meter dari titik kantor (Batas toleransi radius: {$allowedRadius} meter). Silakan pilih cabang yang sesuai atau mendekat ke lokasi kantor untuk melakukan presensi.");
                    }
                }
            }
        } elseif ($attendanceType === 'wfh') {
            $allowWfh = Setting::get('attendance_regular_allow_wfh', '0') === '1';
            if (!$allowWfh) {
                return back()->with('error', "Tipe presensi Work From Home (WFH) saat ini tidak diaktifkan di pengaturan sistem.");
            }
        }

        // Evaluasi Keterlambatan Berdasarkan Jadwal Shift & Toleransi Pengaturan
        $status = 'present';
        $lateMinutes = 0;
        if ($shift && $shift->start_time && $attendanceType !== 'dinas_luar' && !$isHolidayOff) {
            $shiftWindow = AttendanceCalculatorService::resolveShiftWindow($targetDate, $shift);
            $shiftStartTime = $shiftWindow ? $shiftWindow['start'] : Carbon::createFromFormat('Y-m-d H:i', $targetDate . ' ' . substr($shift->start_time, 0, 5));
            $toleranceMinutes = (int) ($shift->tolerance_minutes ?? Setting::get('attendance_late_tolerance', 10));
            $lateThreshold = $shiftStartTime->copy()->addMinutes($toleranceMinutes);

            if ($now->greaterThan($lateThreshold)) {
                $status = 'late';
                $lateMinutes = (int) round($shiftStartTime->diffInMinutes($now));
            }
        }

        // Simpan Foto Selfie Base64 jika ada
        $photoPath = $request->photo ? $this->saveBase64Photo($request->photo, 'attendances/in', "in_{$employee->id}_{$targetDate}") : null;

        $notesFinal = $request->notes;
        if ($isHolidayOff) {
            $notesFinal = $notesFinal ? ($notesFinal . ' (Masuk saat Jadwal Libur/Lembur)') : 'Masuk saat Jadwal Libur/Lembur';
        }

        Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $targetDate,
            ],
            [
                'work_schedule_id' => $schedule?->id,
                'shift_id' => $shift?->id,
                'area_id' => $targetArea?->id,
                'attendance_type' => $attendanceType,
                'clock_in' => $timeStr,
                'photo_in' => $photoPath,
                'lat_in' => $request->latitude,
                'lng_in' => $request->longitude,
                'distance_in' => $distanceCalculated,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'notes' => $notesFinal,
                'duty_location' => $targetArea ? $targetArea->name : $request->duty_location,
                'is_verified' => true,
            ]
        );

        // SINKRONISASI KE STATUS USER ON-DUTY (Teknisi / Staff Sedang Bertugas)
        if ($employee->email) {
            User::where('email', $employee->email)->update(['is_on_duty' => true]);
        }

        $typeLabel = match ($attendanceType) {
            'dinas_luar' => 'Dinas Luar Lapangan',
            'wfh' => 'Work From Home (WFH)',
            'lembur' => 'Lembur / Shift Tambahan',
            default => 'Di Kantor (WFO)',
        };

        $msg = $status === 'late'
            ? "Absen masuk ({$typeLabel}) berhasil tercatat pada {$now->format('H:i')} (Terlambat {$lateMinutes} menit)."
            : "Absen masuk ({$typeLabel}) berhasil tercatat pada {$now->format('H:i')}. Selamat bertugas!";

        return back()->with('success', $msg);
    }

    /**
     * Catat Absen Pulang (Clock Out)
     */
    public function clockOut(Request $request)
    {
        $requirePhoto = Setting::get('attendance_regular_require_photo', '1') === '1';
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_id' => 'nullable|integer|exists:attendances,id',
            'photo' => $requirePhoto ? 'required|string' : 'nullable|string',
            'area_id' => 'nullable|exists:areas,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = Employee::with('area')->findOrFail($request->employee_id);

        // Karyawan Tim Khusus harus absen lewat menu Absen Tim Khusus
        $specialCheck = SpecialTeamMember::with('team')
            ->where('employee_id', $employee->id)
            ->whereHas('team', fn($q) => $q->where('is_active', true))
            ->first();
        if ($specialCheck && $specialCheck->team) {
            return back()->with('error', "Karyawan {$employee->name} terdaftar dalam Absen Tim Khusus ({$specialCheck->team->name}). Silakan gunakan halaman Absen Tim Khusus.");
        }

        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $now = Carbon::now();
        $timeStr = $now->format('H:i:s');

        $attendance = null;
        if ($request->filled('attendance_id')) {
            $specificAtt = Attendance::with('shift')->where('employee_id', $employee->id)->find($request->attendance_id);
            if ($specificAtt && $specificAtt->clock_in && !$specificAtt->clock_out) {
                $attendance = $specificAtt;
            }
        }
        if (!$attendance) {
            $attendance = Attendance::with('shift')
                ->where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();
        }

        // Jika belum ada absensi hari ini, cek sesi absen kemarin yang belum clock-out (Shift Malam / Shift Sore Lintas Hari)
        if (!$attendance || !$attendance->clock_in) {
            $yesterdayOpen = Attendance::with('shift')
                ->where('employee_id', $employee->id)
                ->where('date', $yesterday)
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->first();

            if ($yesterdayOpen) {
                $yesterdayDeadline = AttendanceCalculatorService::resolveCheckoutDeadline($yesterdayOpen);
                if ($now->lessThanOrEqualTo($yesterdayDeadline) || $yesterdayDeadline->format('Y-m-d') === $today) {
                    $attendance = $yesterdayOpen;
                }
            }
        }

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', 'Anda belum melakukan absen masuk hari ini.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Anda sudah melakukan absen pulang hari ini.');
        }

        $attDateStr = is_string($attendance->date) ? substr($attendance->date, 0, 10) : $attendance->date->format('Y-m-d');
        $shift = $attendance->shift ?: ($attendance->shift_id ? Shift::find($attendance->shift_id) : null);

        // Pengecekan Batas Waktu Terakhir Absen Pulang (Clock-Out Cut-Off Berbasis Shift & Lintas Hari)
        $autoLock = Setting::get('attendance_checkout_auto_lock', '1') === '1';
        if ($autoLock) {
            $deadlineCarbon = AttendanceCalculatorService::resolveCheckoutDeadline($attendance);
            if ($now->greaterThan($deadlineCarbon)) {
                $deadlineLabel = $deadlineCarbon->format('Y-m-d') !== $attDateStr
                    ? $deadlineCarbon->translatedFormat('d M Y \p\u\k\u\l H:i')
                    : 'pukul ' . $deadlineCarbon->format('H:i');
                $shiftLabel = $shift ? " ({$shift->name}: {$shift->formatted_time})" : '';
                return back()->with('error', "Batas waktu terakhir absen pulang{$shiftLabel} adalah {$deadlineLabel} WIB. Waktu telah berakhir sehingga absensi pulang dikunci otomatis (Lupa Absen Pulang). Silakan hubungi Admin / HRD untuk koreksi manual.");
            }
        }

        // Pengecekan Durasi Kerja Minimal Sebelum Diizinkan Absen Pulang (Mendukung Lintas Tengah Malam)
        // Jika waktu selesai shift resmi (shift end) sudah tercapai, izinkan absen pulang agar shift 8 jam pas / shift malam terlambat tidak terkunci saat jam pulang resmi tiba.
        $minWorkHours = (int) Setting::get('attendance_checkout_min_work_hours', 4);
        if ($minWorkHours > 0 && !empty($attendance->clock_in)) {
            $hasReachedOfficialShiftEnd = false;
            if ($shift && !empty($shift->end_time)) {
                $shiftWindowForMin = AttendanceCalculatorService::resolveShiftWindow($attDateStr, $shift);
                if ($shiftWindowForMin && !empty($shiftWindowForMin['end']) && $now->greaterThanOrEqualTo($shiftWindowForMin['end'])) {
                    $hasReachedOfficialShiftEnd = true;
                }
            }

            if (!$hasReachedOfficialShiftEnd) {
                $clockInTime = AttendanceCalculatorService::resolveClockInDateTime($attendance, $shift);
                if ($clockInTime) {
                    $workedMinutes = (int) floor($clockInTime->diffInMinutes($now, false));
                    if ($workedMinutes < ($minWorkHours * 60)) {
                        $remainingMinutes = ($minWorkHours * 60) - max(0, $workedMinutes);
                        $remHours = (int) floor($remainingMinutes / 60);
                        $remMins = $remainingMinutes % 60;
                        $remStr = $remHours > 0 ? "{$remHours} jam {$remMins} menit" : "{$remMins} menit";
                        return back()->with('error', "Anda baru masuk pada pukul {$attendance->clock_in}. Durasi kerja minimal sebelum diizinkan absen pulang adalah {$minWorkHours} jam (kurang {$remStr} lagi).");
                    }
                }
            }
        }

        // ==============================================================
        // VALIDASI PEMBATASAN JAM ABSEN PULANG LEBIH AWAL (EARLY CLOCK-OUT)
        // ==============================================================
        $clockOutEarlyRestrictionEnabled = Setting::get('attendance_clock_out_early_restriction_enabled', '1') === '1';
        $clockOutEarlyToleranceMinutes = (int) Setting::get('attendance_clock_out_early_tolerance_minutes', 0);
        $user = auth()->user();
        $canBypassEarlyLimit = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->can('attendance_bypass_early_restriction') || $user->can('attendances_manage'));

        if ($clockOutEarlyRestrictionEnabled && $shift && !empty($shift->end_time) && $attendance->attendance_type !== 'dinas_luar' && !$canBypassEarlyLimit) {
            $clockOutWindow = AttendanceCalculatorService::isClockOutWindowOpen(
                $attDateStr,
                $shift,
                $now,
                $clockOutEarlyToleranceMinutes,
                $clockOutEarlyRestrictionEnabled
            );

            if (!$clockOutWindow['is_open']) {
                $openTimeStr = $clockOutWindow['earliest_time'] ? $clockOutWindow['earliest_time']->format('H:i') : '--:--';
                $shiftEndStr = $clockOutWindow['shift_end'] ? $clockOutWindow['shift_end']->format('H:i') : substr($shift->end_time, 0, 5);
                $shiftStartStr = $clockOutWindow['shift_start'] ? $clockOutWindow['shift_start']->format('H:i') : substr($shift->start_time, 0, 5);
                $minutesLeft = $clockOutWindow['minutes_until_open'];

                if ($minutesLeft >= 60) {
                    $hours = intdiv($minutesLeft, 60);
                    $remMinutes = $minutesLeft % 60;
                    $diffText = $remMinutes > 0 ? "{$hours} jam {$remMinutes} menit" : "{$hours} jam";
                } else {
                    $diffText = "{$minutesLeft} menit";
                }

                $currentTimeStr = $now->format('H:i:s');
                $msg = "Presensi Pulang Ditolak: Jam shift Anda ({$shift->name}: {$shiftStartStr} - {$shiftEndStr} WIB) belum selesai. Presensi pulang baru dapat diabsen mulai pukul {$openTimeStr} WIB. Jam saat ini {$currentTimeStr} WIB. Kamera ditolak sampai {$openTimeStr} WIB (tersisa {$diffText}).";

                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg, 'error' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        // ==============================================================
        // PENENTUAN AREA PULANG & VALIDASI RADIUS GEOFENCING MULTI-AREA
        // ==============================================================
        $allowAnyArea = Setting::get('attendance_regular_allow_any_area', '1') === '1';
        $targetArea = null;

        if ($attendance->area_id) {
            $targetArea = Area::find($attendance->area_id);
        } elseif ($request->filled('area_id') && $allowAnyArea) {
            $targetArea = Area::find($request->area_id);
        } else {
            $targetArea = $employee->area;
        }

        $distanceOutCalculated = $request->distance ? (int) $request->distance : null;
        if ($attendance->attendance_type === 'wfo') {
            $strictRadius = Setting::get('attendance_regular_strict_radius', '1') === '1';
            $requireGps = Setting::get('attendance_regular_require_gps', '1') === '1';

            if ($targetArea && $targetArea->latitude && $targetArea->longitude) {
                if ($requireGps && (!$request->latitude || !$request->longitude)) {
                    return back()->with('error', "Titik koordinat GPS wajib diaktifkan untuk absensi pulang di area '{$targetArea->name}'. Pastikan GPS aktif.");
                }

                if ($request->latitude && $request->longitude) {
                    $distanceOutCalculated = $this->calculateDistanceMeters(
                        (float) $request->latitude,
                        (float) $request->longitude,
                        (float) $targetArea->latitude,
                        (float) $targetArea->longitude
                    );

                    $allowedRadius = (int) ($targetArea->radius_meters ?: Setting::get('attendance_regular_default_radius', 200));

                    if ($strictRadius && $distanceOutCalculated > $allowedRadius) {
                        // Cek apakah user berada di dalam radius kantor cabang lain yang diizinkan
                        $foundAnother = false;
                        $userAllowedAreaIds = $employee->getAllowedAttendanceAreaIds();
                        $otherAreasQuery = Area::whereNotNull('latitude')->whereNotNull('longitude');
                        if (!$allowAnyArea && !empty($userAllowedAreaIds)) {
                            $otherAreasQuery->whereIn('id', $userAllowedAreaIds);
                        }
                        if ($allowAnyArea || !empty($userAllowedAreaIds)) {
                            $otherAreas = $otherAreasQuery->get();
                            foreach ($otherAreas as $oArea) {
                                $dOther = $this->calculateDistanceMeters(
                                    (float) $request->latitude,
                                    (float) $request->longitude,
                                    (float) $oArea->latitude,
                                    (float) $oArea->longitude
                                );
                                $radOther = (int) ($oArea->radius_meters ?: Setting::get('attendance_regular_default_radius', 200));
                                if ($dOther <= $radOther) {
                                    $targetArea = $oArea;
                                    $distanceOutCalculated = $dOther;
                                    $foundAnother = true;
                                    break;
                                }
                            }
                        }

                        if (!$foundAnother) {
                            return back()->with('error', "Absen Pulang Ditolak: Anda berada di luar radius kantor cabang area '{$targetArea->name}'. Jarak Anda saat ini: {$distanceOutCalculated} meter dari kantor (Batas toleransi radius: {$allowedRadius} meter).");
                        }
                    }
                }
            }
        }

        // Evaluasi Pulang Lebih Awal terhadap Jam Selesai Shift (Mendukung Shift Malam / Lintas Hari)
        $notesFinal = $request->notes ?: $attendance->notes;
        if ($shift && $shift->end_time && $attendance->attendance_type !== 'dinas_luar') {
            $shiftWindow = AttendanceCalculatorService::resolveShiftWindow($attDateStr, $shift);
            if ($shiftWindow) {
                $shiftEndTime = $shiftWindow['end'];
                $earlyTolerance = AttendanceCalculatorService::resolveEarlyLeaveTolerance();
                $earlyThreshold = $shiftEndTime->copy()->subMinutes($earlyTolerance);

                if ($now->lessThan($earlyThreshold)) {
                    $earlyMins = (int) round($now->diffInMinutes($shiftEndTime));
                    $earlyNote = "Pulang lebih awal {$earlyMins} menit (Jadwal shift berakhir: " . substr($shift->end_time, 0, 5) . ")";
                    $notesFinal = $notesFinal ? "{$notesFinal} | {$earlyNote}" : $earlyNote;
                }
            }
        }

        $photoPath = $request->photo ? $this->saveBase64Photo($request->photo, 'attendances/out', "out_{$employee->id}_{$attDateStr}") : null;

        $attendance->update([
            'clock_out' => $timeStr,
            'photo_out' => $photoPath,
            'lat_out' => $request->latitude,
            'lng_out' => $request->longitude,
            'distance_out' => $distanceOutCalculated,
            'notes' => $notesFinal,
            'area_id' => $attendance->area_id ?: $targetArea?->id,
        ]);

        // SINKRONISASI KE STATUS USER ON-DUTY (Teknisi Selesai Bertugas)
        if ($employee->email) {
            User::where('email', $employee->email)->update(['is_on_duty' => false]);
        }

        return back()->with('success', "Absen pulang berhasil tercatat pada {$now->format('H:i')}. Terima kasih atas kerja keras Anda!");
    }

    /**
     * Terapkan Shift Cepat Hari Ini (Quick Assign Shift)
     */
    public function quickAssignShift(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->can('attendance_select_shift') && !$user->can('attendance_btn_set_shift_holiday') && !$user->can('work_schedules_manage')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah shift atau menetapkan hari libur.');
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id'    => 'nullable',
            'status'      => 'nullable|string', // 'work' or 'off'
            'notes'       => 'nullable|string|max:255',
            'date'        => 'nullable|date_format:Y-m-d',
        ]);

        $date = $request->date ?: Carbon::today()->format('Y-m-d');
        $shiftId = $request->shift_id;
        $isOff = $request->status === 'off' || $shiftId === 'OFF' || empty($shiftId);

        if ($isOff) {
            $notes = $request->notes ?: 'Jadwal Libur (OFF)';
            $lowerNotes = strtolower($notes);
            $schedStatus = 'off';
            if (str_contains($lowerNotes, 'sakit')) {
                $schedStatus = 'sick';
            } elseif (str_contains($lowerNotes, 'izin') || str_contains($lowerNotes, 'cuti')) {
                $schedStatus = 'leave';
            }

            WorkSchedule::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'date'        => $date,
                ],
                [
                    'shift_id' => null,
                    'status'   => $schedStatus,
                    'notes'    => $notes,
                ]
            );

            return back()->with('success', "Jadwal berhasil diubah menjadi Libur/Izin: {$notes}");
        }

        $shift = Shift::findOrFail($shiftId);
        $notes = $request->notes ?: ("Shift " . $shift->name);

        WorkSchedule::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date'        => $date,
            ],
            [
                'shift_id' => $shift->id,
                'status'   => 'work',
                'notes'    => $notes,
            ]
        );

        return back()->with('success', "Shift {$shift->name} ({$shift->formatted_time}) berhasil diterapkan untuk hari ini.");
    }

    /**
     * Sinkronisasi Jadwal Kerja, Absensi, Potongan & Reward Otomatis ke Modul Penggajian (Payroll)
     */
    public function syncToPayroll(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('attendances_sync_payroll'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyinkronkan data absensi ke payroll.');
        }

        $request->validate([
            'month' => 'required|string', // Format: YYYY-MM
            'penalty_per_minute' => 'nullable|numeric|min:0',
            'fixed_penalty_per_late' => 'nullable|numeric|min:0',
        ]);

        $month = $request->month;
        $latePenaltyMode = Setting::get('attendance_late_mode', Setting::get('attendance_late_penalty_mode', 'Per Kejadian'));
        $latePenaltyModeSlug = str_contains(strtolower($latePenaltyMode), 'menit') ? 'per_menit' : 'per_kejadian';
        $penaltyPerMinute = (float) ($request->penalty_per_minute ?? Setting::get('attendance_late_per_minute_amount', 1000));
        $fixedPenalty = (float) ($request->fixed_penalty_per_late ?? Setting::get('attendance_late_amount', 10000));
        $missingOutAmount = (float) Setting::get('attendance_missing_out_amount', 10000);
        $earlyLeaveAmount = (float) Setting::get('attendance_early_leave_amount', 10000);
        $alphaAmount = (float) Setting::get('attendance_alpha_amount', 35000);

        $latePenaltyEnabled = Setting::get('attendance_late_enabled', Setting::get('attendance_late_penalty_enabled', '1')) === '1';
        $missingOutPenaltyEnabled = Setting::get('attendance_missing_out_enabled', Setting::get('attendance_missing_out_penalty_enabled', '1')) === '1';
        $earlyLeavePenaltyEnabled = Setting::get('attendance_early_leave_enabled', Setting::get('attendance_early_leave_penalty_enabled', '1')) === '1';
        $alphaPenaltyEnabled = Setting::get('attendance_alpha_enabled', Setting::get('attendance_alpha_penalty_enabled', '1')) === '1';

        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth();
        $todayDate = Carbon::today()->format('Y-m-d');
        $cutoffTime = Setting::get('attendance_checkout_deadline_time', '23:30');

        // Ambil seluruh data absensi reguler, absensi khusus, jadwal kerja, dan karyawan dalam periode
        $allMonthAtts = Attendance::with(['employee.area', 'shift'])
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $allSpecialAtts = SpecialAttendance::with(['employee', 'team'])
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $allWorkSchedules = WorkSchedule::whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get();

        $employees = Employee::with('area')
            ->excludeTokoAndDemo()
            ->where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })
            ->get();

        // 1. Eksekusi Sinkronisasi Seluruh Potongan Presensi secara Cerdas via AttendanceCalculatorService
        $deductionSync = AttendanceCalculatorService::syncMonthlyToPayroll($month);
        $syncedDeductionsCount = $deductionSync['synced_deductions_count'];
        $syncedRewardsCount = 0;

        DB::transaction(function () use (
            $employees, $allMonthAtts, $allSpecialAtts, $allWorkSchedules,
            $month, $monthStart, $monthEnd, $todayDate, $cutoffTime,
            &$syncedRewardsCount
        ) {
            $performance = [];

            foreach ($employees as $employee) {
                $linkedUser = null;
                if (!empty($employee->email)) {
                    $linkedUser = User::where('email', $employee->email)->first();
                }
                if (!$linkedUser && !empty($employee->phone)) {
                    $linkedUser = User::where('phone', $employee->phone)->first();
                }
                if (!$linkedUser && !empty($employee->name)) {
                    $linkedUser = User::where('name', $employee->name)->first();
                }
                if (!$linkedUser) continue;

                $empAtts = $allMonthAtts->where('employee_id', $employee->id);
                $empSpecialAtts = $allSpecialAtts->where('employee_id', $employee->id);

                $lateRegularAtts = $empAtts->where('status', 'late')->where('late_minutes', '>', 0);
                $totalLateMinutes = (int) $lateRegularAtts->sum('late_minutes');
                $nowEval = Carbon::now();
                $missingOutCount = $empAtts->filter(fn($att) => AttendanceCalculatorService::isMissingClockOut($att, $nowEval))->count();
                $alphaDates = $allWorkSchedules->where('employee_id', $employee->id)
                    ->where('status', 'work')
                    ->whereNotNull('shift_id')
                    ->pluck('date')
                    ->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))
                    ->unique()
                    ->filter(fn($d) => $d <= $todayDate);
                $regAttDates = $empAtts->whereNotNull('clock_in')->pluck('date')->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))->unique();
                $spAttDates = $empSpecialAtts->whereNotNull('clock_in')->pluck('date')->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))->unique();
                $alphaCount = $alphaDates->diff($regAttDates)->diff($spAttDates)->count();

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
            $isMonthCompleted = $monthEnd->endOfDay()->isPast();
            if ($isMonthCompleted && Setting::get('attendance_reward_enabled', '1') === '1' && !empty($performance)) {
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
                                $syncedRewardsCount++;
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
                            $syncedRewardsCount++;
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
                                    'description' => "Reward Absen Terbaik (Juara {$rank} Maintenance) Periode {$month}",
                                    'amount' => $nominal,
                                    'incentive_date' => $monthEnd->format('Y-m-d'),
                                    'status' => 'pending',
                                ]
                            );
                            $syncedRewardsCount++;
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
                                $syncedRewardsCount++;
                            }
                        }
                    }
                }
            }
        });

        $rewardMsg = $syncedRewardsCount > 0 ? " & {$syncedRewardsCount} reward insentif" : ($isMonthCompleted ? "" : " (Reward absen terbaik dievaluasi setelah 1 bulan selesai)");
        return back()->with('success', "Berhasil menyinkronkan data presensi & jadwal kerja ke Penggajian untuk periode {$month}: {$syncedDeductionsCount} potongan otomatis{$rewardMsg}!");
    }

    /**
     * Perbarui Koordinat & Radius Kantor Cabang (Geofence Setting)
     */
    public function updateAreaLocation(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('attendances_geofence_edit'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah titik koordinat & radius geofence.');
        }

        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meters' => 'required|integer|min:20|max:5000',
        ]);

        $area = Area::findOrFail($request->area_id);
        $area->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
        ]);

        return back()->with('success', "Titik koordinat & radius kantor '{$area->name}' berhasil diperbarui (Radius: {$request->radius_meters}m).");
    }

    /**
     * Helper simpan base64 string ke file storage
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
            throw new \Exception('Gagal mendekode gambar selfie.');
        }

        $filePath = "{$folder}/{$filename}_" . time() . ".{$type}";
        Storage::disk('public')->put($filePath, $decoded);

        return $filePath;
    }

    /**
     * Hitung Jarak dalam Meter Menggunakan Rumus Haversine
     */
    protected function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // Radius bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }

    /**
     * Resolusi shift default / standar berdasarkan divisi & jabatan karyawan
     */
    public function resolveDefaultShiftForEmployee(?Employee $employee): ?Shift
    {
        if (!$employee) {
            return Shift::where('is_active', true)->orderBy('start_time')->first();
        }

        if ($employee->shift_id) {
            $shift = Shift::find($employee->shift_id);
            if ($shift) return $shift;
        }

        $pos = strtolower(trim($employee->position ?? ''));

        // CS -> Shift 1-CS
        if (str_contains($pos, 'cs') || str_contains($pos, 'customer service')) {
            $shift = Shift::where('name', 'like', '%Shift 1-CS%')->orWhere('name', 'like', '%CS%')->first();
            if ($shift) return $shift;
        }

        // Teknisi, Maintenance, Kolektor, Infrastruktur -> Shift 1-Teknisi&Kolektor
        if (str_contains($pos, 'teknisi') || str_contains($pos, 'maintenance') || str_contains($pos, 'kolektor') || str_contains($pos, 'infra')) {
            $shift = Shift::where('name', 'like', '%Teknisi%')->first();
            if ($shift) return $shift;
        }

        // Admin, Finance, Kasir, Manager, NOC -> Shift 1/Admin
        if (str_contains($pos, 'admin') || str_contains($pos, 'finance') || str_contains($pos, 'manager') || str_contains($pos, 'noc') || str_contains($pos, 'kasir')) {
            $shift = Shift::where('name', 'like', '%Admin%')->first();
            if ($shift) return $shift;
        }

        // Fallback ke shift aktif pertama
        return Shift::where('is_active', true)->orderBy('start_time')->first();
    }

    /**
     * Hapus satu data presensi (Khusus HRD, Finance, Admin)
     */
    public function destroy(Attendance $attendance)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $userRole = strtolower($user->role ?? '');
        $isAllowed = in_array($userRole, ['admin', 'hrd', 'finance']) || $user->can('attendance_report_btn_delete') || $user->can('attendances_manage');

        if (!$isAllowed) {
            abort(403, 'Akses ditolak. Tindakan hapus absensi hanya dapat dilakukan oleh akun HRD atau Finance.');
        }

        $empName = $attendance->employee?->name ?? 'Karyawan';
        $date = $attendance->date;
        $attendance->delete();

        return redirect()->back()->with('success', "Data absensi {$empName} tanggal {$date} berhasil dihapus.");
    }
}

