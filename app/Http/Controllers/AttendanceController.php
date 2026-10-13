<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $employeeQuery = Employee::with(['area', 'shift']);
        
        // Cari employee yang terhubung dengan akun user login
        $currentEmployee = null;
        if ($user) {
            $currentEmployee = Employee::with(['area', 'shift'])->where('email', $user->email)->first();
        }

        // Jika user adalah admin dan memilih employee lain di dropdown switch
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

        $allEmployees = Employee::orderBy('name')->get(['id', 'name', 'position', 'area_id']);

        // 2. Ambil Jadwal Shift Hari Ini untuk Karyawan Aktif
        $todaySchedule = null;
        $shiftToday = null;
        if ($currentEmployee) {
            $todaySchedule = WorkSchedule::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();

            $shiftToday = $todaySchedule?->shift ?: $currentEmployee->shift;
        }

        // 3. Ambil Data Absensi Hari Ini untuk Karyawan Aktif
        $todayAttendance = null;
        if ($currentEmployee) {
            $todayAttendance = Attendance::with('shift')
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();
        }

        // 4. Jadwal Shift Minggu Ini (Senin s/d Minggu)
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

            for ($i = 0; $i < 7; $i++) {
                $dayObj = $startOfWeek->copy()->addDays($i);
                $dateStr = $dayObj->format('Y-m-d');
                $sched = $weekSchedules->get($dateStr);

                $shiftLabel = '-';
                $shiftColor = 'gray';
                if ($sched) {
                    if ($sched->status === 'off' || empty($sched->shift_id)) {
                        $shiftLabel = 'OFF';
                        $shiftColor = 'rose';
                    } else {
                        $shiftLabel = $sched->shift?->name ?: 'Shift';
                        $shiftColor = $sched->shift?->color ?: 'blue';
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
                    'shift_time' => $sched?->shift?->formatted_time ?? '',
                ];
            }
        }

        // 5. Informasi Area & Koordinat Kantor
        $officeLocation = null;
        if ($currentEmployee && $currentEmployee->area) {
            $officeLocation = [
                'name' => $currentEmployee->area->name,
                'address' => $currentEmployee->area->address,
                'latitude' => $currentEmployee->area->latitude ? (float) $currentEmployee->area->latitude : null,
                'longitude' => $currentEmployee->area->longitude ? (float) $currentEmployee->area->longitude : null,
                'radius_meters' => 200, // Radius toleransi 200 meter
            ];
        }

        // 6. Data Monitoring Kehadiran Seluruh Karyawan Hari Ini (Admin Tab)
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

        $areas = Area::orderBy('name')->get(['id', 'name']);
        $positions = Position::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Attendances/Index', [
            'todayDateStr' => $now->translatedFormat('l, d/m/Y'),
            'todayIso' => $today,
            'currentEmployee' => $currentEmployee,
            'allEmployees' => $allEmployees,
            'todaySchedule' => $todaySchedule,
            'shiftToday' => $shiftToday,
            'todayAttendance' => $todayAttendance,
            'weeklySchedules' => $weeklySchedules,
            'officeLocation' => $officeLocation,
            'monitoring' => [
                'attendances' => $todayAllAttendances,
                'totalEmployees' => $totalEmployeesCount,
                'presentCount' => $presentCount,
                'lateCount' => $lateCount,
                'absentCount' => max(0, $totalEmployeesCount - $presentCount),
            ],
            'areas' => $areas,
            'positions' => $positions,
            'filters' => [
                'area_id' => $request->area_id ?? '',
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
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance' => 'nullable|integer',
            'notes' => 'nullable|string',
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

        // Ambil jadwal shift hari ini
        $schedule = WorkSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $shift = $schedule?->shift ?: $employee->shift;

        // Hitung Keterlambatan
        $status = 'present';
        $lateMinutes = 0;

        if ($shift && $shift->start_time) {
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
                'clock_in' => $timeStr,
                'photo_in' => $photoPath,
                'lat_in' => $request->latitude,
                'lng_in' => $request->longitude,
                'distance_in' => $request->distance,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'notes' => $request->notes,
            ]
        );

        $msg = $status === 'late'
            ? "Absen masuk berhasil tercatat pada {$now->format('H:i')} (Terlambat {$lateMinutes} menit)."
            : "Absen masuk berhasil tercatat pada {$now->format('H:i')}. Semangat bekerja!";

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
            'notes' => 'nullable|string',
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

        return back()->with('success', "Absen pulang berhasil tercatat pada {$now->format('H:i')}. Selamat beristirahat!");
    }

    /**
     * Helper simpan base64 string ke file storage
     */
    private function saveBase64Photo(string $base64Data, string $folder, string $filename): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc.
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
