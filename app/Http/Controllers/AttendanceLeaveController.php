<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLeave;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\WorkSchedule;
use App\Services\AttendanceCalculatorService;
use App\Services\ImageCompressionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AttendanceLeaveController extends Controller
{
    /**
     * Tampilkan halaman daftar izin, sakit, dan alpha (Khusus HRD & Finance)
     */
    public function index(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $type = $request->input('type', 'all');
        $search = trim($request->input('search', ''));

        $query = AttendanceLeave::with(['employee.area', 'recordedBy'])
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        if ($type !== 'all' && in_array($type, ['sick_with_cert', 'sick_no_cert', 'permit', 'alpha', 'leave'])) {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('iak_number', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $leaves = $query->orderBy('date', 'desc')->paginate(20)->withQueryString();

        // Statistik Cepat Bulan Ini
        $allMonthLeaves = AttendanceLeave::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $stats = [
            'total_records' => $allMonthLeaves->count(),
            'sick_with_cert' => $allMonthLeaves->where('type', 'sick_with_cert')->count(),
            'sick_no_cert' => $allMonthLeaves->where('type', 'sick_no_cert')->count(),
            'permit' => $allMonthLeaves->where('type', 'permit')->count(),
            'alpha' => $allMonthLeaves->where('type', 'alpha')->count(),
            'leave' => $allMonthLeaves->where('type', 'leave')->count(),
            'total_penalty' => (float) $allMonthLeaves->sum('penalty_amount'),
        ];

        // Daftar Karyawan Aktif untuk pilihan input
        $employees = Employee::excludeTokoAndDemo()->orderBy('name', 'asc')
            ->get(['id', 'name', 'iak_number', 'position', 'branch', 'area_id']);

        // Default setting nominal denda dari Master Settings
        $defaultSettings = [
            'sick_no_cert_amount' => (float) Setting::get('attendance_sick_no_cert_amount', 20000),
            'permit_amount' => (float) Setting::get('attendance_permit_amount', 25000),
            'alpha_amount' => (float) Setting::get('attendance_alpha_amount', 35000),
        ];

        return Inertia::render('Attendances/Leaves', [
            'leaves' => $leaves,
            'stats' => $stats,
            'employees' => $employees,
            'defaultSettings' => $defaultSettings,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'type' => $type,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Simpan input izin, sakit, atau alpha manual baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'required|in:sick_with_cert,sick_no_cert,permit,alpha,leave',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:5120',
            'penalty_amount' => 'nullable|numeric|min:0',
        ], [
            'employee_id.required' => 'Pilih karyawan terlebih dahulu.',
            'employee_id.exists' => 'Karyawan yang dipilih tidak terdaftar.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'type.required' => 'Pilih jenis ketidakhadiran.',
            'reason.required' => 'Keterangan atau alasan wajib diisi.',
            'attachment.max' => 'Ukuran berkas surat dokter / bukti maksimal 5MB.',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate->copy();

        // Hitung nominal penalti harian
        $dailyPenalty = match ($validated['type']) {
            'sick_with_cert', 'leave' => 0,
            'sick_no_cert' => $request->has('penalty_amount') && $request->penalty_amount !== null
                ? (float) $request->penalty_amount
                : (float) Setting::get('attendance_sick_no_cert_amount', 20000),
            'permit' => $request->has('penalty_amount') && $request->penalty_amount !== null
                ? (float) $request->penalty_amount
                : (float) Setting::get('attendance_permit_amount', 25000),
            'alpha' => $request->has('penalty_amount') && $request->penalty_amount !== null
                ? (float) $request->penalty_amount
                : (float) Setting::get('attendance_alpha_amount', 35000),
            default => 0,
        };

        // Upload attachment foto surat dokter jika ada
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = ImageCompressionService::compressAndStore(
                $request->file('attachment'),
                'attendance_leaves'
            );
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $totalDays = 0;

        DB::beginTransaction();
        try {
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $totalDays++;

                // 1. Tentukan schedule status & label notes
                $schedStatus = match ($validated['type']) {
                    'sick_with_cert' => 'sick',
                    'sick_no_cert' => 'sick_no_cert',
                    'permit' => 'permit',
                    'alpha' => 'alpha',
                    'leave' => 'leave',
                    default => 'off',
                };

                $labelPrefix = match ($validated['type']) {
                    'sick_with_cert' => '[Sakit Surat Dokter]',
                    'sick_no_cert' => '[Sakit Tanpa Surat]',
                    'permit' => '[Izin Tidak Masuk]',
                    'alpha' => '[Alpha / Mangkir]',
                    'leave' => '[Cuti]',
                    default => '[Izin]',
                };

                $notesFormatted = trim($labelPrefix . ' ' . $validated['reason']);

                // 2. Sinkronisasi ke WorkSchedule (Jadwal Kerja / Roster)
                $schedule = WorkSchedule::firstOrNew([
                    'employee_id' => $employee->id,
                    'date' => $dateStr,
                ]);
                $schedule->status = $schedStatus;
                $schedule->notes = $notesFormatted;
                $schedule->save();

                // 3. Simpan atau perbarui AttendanceLeave
                $leave = AttendanceLeave::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $dateStr,
                    ],
                    [
                        'type' => $validated['type'],
                        'reason' => $validated['reason'],
                        'attachment' => $attachmentPath ?: null,
                        'penalty_amount' => $dailyPenalty,
                        'recorded_by' => auth()->id(),
                        'work_schedule_id' => $schedule->id,
                    ]
                );

                // 4. Sinkronisasi ke Presensi Harian (Attendance)
                $attendance = Attendance::firstOrNew([
                    'employee_id' => $employee->id,
                    'date' => $dateStr,
                ]);

                // Jangan timpa clock_in jika karyawan ternyata sempat hadir
                if (!$attendance->clock_in) {
                    $attendance->work_schedule_id = $schedule->id;
                    $attendance->status = ($validated['type'] === 'alpha') ? 'absent' : 'excused';
                    $attendance->notes = $notesFormatted;
                    if ($attachmentPath) {
                        $attendance->photo_in = $attachmentPath;
                    }
                    $attendance->save();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            return back()->with('error', 'Gagal menyimpan data ketidakhadiran: ' . $e->getMessage());
        }

        // Sinkronisasi otomatis ke Payroll untuk bulan terkait
        try {
            $monthStr = $startDate->format('Y-m');
            AttendanceCalculatorService::syncMonthlyToPayroll($monthStr);
        } catch (\Throwable $ignored) {}

        $typeName = AttendanceLeave::getTypeName($validated['type']);
        return back()->with('success', "Berhasil mencatat {$typeName} untuk {$employee->name} sebanyak {$totalDays} hari kerja.");
    }

    /**
     * Hapus catatan ketidakhadiran dan pulihkan jadwal serta presensi
     */
    public function destroy(AttendanceLeave $attendanceLeave)
    {
        $employee = $attendanceLeave->employee;
        $dateStr = $attendanceLeave->date->format('Y-m-d');
        $monthStr = $attendanceLeave->date->format('Y-m');

        DB::beginTransaction();
        try {
            // Hapus file attachment jika ada
            if ($attendanceLeave->attachment) {
                Storage::disk('public')->delete($attendanceLeave->attachment);
            }

            // Pulihkan WorkSchedule menjadi work jika sebelumnya terikat
            if ($attendanceLeave->work_schedule_id) {
                $sched = WorkSchedule::find($attendanceLeave->work_schedule_id);
                if ($sched) {
                    $sched->update([
                        'status' => 'work',
                        'notes' => null,
                    ]);
                }
            } else {
                $sched = WorkSchedule::where('employee_id', $attendanceLeave->employee_id)
                    ->where('date', $dateStr)
                    ->first();
                if ($sched) {
                    $sched->update([
                        'status' => 'work',
                        'notes' => null,
                    ]);
                }
            }

            // Hapus rekaman presensi jika hanya berupa dispensasi otomatis (belum ada absen masuk riil)
            $att = Attendance::where('employee_id', $attendanceLeave->employee_id)
                ->where('date', $dateStr)
                ->first();
            if ($att && empty($att->clock_in)) {
                $att->delete();
            }

            $attendanceLeave->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }

        // Sinkronisasi ulang Payroll
        try {
            AttendanceCalculatorService::syncMonthlyToPayroll($monthStr);
        } catch (\Throwable $ignored) {}

        return back()->with('success', "Catatan ketidakhadiran {$employee->name} pada tanggal {$dateStr} berhasil dihapus dan jadwal dipulihkan.");
    }

    /**
     * Sinkronisasi paksa potongan izin/sakit/alpha ke payroll bulan terpilih
     */
    public function syncPayroll(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $monthStr = sprintf('%04d-%02d', $year, $month);

        try {
            $syncedCount = AttendanceCalculatorService::syncMonthlyToPayroll($monthStr);
            return back()->with('success', "Sinkronisasi potongan presensi ke Payroll periode {$monthStr} berhasil diperbarui ({$syncedCount} karyawan diproses).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyinkronkan ke payroll: ' . $e->getMessage());
        }
    }
}
