<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    /**
     * Tampilkan halaman utama Laporan Absensi & Kedisiplinan Karyawan
     */
    public function index(Request $request): Response
    {
        $data = $this->buildReportData($request);
        $userRole = strtolower(auth()->user()?->role ?? '');
        $data['canDelete'] = in_array($userRole, ['admin', 'hrd', 'finance']) || (bool) auth()->user()?->can('attendance_report_btn_delete');
        $data['canSyncPayroll'] = in_array($userRole, ['admin', 'hrd', 'finance']) || (bool) auth()->user()?->can('attendances_sync_payroll');
        return Inertia::render('Attendances/Report', $data);
    }

    /**
     * Export data rekapitulasi atau log rinci ke format CSV/Excel
     */
    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildReportData($request, false);
        $tab = $request->get('tab', 'recap');
        $periodLabel = $data['filterPeriodLabel'];
        $filename = "Laporan_Absensi_{$tab}_" . str_replace([' ', '/', '-'], '_', $periodLabel) . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($data, $tab) {
            $handle = fopen('php://output', 'w');
            // Tambahkan UTF-8 BOM agar Excel membaca karakter & simbol dengan sempurna
            fputs($handle, "\xEF\xBB\xBF");

            if ($tab === 'detail') {
                // Header CSV Detail Log Harian
                fputcsv($handle, [
                    'No',
                    'Tanggal',
                    'Nama Karyawan',
                    'IAK / NIK',
                    'Jabatan',
                    'Wilayah / Cabang',
                    'Shift Kerja',
                    'Jadwal Shift',
                    'Jam Masuk',
                    'Jam Pulang',
                    'Status',
                    'Terlambat (Menit)',
                    'Tipe Presensi',
                    'Jarak GPS Masuk (m)',
                    'Lokasi Tugas / Keterangan',
                ]);

                $logs = $data['detailLogs'];
                foreach ($logs as $i => $row) {
                    fputcsv($handle, [
                        $i + 1,
                        $row['date'],
                        $row['employee_name'],
                        $row['iak_number'] ?: '-',
                        $row['position'] ?: '-',
                        $row['area_name'] ?: '-',
                        $row['shift_name'] ?: '-',
                        $row['shift_time'] ?: '-',
                        $row['clock_in'] ?: '-',
                        $row['clock_out'] ?: '-',
                        $row['status_label'],
                        $row['late_minutes'] > 0 ? $row['late_minutes'] : 0,
                        $row['attendance_type_label'],
                        $row['distance_in'] !== null ? $row['distance_in'] . 'm' : '-',
                        $row['notes'] ?: ($row['duty_location'] ?: '-'),
                    ]);
                }
            } else {
                // Header CSV Rekapitulasi per Karyawan (Cerdas & Lengkap)
                fputcsv($handle, [
                    'No',
                    'Nama Karyawan',
                    'IAK / NIK',
                    'Jabatan',
                    'Wilayah / Cabang',
                    'Shift Default',
                    'Total Hari Kerja',
                    'Hari Kerja Berjalan',
                    'Total Hadir (Hari)',
                    'Tepat Waktu (Hari)',
                    'Terlambat (Kali)',
                    'Total Menit Terlambat',
                    'Tugas Luar / Dinas (Hari)',
                    'Tanpa Clock-Out (Kali)',
                    'Pulang Cepat (Kali)',
                    'Alpha / Tidak Hadir (Hari)',
                    'Rasio Kehadiran (%)',
                    'Tingkat Disiplin (%)',
                    'Estimasi Potongan (Rp)',
                    'Status Payroll',
                ]);

                $recap = $data['employeeRecap'];
                foreach ($recap as $i => $emp) {
                    fputcsv($handle, [
                        $i + 1,
                        $emp['name'],
                        $emp['iak_number'] ?: '-',
                        $emp['position'] ?: '-',
                        $emp['area_name'] ?: '-',
                        $emp['shift_name'] ?: '-',
                        $emp['total_work_days'] . ' Hari',
                        $emp['elapsed_work_days'] . ' Hari',
                        $emp['total_present'],
                        $emp['total_on_time'],
                        $emp['total_late'],
                        $emp['total_late_minutes'],
                        $emp['total_field_duty'],
                        $emp['total_missing_out'],
                        $emp['total_early_leave'] ?? 0,
                        $emp['total_alpha'],
                        $emp['attendance_rate'] . '%',
                        $emp['discipline_rate'] . '%',
                        $emp['estimated_penalty'],
                        $emp['is_all_synced'] ? 'Sudah Sinkron' : 'Belum Sinkron',
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Tampilan cetak PDF / print-out siap cetak A4
     */
    public function print(Request $request)
    {
        $data = $this->buildReportData($request, false);
        return view('print.attendance-report', $data);
    }

    /**
     * Sinkronkan hasil kalkulasi presensi bulanan otomatis ke modul Penggajian (Payroll)
     */
    public function syncToPayroll(Request $request)
    {
        $user = auth()->user();
        $userRole = strtolower($user?->role ?? '');
        $isAllowed = in_array($userRole, ['admin', 'hrd', 'finance']) 
            || (bool) $user?->can('attendances_sync_payroll') 
            || (bool) $user?->can('attendance_report_btn_delete');

        if (!$isAllowed) {
            abort(403, 'Akses ditolak. Tindakan sinkronisasi presensi ke penggajian hanya diizinkan untuk akun HRD atau Finance.');
        }

        $month = $request->input('month', now('Asia/Jakarta')->format('Y-m'));
        $result = AttendanceCalculatorService::syncMonthlyToPayroll($month);

        return redirect()->back()->with('success', "Presensi periode {$result['period']} ({$month}) berhasil disinkronkan ke Penggajian: {$result['synced_deductions_count']} pemotongan diproses untuk {$result['affected_employees_count']} karyawan.");
    }

    /**
     * Helper agregasi data laporan absensi secara cerdas & otomatis
     */
    protected function buildReportData(Request $request, bool $paginateDetail = true): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $periodMode = $request->get('period_mode', 'month'); // 'month' atau 'custom'
        $selectedMonth = $request->get('month', $today->format('Y-m'));

        // Dapatkan detail cerdas awal s/d akhir bulan
        $periodDetails = AttendanceCalculatorService::getMonthPeriodDetails($selectedMonth);

        if ($periodMode === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date, 'Asia/Jakarta')->startOfDay();
            $endDate = Carbon::parse($request->end_date, 'Asia/Jakarta')->endOfDay();
            $filterPeriodLabel = $startDate->translatedFormat('d M Y') . ' s/d ' . $endDate->translatedFormat('d M Y');
        } else {
            $startDate = $periodDetails['start_datetime'];
            $endDate = $periodDetails['end_datetime'];
            $filterPeriodLabel = $periodDetails['period_label'];
        }

        // Scope Area User jika ada restriksi
        $isAreaRestricted = auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('attendances_view_all');
        $userAreaIds = auth()->check() ? auth()->user()->getAccessibleAreaIds() : [];

        // 1. Data Area & Karyawan untuk Filter Dropdown
        $areasQuery = Area::select('id', 'name')->orderBy('name');
        if ($isAreaRestricted && !empty($userAreaIds)) {
            $areasQuery->whereIn('id', $userAreaIds);
        }
        $areas = $areasQuery->get();

        $employeesQuery = Employee::with('area:id,name', 'shift:id,name')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->orderBy('name');

        if ($isAreaRestricted && !empty($userAreaIds)) {
            $employeesQuery->whereIn('area_id', $userAreaIds);
        }

        if ($request->filled('area_id')) {
            $employeesQuery->where('area_id', $request->area_id);
        }

        $allEmployees = $employeesQuery->get();

        // 2. Query Utama Data Absensi dalam Periode Awal s/d Akhir Bulan
        $attQuery = Attendance::with([
            'employee' => function ($q) {
                $q->select('id', 'name', 'iak_number', 'position', 'area_id', 'shift_id', 'email', 'status');
            },
            'employee.area:id,name',
            'shift:id,name,start_time,end_time',
            'area:id,name',
        ])
        ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($isAreaRestricted && !empty($userAreaIds)) {
            $attQuery->where(function ($q) use ($userAreaIds) {
                $q->whereIn('area_id', $userAreaIds)
                  ->orWhereHas('employee', fn($eq) => $eq->whereIn('area_id', $userAreaIds));
            });
        }

        if ($request->filled('area_id')) {
            $areaId = $request->area_id;
            $attQuery->where(function ($q) use ($areaId) {
                $q->where('area_id', $areaId)
                  ->orWhereHas('employee', fn($eq) => $eq->where('area_id', $areaId));
            });
        }

        if ($request->filled('employee_id')) {
            $attQuery->where('employee_id', $request->employee_id);
        }

        if ($request->filled('attendance_type') && $request->attendance_type !== 'all') {
            $attQuery->where('attendance_type', $request->attendance_type);
        }

        // Ambil semua records absensi periode untuk rekap
        $allAttendancesInPeriod = (clone $attQuery)->get();

        // Ambil data jadwal kerja jika ada
        $workSchedules = WorkSchedule::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', 'work')
            ->get();

        // 3. Rekapitulasi per Karyawan (Cerdas & Otomatis)
        $employeeRecap = AttendanceCalculatorService::calculateMonthlyRecap(
            $allEmployees,
            $allAttendancesInPeriod,
            $periodDetails,
            null,
            $workSchedules
        );

        // Filter search jika ada pencarian teks
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $employeeRecap = $employeeRecap->filter(function ($r) use ($search) {
                return str_contains(strtolower($r['name'] ?? ''), $search)
                    || str_contains(strtolower($r['iak_number'] ?? ''), $search);
            })->values();

            $attQuery->whereHas('employee', function ($eq) use ($search) {
                $eq->where('name', 'like', "%{$search}%")
                   ->orWhere('iak_number', 'like', "%{$search}%");
            });
        }

        // 4. Kalkulasi Summary Cards (Statistik Global Periode)
        $totalLogs = $allAttendancesInPeriod->count();
        $totalPresentCount = $employeeRecap->sum('total_present');
        $totalLateCount = $employeeRecap->sum('total_late');
        $totalLateMinutesCount = $employeeRecap->sum('total_late_minutes');
        $totalFieldDutyCount = $employeeRecap->sum('total_field_duty');
        $totalMissingOutCount = $employeeRecap->sum('total_missing_out');
        $totalAlphaCount = $employeeRecap->sum('total_alpha');
        $totalOnTimeCount = $employeeRecap->sum('total_on_time');
        $totalPenaltySum = $employeeRecap->sum('estimated_penalty');
        $avgAttendance = $employeeRecap->avg('attendance_rate') ?: 0;
        $avgDiscipline = $employeeRecap->where('total_present', '>', 0)->avg('discipline_rate') ?: 0;

        $stats = [
            'total_employees' => $allEmployees->count(),
            'total_logs' => $totalLogs,
            'total_calendar_days' => $periodDetails['total_calendar_days'],
            'total_work_days' => $periodDetails['total_work_days'],
            'elapsed_work_days' => $periodDetails['elapsed_work_days'],
            'total_present' => $totalPresentCount,
            'total_on_time' => $totalOnTimeCount,
            'total_late' => $totalLateCount,
            'total_late_minutes' => $totalLateMinutesCount,
            'total_field_duty' => $totalFieldDutyCount,
            'total_missing_out' => $totalMissingOutCount,
            'total_alpha' => $totalAlphaCount,
            'total_penalty_amount' => $totalPenaltySum,
            'avg_attendance_rate' => round($avgAttendance, 1),
            'avg_discipline_rate' => round($avgDiscipline, 1),
        ];

        // 5. Query Log Rinci Harian (Detail Tab)
        $detailQuery = clone $attQuery;
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'on_time') {
                $detailQuery->where('status', 'present')->where(function ($q) {
                    $q->whereNull('late_minutes')->orWhere('late_minutes', '<=', 0);
                })->where('attendance_type', '!=', 'dinas_luar');
            } elseif ($request->status === 'late') {
                $detailQuery->where(function ($q) {
                    $q->where('status', 'late')->orWhere('late_minutes', '>', 0);
                });
            } elseif ($request->status === 'field_duty') {
                $detailQuery->where('attendance_type', 'dinas_luar');
            } elseif ($request->status === 'missing_out') {
                $detailQuery->whereNotNull('clock_in')
                            ->whereNull('clock_out')
                            ->whereDate('date', '<', $today);
            }
        }

        $detailQuery->orderByDesc('date')->orderByDesc('clock_in');

        if ($paginateDetail) {
            $paginatedDetails = $detailQuery->paginate($request->get('per_page', 25))->withQueryString();
            $detailLogs = $paginatedDetails->through(function ($item) use ($today) {
                return $this->formatDetailItem($item, $today);
            });
        } else {
            $rawItems = $detailQuery->get();
            $detailLogs = $rawItems->map(function ($item) use ($today) {
                return $this->formatDetailItem($item, $today);
            });
            $paginatedDetails = null;
        }

        return [
            'stats' => $stats,
            'periodDetails' => $periodDetails,
            'employeeRecap' => $employeeRecap,
            'detailLogs' => $detailLogs,
            'paginatedDetails' => $paginatedDetails,
            'areas' => $areas,
            'employees' => $allEmployees->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'area_id' => $e->area_id]),
            'filters' => [
                'period_mode' => $periodMode,
                'month' => $selectedMonth,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'area_id' => $request->get('area_id', ''),
                'employee_id' => $request->get('employee_id', ''),
                'status' => $request->get('status', 'all'),
                'attendance_type' => $request->get('attendance_type', 'all'),
                'tab' => $request->get('tab', 'recap'),
                'search' => $request->get('search', ''),
            ],
            'filterPeriodLabel' => $filterPeriodLabel,
        ];
    }

    /**
     * Format item record absensi untuk tabel detail dan export
     */
    protected function formatDetailItem(Attendance $item, Carbon $today): array
    {
        $dateCarbon = Carbon::parse($item->date);
        $isLate = $item->status === 'late' || (int)$item->late_minutes > 0;
        $isField = $item->attendance_type === 'dinas_luar';
        $isSpecial = $item->attendance_type === 'special_team';
        $isMissingOut = !empty($item->clock_in) && empty($item->clock_out) && $dateCarbon->isBefore($today);

        $statusLabel = 'Tepat Waktu';
        $statusBadge = 'green';
        if ($isLate) {
            $statusLabel = 'Terlambat (' . $item->late_minutes . 'm)';
            $statusBadge = 'amber';
        } elseif ($isField) {
            $statusLabel = 'Dinas Luar';
            $statusBadge = 'blue';
        } elseif ($isMissingOut) {
            $statusLabel = 'Tanpa Clock Out';
            $statusBadge = 'red';
        } elseif (empty($item->clock_in)) {
            $statusLabel = 'Tidak Hadir';
            $statusBadge = 'slate';
        }

        $typeLabel = 'WFO Reguler';
        if ($isField) {
            $typeLabel = 'Tugas Luar';
        } elseif ($isSpecial) {
            $typeLabel = 'Tim Khusus';
        }

        $shiftTime = '-';
        if ($item->shift && $item->shift->start_time && $item->shift->end_time) {
            $shiftTime = substr($item->shift->start_time, 0, 5) . ' - ' . substr($item->shift->end_time, 0, 5) . ' WIB';
        }

        return [
            'id' => $item->id,
            'date' => $dateCarbon->format('Y-m-d'),
            'formatted_date' => $dateCarbon->translatedFormat('d M Y'),
            'day_name' => $dateCarbon->translatedFormat('l'),
            'employee_name' => $item->employee?->name ?? 'Karyawan Dihapus',
            'iak_number' => $item->employee?->iak_number ?? '-',
            'position' => $item->employee?->position ?? '-',
            'area_name' => $item->area?->name ?? ($item->employee?->area?->name ?? '-'),
            'shift_name' => $item->shift?->name ?? 'Reguler',
            'shift_time' => $shiftTime,
            'clock_in' => $item->clock_in ? substr($item->clock_in, 0, 5) : null,
            'clock_out' => $item->clock_out ? substr($item->clock_out, 0, 5) : null,
            'late_minutes' => (int) $item->late_minutes,
            'status' => $item->status,
            'status_label' => $statusLabel,
            'status_badge' => $statusBadge,
            'attendance_type' => $item->attendance_type,
            'attendance_type_label' => $typeLabel,
            'distance_in' => $item->distance_in,
            'distance_out' => $item->distance_out,
            'photo_in' => $item->photo_in,
            'photo_out' => $item->photo_out,
            'lat_in' => $item->lat_in,
            'lng_in' => $item->lng_in,
            'notes' => $item->notes,
            'duty_location' => $item->duty_location,
            'is_synced' => (bool) $item->is_synced_to_payroll,
        ];
    }

    /**
     * Otorisasi ketat untuk aksi hapus: Hanya akun HRD, Finance, dan Admin
     */
    protected function authorizeDelete(): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $userRole = strtolower($user->role ?? '');
        $isAllowedRole = in_array($userRole, ['admin', 'hrd', 'finance']);
        $hasPermission = (bool) $user->can('attendance_report_btn_delete');

        if (!$isAllowedRole && !$hasPermission) {
            abort(403, 'Akses ditolak. Aksi hapus pada laporan absensi hanya diizinkan untuk akun HRD dan Finance.');
        }
    }

    /**
     * Hapus satu log rinci presensi
     */
    public function destroyLog(Request $request, Attendance $attendance)
    {
        $this->authorizeDelete();

        $employeeName = $attendance->employee?->name ?? 'Karyawan';
        $date = $attendance->date ? Carbon::parse($attendance->date)->translatedFormat('d M Y') : 'terpilih';

        $attendance->delete();

        return redirect()->back()->with('success', "Log presensi {$employeeName} pada tanggal {$date} berhasil dihapus.");
    }

    /**
     * Hapus seluruh log presensi karyawan dalam rentang periode filter yang sedang aktif
     */
    public function destroyEmployeeRecap(Request $request, Employee $employee)
    {
        $this->authorizeDelete();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            $periodMode = $request->input('period_mode', 'month');
            if ($periodMode === 'month') {
                $month = $request->input('month', now('Asia/Jakarta')->format('Y-m'));
                $startDate = Carbon::parse($month . '-01', 'Asia/Jakarta')->startOfMonth()->toDateString();
                $endDate = Carbon::parse($month . '-01', 'Asia/Jakarta')->endOfMonth()->toDateString();
            } else {
                $startDate = now('Asia/Jakarta')->startOfMonth()->toDateString();
                $endDate = now('Asia/Jakarta')->endOfMonth()->toDateString();
            }
        }

        $count = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->delete();

        $periodFormatted = Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y');

        return redirect()->back()->with('success', "Sebanyak {$count} data presensi {$employee->name} periode {$periodFormatted} berhasil dihapus.");
    }
}
