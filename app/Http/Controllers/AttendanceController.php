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
use App\Models\User;
use App\Models\WorkSchedule;
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
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // 1. Tentukan Karyawan Aktif
        $currentEmployee = null;
        if ($user) {
            $currentEmployee = Employee::with(['area', 'shift'])->where('email', $user->email)->first();
        }

        // Jika ada filter / switcher employee_id (untuk admin)
        if ($request->filled('employee_id')) {
            $selected = Employee::with(['area', 'shift'])->find($request->employee_id);
            if ($selected) {
                $currentEmployee = $selected;
            }
        }

        // Fallback jika belum ada employee sama sekali untuk user ini
        if (!$currentEmployee) {
            $currentEmployee = Employee::with(['area', 'shift'])->first();
        }

        $allEmployees = Employee::with(['area', 'shift'])
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'branch', 'area_id', 'shift_id', 'email', 'iak_number']);

        // 2. Ambil Master Shifts & Areas
        $allShifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        $areas = Area::orderBy('name')->get();
        $positions = Position::orderBy('name')->get(['id', 'name']);

        // 3. Ambil Jadwal Shift Hari Ini untuk Karyawan Aktif
        $todaySchedule = null;
        $shiftToday = null;
        if ($currentEmployee) {
            $todaySchedule = WorkSchedule::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();

            if ($todaySchedule && ($todaySchedule->status === 'off' || empty($todaySchedule->shift_id))) {
                $shiftToday = null;
            } else {
                $shiftToday = $todaySchedule?->shift ?: $currentEmployee->shift;
            }
        }

        // 4. Ambil Data Absensi Hari Ini untuk Karyawan Aktif
        $todayAttendance = null;
        if ($currentEmployee) {
            $todayAttendance = Attendance::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();
        }

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

            for ($i = 0; $i < 7; $i++) {
                $dayObj = $startOfWeek->copy()->addDays($i);
                $dateStr = $dayObj->format('Y-m-d');
                $sched = $weekSchedules->get($dateStr);
                $att = $weekAttendances->get($dateStr);

                $shiftLabel = '-';
                $shiftColor = 'gray';
                if ($sched) {
                    if ($sched->status === 'off' || empty($sched->shift_id)) {
                        $shiftLabel = 'LIBUR';
                        $shiftColor = 'rose';
                    } else {
                        $shiftLabel = $sched->shift?->name ?: 'Shift';
                        $shiftColor = $sched->shift?->color ?: 'blue';
                    }
                } elseif ($currentEmployee->shift) {
                    $shiftLabel = $currentEmployee->shift->name;
                    $shiftColor = $currentEmployee->shift->color ?: 'blue';
                }

                $weeklySchedules[] = [
                    'date' => $dateStr,
                    'day_name' => $dayNamesIndo[$dayObj->dayOfWeek],
                    'day_number' => (int) $dayObj->format('d'),
                    'is_today' => $dayObj->isToday(),
                    'is_sunday' => $dayObj->dayOfWeek === 0,
                    'shift_label' => $shiftLabel,
                    'shift_color' => $shiftColor,
                    'shift_time' => $sched?->shift?->formatted_time ?? ($currentEmployee->shift?->formatted_time ?? ''),
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
        $monitoringQuery = Attendance::with(['employee.area', 'shift'])->where('date', $today);
        if ($request->filled('area_id')) {
            $monitoringQuery->whereHas('employee', function ($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }
        $todayAllAttendances = $monitoringQuery->latest()->get();

        $totalEmployeesCount = Employee::count();
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

        return Inertia::render('Attendances/Index', [
            'todayDateStr' => $now->translatedFormat('l, d F Y'),
            'todayIso' => $today,
            'currentEmployee' => $currentEmployee,
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
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'photo' => 'required|string', // base64 photo
            'shift_id' => 'nullable|exists:shifts,id',
            'attendance_type' => 'nullable|string|in:wfo,dinas_luar,wfh,lembur',
            'duty_location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = Employee::with(['area', 'shift'])->findOrFail($request->employee_id);
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        $timeStr = $now->format('H:i:s');

        // Cek apakah sudah absen masuk
        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $today)->first();
        if ($attendance && $attendance->clock_in) {
            return back()->with('error', 'Anda sudah melakukan absen masuk hari ini.');
        }

        // Ambil jadwal shift hari ini atau gunakan shift yang dipilih
        $schedule = WorkSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $shiftId = $request->shift_id ?: ($schedule?->shift_id ?: $employee->shift_id);
        $shift = $shiftId ? Shift::find($shiftId) : null;

        // Auto-sinkronisasi ke Jadwal Kerja / Roster jika belum ada jadwal hari ini
        if (!$schedule && $shift) {
            $schedule = WorkSchedule::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $today,
                ],
                [
                    'shift_id' => $shift->id,
                    'status' => 'scheduled',
                    'notes' => 'Sinkronisasi otomatis saat presensi masuk',
                ]
            );
        }

        // Hitung Keterlambatan
        $status = 'present';
        $lateMinutes = 0;
        $attendanceType = $request->attendance_type ?: 'wfo';

        if ($shift && $shift->start_time && $attendanceType !== 'dinas_luar') {
            $shiftStartTime = Carbon::createFromFormat('Y-m-d H:i', $today . ' ' . substr($shift->start_time, 0, 5));
            // Toleransi 5 menit keterlambatan
            if ($now->greaterThan($shiftStartTime->copy()->addMinutes(5))) {
                $status = 'late';
                $lateMinutes = $now->diffInMinutes($shiftStartTime);
            }
        }

        // Simpan Foto Selfie Base64
        $photoPath = $this->saveBase64Photo($request->photo, 'attendances/in', "in_{$employee->id}_{$today}");

        Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $today,
            ],
            [
                'work_schedule_id' => $schedule?->id,
                'shift_id' => $shift?->id,
                'attendance_type' => $attendanceType,
                'clock_in' => $timeStr,
                'photo_in' => $photoPath,
                'lat_in' => $request->latitude,
                'lng_in' => $request->longitude,
                'distance_in' => $request->distance,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'notes' => $request->notes,
                'duty_location' => $request->duty_location,
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
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'photo' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        $timeStr = $now->format('H:i:s');

        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $today)->first();

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', 'Anda belum melakukan absen masuk hari ini.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Anda sudah melakukan absen pulang hari ini.');
        }

        $photoPath = $this->saveBase64Photo($request->photo, 'attendances/out', "out_{$employee->id}_{$today}");

        $attendance->update([
            'clock_out' => $timeStr,
            'photo_out' => $photoPath,
            'lat_out' => $request->latitude,
            'lng_out' => $request->longitude,
            'distance_out' => $request->distance,
            'notes' => $request->notes ?: $attendance->notes,
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
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $today = Carbon::today()->format('Y-m-d');
        $shift = Shift::findOrFail($request->shift_id);

        WorkSchedule::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date' => $today,
            ],
            [
                'shift_id' => $shift->id,
                'status' => 'scheduled',
                'notes' => 'Ditetapkan melalui menu absensi cepat',
            ]
        );

        return back()->with('success', "Shift {$shift->name} ({$shift->formatted_time}) berhasil diterapkan untuk hari ini.");
    }

    /**
     * Sinkronisasi Potongan Keterlambatan ke Modul Penggajian (Payroll)
     */
    public function syncToPayroll(Request $request)
    {
        $request->validate([
            'month' => 'required|string', // Format: YYYY-MM
            'penalty_per_minute' => 'nullable|numeric|min:0', // misal Rp 1.000 / menit
            'fixed_penalty_per_late' => 'nullable|numeric|min:0', // misal Rp 20.000 / kejadian
        ]);

        $month = $request->month;
        $penaltyPerMinute = (float) ($request->penalty_per_minute ?? 1000);
        $fixedPenalty = (float) ($request->fixed_penalty_per_late ?? 0);

        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth();

        // Ambil data absensi terlambat yang belum disinkronkan
        $lateAttendances = Attendance::with('employee')
            ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->where('status', 'late')
            ->where('late_minutes', '>', 0)
            ->get();

        if ($lateAttendances->isEmpty()) {
            return back()->with('info', "Tidak ada catatan keterlambatan untuk periode {$month}.");
        }

        $grouped = $lateAttendances->groupBy('employee_id');
        $syncedCount = 0;

        DB::transaction(function () use ($grouped, $penaltyPerMinute, $fixedPenalty, $month, $monthEnd, &$syncedCount) {
            foreach ($grouped as $employeeId => $atts) {
                $employee = $atts->first()->employee;
                if (!$employee || !$employee->email) {
                    continue;
                }

                // Cari User yang memiliki email sama dengan employee
                $user = User::where('email', $employee->email)->first();
                if (!$user) {
                    continue;
                }

                $totalMinutes = $atts->sum('late_minutes');
                $lateCount = $atts->count();

                // Hitung total nominal potongan
                $totalDeductionAmount = ($totalMinutes * $penaltyPerMinute) + ($lateCount * $fixedPenalty);

                if ($totalDeductionAmount > 0) {
                    $desc = "Potongan Terlambat Absensi Periode {$month} ({$lateCount}x telat, total {$totalMinutes} menit)";
                    
                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'type' => 'auto',
                            'description' => $desc,
                        ],
                        [
                            'amount' => $totalDeductionAmount,
                            'deduction_date' => $monthEnd->format('Y-m-d'),
                            'status' => 'pending',
                        ]
                    );

                    // Tandai absensi telah disinkronkan
                    Attendance::whereIn('id', $atts->pluck('id'))->update(['is_synced_to_payroll' => true]);
                    $syncedCount++;
                }
            }

            // PROSES REWARD ABSEN TERBAIK JIKA DIAKTIFKAN
            if (Setting::get('attendance_reward_enabled', '1') === '1') {
                $allMonthAtts = Attendance::with('employee')
                    ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->get()
                    ->groupBy('employee_id');

                // Hitung Skor & Disiplin: Hadir banyak, late_minutes paling kecil
                $performance = [];
                foreach ($allMonthAtts as $empId => $atts) {
                    $emp = $atts->first()->employee;
                    if (!$emp || !$emp->email) continue;
                    $usr = User::where('email', $emp->email)->first();
                    if (!$usr) continue;

                    $presents = $atts->whereNotNull('clock_in')->count();
                    $lates = $atts->where('status', 'late')->count();
                    $lateMins = $atts->sum('late_minutes');

                    if ($presents > 0) {
                        $performance[] = [
                            'user' => $usr,
                            'employee' => $emp,
                            'area_id' => $emp->area_id,
                            'presents' => $presents,
                            'late_minutes' => $lateMins,
                            'score' => ($presents * 100) - ($lateMins * 2),
                        ];
                    }
                }

                // Reward Per Cabang
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
                            }
                        }
                    }
                }

                // Reward Global (Top 3 Perusahaan)
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
                        }
                    }
                }

                // Reward Maintenance (Top 3 Global)
                if (Setting::get('attendance_reward_maintenance_enabled', '1') === '1') {
                    $maintNominals = [
                        1 => (float) Setting::get('attendance_reward_maint_1', 200000),
                        2 => (float) Setting::get('attendance_reward_maint_2', 150000),
                        3 => (float) Setting::get('attendance_reward_maint_3', 100000),
                    ];

                    $maintSorted = collect($performance)->filter(function ($item) {
                        $pos = strtolower($item['employee']->position ?? '');
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
                        }
                    }
                }

                // Reward Tim Khusus (Top 3 per Grup)
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
                            }
                        }
                    }
                }
            }
        });

        return back()->with('success', "Berhasil menyinkronkan data potongan & reward absen terbaik ke modul Penggajian (Payroll)!");
    }

    /**
     * Perbarui Koordinat & Radius Kantor Cabang (Geofence Setting)
     */
    public function updateAreaLocation(Request $request)
    {
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
}
