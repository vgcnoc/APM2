<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
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
                // Header CSV Rekapitulasi per Karyawan
                fputcsv($handle, [
                    'No',
                    'Nama Karyawan',
                    'IAK / NIK',
                    'Jabatan',
                    'Wilayah / Cabang',
                    'Shift Default',
                    'Total Hadir (Hari)',
                    'Tepat Waktu (Hari)',
                    'Terlambat (Kali)',
                    'Total Menit Terlambat',
                    'Tugas Luar / Dinas (Hari)',
                    'Tanpa Clock-Out (Kali)',
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
                        $emp['total_present'],
                        $emp['total_on_time'],
                        $emp['total_late'],
                        $emp['total_late_minutes'],
                        $emp['total_field_duty'],
                        $emp['total_missing_out'],
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
     * Helper agregasi data laporan absensi
     */
    protected function buildReportData(Request $request, bool $paginateDetail = true): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $periodMode = $request->get('period_mode', 'month'); // 'month' atau 'custom'
        $selectedMonth = $request->get('month', $today->format('Y-m'));

        if ($periodMode === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $filterPeriodLabel = $startDate->translatedFormat('d M Y') . ' s/d ' . $endDate->translatedFormat('d M Y');
        } else {
            $monthCarbon = Carbon::parse($selectedMonth . '-01');
            $startDate = (clone $monthCarbon)->startOfMonth()->startOfDay();
            $endDate = (clone $monthCarbon)->endOfMonth()->endOfDay();
            $filterPeriodLabel = $monthCarbon->translatedFormat('F Y');
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

        // 2. Query Utama Data Absensi dalam Periode
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

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $attQuery->whereHas('employee', function ($eq) use ($search) {
                $eq->where('name', 'like', "%{$search}%")
                   ->orWhere('iak_number', 'like', "%{$search}%");
            });
        }

        // Ambil semua records untuk kalkulasi statistik dan rekap
        $allAttendancesInPeriod = (clone $attQuery)->get();

        // Parameter Aturan Potongan & Sanksi
        $latePenaltyMode = Setting::get('attendance_late_mode', 'flat');
        $fixedPenalty = (float) Setting::get('attendance_late_amount', 10000);
        $perMinutePenalty = (float) Setting::get('attendance_late_penalty_per_minute', 1000);
        $missingOutPenalty = (float) Setting::get('attendance_missing_out_amount', 10000);
        $latePenaltyEnabled = Setting::get('attendance_late_enabled', Setting::get('attendance_late_penalty_enabled', '1')) === '1';
        $missingOutPenaltyEnabled = Setting::get('attendance_missing_out_enabled', Setting::get('attendance_missing_out_penalty_enabled', '1')) === '1';

        // 3. Rekapitulasi per Karyawan (Matrix)
        $employeeRecap = $allEmployees->map(function ($emp) use (
            $allAttendancesInPeriod,
            $latePenaltyEnabled,
            $missingOutPenaltyEnabled,
            $latePenaltyMode,
            $fixedPenalty,
            $perMinutePenalty,
            $missingOutPenalty,
            $today
        ) {
            $empAtts = $allAttendancesInPeriod->where('employee_id', $emp->id);

            $totalPresent = $empAtts->whereNotNull('clock_in')->count();
            $totalLate = $empAtts->filter(fn($a) => $a->status === 'late' || (int)$a->late_minutes > 0)->count();
            $totalLateMinutes = (int) $empAtts->sum('late_minutes');
            $totalFieldDuty = $empAtts->where('attendance_type', 'dinas_luar')->count();

            // Missing clock out: ada clock-in, tidak ada clock-out, dan tanggalnya sudah lewat hari ini
            $totalMissingOut = $empAtts->filter(function ($a) use ($today) {
                return !empty($a->clock_in) && empty($a->clock_out) && Carbon::parse($a->date)->isBefore($today);
            })->count();

            $totalOnTime = max(0, $totalPresent - $totalLate - $totalFieldDuty);

            // Tingkat kedisiplinan persentase
            $disciplineRate = $totalPresent > 0 ? round(($totalOnTime / $totalPresent) * 100, 1) : 0;

            // Estimasi Potongan
            $latePenalty = 0;
            if ($latePenaltyEnabled && $totalLate > 0) {
                $latePenalty = ($latePenaltyMode === 'per_menit')
                    ? ($totalLateMinutes * $perMinutePenalty)
                    : ($totalLate * $fixedPenalty);
            }

            $missingOutDeduction = 0;
            if ($missingOutPenaltyEnabled && $totalMissingOut > 0) {
                $missingOutDeduction = $totalMissingOut * $missingOutPenalty;
            }

            $estimatedPenalty = $latePenalty + $missingOutDeduction;

            $totalSynced = $empAtts->where('is_synced_to_payroll', true)->count();
            $isAllSynced = $totalLate > 0 ? ($totalSynced >= $totalLate) : true;

            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'iak_number' => $emp->iak_number,
                'position' => $emp->position,
                'area_name' => $emp->area?->name ?? '-',
                'shift_name' => $emp->shift?->name ?? 'Reguler',
                'total_present' => $totalPresent,
                'total_on_time' => $totalOnTime,
                'total_late' => $totalLate,
                'total_late_minutes' => $totalLateMinutes,
                'total_field_duty' => $totalFieldDuty,
                'total_missing_out' => $totalMissingOut,
                'discipline_rate' => $disciplineRate,
                'estimated_penalty' => $estimatedPenalty,
                'is_all_synced' => $isAllSynced,
            ];
        })->values();

        // 4. Kalkulasi Summary Cards (Statistik Global Periode)
        $totalLogs = $allAttendancesInPeriod->count();
        $totalPresentCount = $allAttendancesInPeriod->whereNotNull('clock_in')->count();
        $totalLateCount = $allAttendancesInPeriod->filter(fn($a) => $a->status === 'late' || (int)$a->late_minutes > 0)->count();
        $totalLateMinutesCount = (int) $allAttendancesInPeriod->sum('late_minutes');
        $totalFieldDutyCount = $allAttendancesInPeriod->where('attendance_type', 'dinas_luar')->count();
        $totalMissingOutCount = $allAttendancesInPeriod->filter(function ($a) use ($today) {
            return !empty($a->clock_in) && empty($a->clock_out) && Carbon::parse($a->date)->isBefore($today);
        })->count();
        $totalOnTimeCount = max(0, $totalPresentCount - $totalLateCount - $totalFieldDutyCount);
        $totalPenaltySum = $employeeRecap->sum('estimated_penalty');
        $avgDiscipline = $employeeRecap->where('total_present', '>', 0)->avg('discipline_rate') ?: 0;

        $stats = [
            'total_employees' => $allEmployees->count(),
            'total_logs' => $totalLogs,
            'total_present' => $totalPresentCount,
            'total_on_time' => $totalOnTimeCount,
            'total_late' => $totalLateCount,
            'total_late_minutes' => $totalLateMinutesCount,
            'total_field_duty' => $totalFieldDutyCount,
            'total_missing_out' => $totalMissingOutCount,
            'total_penalty_amount' => $totalPenaltySum,
            'avg_discipline_rate' => round($avgDiscipline, 1),
        ];

        // 5. Query Log Rinci Harian (Detail Tab)
        // Filter status khusus pada query detail jika diminta
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
}

