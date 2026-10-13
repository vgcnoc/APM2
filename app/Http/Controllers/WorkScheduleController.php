<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Employee;
use App\Models\Position;
use App\Models\RosterPattern;
use App\Models\Shift;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkScheduleController extends Controller
{
    /**
     * Halaman Utama Jadwal Kerja & Roster Shift
     */
    public function index(Request $request)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        
        try {
            $currentMonth = Carbon::parse($month . '-01');
        } catch (\Exception $e) {
            $month = Carbon::now()->format('Y-m');
            $currentMonth = Carbon::parse($month . '-01');
        }

        $daysInMonth = $currentMonth->daysInMonth;
        $dayNamesIndo = [
            0 => 'MIN',
            1 => 'SEN',
            2 => 'SEL',
            3 => 'RAB',
            4 => 'KAM',
            5 => 'JUM',
            6 => 'SAB',
        ];

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateObj = $currentMonth->copy()->day($d);
            $dayOfWeek = $dateObj->dayOfWeek;
            $days[] = [
                'day' => $d,
                'date' => $dateObj->format('Y-m-d'),
                'day_name' => $dayNamesIndo[$dayOfWeek],
                'is_sunday' => $dayOfWeek === 0,
                'is_today' => $dateObj->isToday(),
            ];
        }

        // Query Employees dengan eager load area & shift
        $employeeQuery = Employee::with(['area', 'shift']);

        if ($request->filled('branch')) {
            $branchVal = $request->branch;
            if (is_numeric($branchVal)) {
                $employeeQuery->where('area_id', $branchVal);
            } else {
                $employeeQuery->where(function ($q) use ($branchVal) {
                    $q->where('branch', $branchVal)
                      ->orWhereHas('area', function ($sq) use ($branchVal) {
                          $sq->where('name', $branchVal);
                      });
                });
            }
        }

        if ($request->filled('position')) {
            $employeeQuery->where('position', $request->position);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $employeeQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('branch', 'like', "%{$search}%")
                  ->orWhereHas('area', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $employees = $employeeQuery->orderBy('name')->get();

        // Master Shifts Aktif
        $shifts = Shift::where('is_active', true)->orderBy('name')->get();

        // Roster Patterns
        $patterns = RosterPattern::orderBy('name')->get();

        // Jadwal pada bulan ini
        $startDateStr = $currentMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDateStr = $currentMonth->copy()->endOfMonth()->format('Y-m-d');

        $schedules = WorkSchedule::with('shift')
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get();

        // Susun Map: [employee_id][date_string] = [shift_id, status, notes, shift_data]
        $schedulesMap = [];
        foreach ($schedules as $sched) {
            $dateStr = $sched->date->format('Y-m-d');
            $schedulesMap[$sched->employee_id][$dateStr] = [
                'id' => $sched->id,
                'employee_id' => $sched->employee_id,
                'shift_id' => $sched->shift_id,
                'shift_name' => $sched->shift?->name,
                'shift_color' => $sched->shift?->color,
                'status' => $sched->status,
                'notes' => $sched->notes,
            ];
        }

        // Statistik Cards
        $totalEmployees = $employees->count();
        $totalSchedules = 0;
        $totalOff = 0;
        $shiftCounts = [];

        foreach ($schedules as $sched) {
            if ($sched->status === 'off' || empty($sched->shift_id)) {
                $totalOff++;
            } else {
                $totalSchedules++;
                $shiftName = $sched->shift?->name ?: 'Shift Lain';
                if (!isset($shiftCounts[$shiftName])) {
                    $shiftCounts[$shiftName] = 0;
                }
                $shiftCounts[$shiftName]++;
            }
        }

        // Urutkan shift terbanyak menurun
        arsort($shiftCounts);

        // Ambil daftar Master Cabang (Area) dan Jabatan (Position)
        $branches = Area::orderBy('name')->get(['id', 'name'])->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
            ];
        });

        // Tambahkan branch custom yang tercatat di tabel employees jika ada
        $extraBranches = Employee::whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->pluck('branch');
        foreach ($extraBranches as $eb) {
            if (!$branches->contains('name', $eb)) {
                $branches->push(['id' => $eb, 'name' => $eb]);
            }
        }

        // Master Jabatan (Position)
        $positions = Position::orderBy('name')->get(['id', 'name'])->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
            ];
        });

        // Tambahkan position custom yang ada di karyawan jika belum terdaftar di master
        $extraPositions = Employee::whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->pluck('position');
        foreach ($extraPositions as $ep) {
            if (!$positions->contains('name', $ep)) {
                $positions->push(['id' => $ep, 'name' => $ep]);
            }
        }

        return Inertia::render('WorkSchedules/Index', [
            'month' => $month,
            'monthName' => $currentMonth->translatedFormat('F Y'),
            'days' => $days,
            'employees' => $employees,
            'shifts' => $shifts,
            'patterns' => $patterns,
            'schedulesMap' => $schedulesMap,
            'branches' => $branches,
            'positions' => $positions,
            'filters' => [
                'branch' => $request->branch ?? '',
                'position' => $request->position ?? '',
                'search' => $request->search ?? '',
            ],
            'stats' => [
                'totalEmployees' => $totalEmployees,
                'totalSchedules' => $totalSchedules,
                'totalOff' => $totalOff,
                'shiftCounts' => $shiftCounts,
            ],
        ]);
    }

    /**
     * Batch simpan perubahan jadwal kalender
     */
    public function saveBatch(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'changes' => 'required|array',
            'changes.*.employee_id' => 'required|integer',
            'changes.*.date' => 'required|date_format:Y-m-d',
            'changes.*.shift_id' => 'nullable',
            'changes.*.status' => 'nullable|string',
            'changes.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->changes as $change) {
                $employeeId = $change['employee_id'];
                $date = $change['date'];
                $shiftId = !empty($change['shift_id']) && $change['shift_id'] !== 'OFF' ? (int) $change['shift_id'] : null;
                $status = $change['status'] ?? ($shiftId ? 'work' : 'off');

                if ($change['shift_id'] === 'OFF') {
                    $shiftId = null;
                    $status = 'off';
                }

                // Jika benar-benar kosong dan status bukan off, hapus record
                if ($change['shift_id'] === null && empty($status)) {
                    WorkSchedule::where('employee_id', $employeeId)->where('date', $date)->delete();
                } else {
                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $employeeId,
                            'date' => $date,
                        ],
                        [
                            'shift_id' => $shiftId,
                            'status' => $status,
                            'notes' => $change['notes'] ?? null,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Perubahan jadwal kerja berhasil disimpan!');
    }

    /**
     * Generator jadwal otomatis menggunakan pola shift
     */
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'employee_ids' => 'required|array',
            'pattern_days' => 'required|array', // e.g. [1, 1, 1, 1, 1, 1, 'OFF']
            'offset' => 'nullable|integer',
        ]);

        $month = $request->month;
        $currentMonth = Carbon::parse($month . '-01');
        $daysInMonth = $currentMonth->daysInMonth;
        $pattern = $request->pattern_days;
        $patternLen = count($pattern);
        $offset = (int) ($request->offset ?? 0);

        if ($patternLen === 0) {
            return back()->with('error', 'Pola shift tidak boleh kosong.');
        }

        DB::transaction(function () use ($request, $currentMonth, $daysInMonth, $pattern, $patternLen, $offset) {
            $employeeIds = $request->employee_ids;

            foreach ($employeeIds as $empIdx => $empId) {
                // Untuk rotasi karyawan, kita bisa kombinasikan offset dengan indeks karyawan jika diinginkan,
                // atau gunakan offset tetap
                $currentIdx = ($offset + ($request->stagger ? $empIdx : 0)) % $patternLen;

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dateStr = $currentMonth->copy()->day($d)->format('Y-m-d');
                    $val = $pattern[$currentIdx];

                    $isOff = ($val === 'OFF' || empty($val));
                    $shiftId = (!$isOff && is_numeric($val)) ? (int) $val : null;
                    $status = $isOff ? 'off' : 'work';

                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'date' => $dateStr,
                        ],
                        [
                            'shift_id' => $shiftId,
                            'status' => $status,
                        ]
                    );

                    $currentIdx = ($currentIdx + 1) % $patternLen;
                }
            }
        });

        return back()->with('success', 'Jadwal kerja berhasil digenerate otomatis!');
    }

    /**
     * Lanjutkan pola jadwal dari akhir bulan lalu
     */
    public function continuePreviousMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
        ]);

        $targetMonth = Carbon::parse($request->month . '-01');
        $prevMonth = $targetMonth->copy()->subMonth();
        $daysInTarget = $targetMonth->daysInMonth;

        $startDatePrev = $prevMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDatePrev = $prevMonth->copy()->endOfMonth()->format('Y-m-d');

        // Ambil riwayat jadwal bulan lalu
        $prevSchedules = WorkSchedule::whereBetween('date', [$startDatePrev, $endDatePrev])
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('employee_id');

        if ($prevSchedules->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan data jadwal pada bulan sebelumnya (' . $prevMonth->translatedFormat('F Y') . ').');
        }

        DB::transaction(function () use ($prevSchedules, $targetMonth, $daysInTarget) {
            foreach ($prevSchedules as $empId => $schedList) {
                // Ambil shift terakhir yang tercatat di bulan lalu
                $lastSchedule = $schedList->first(); // karena sudah diurutkan desc
                $lastShiftId = $lastSchedule->shift_id;
                $lastStatus = $lastSchedule->status;

                // Terapkan pola sederhana berulang atau lanjutkan shift default
                for ($d = 1; $d <= $daysInTarget; $d++) {
                    $dateStr = $targetMonth->copy()->day($d)->format('Y-m-d');
                    $dayOfWeek = $targetMonth->copy()->day($d)->dayOfWeek;

                    // Jika hari Minggu secara default libur kecuali sudah ada pola khusus
                    $isSunday = ($dayOfWeek === 0);
                    $shiftId = $isSunday ? null : ($lastShiftId ?: 1);
                    $status = $isSunday ? 'off' : 'work';

                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'date' => $dateStr,
                        ],
                        [
                            'shift_id' => $shiftId,
                            'status' => $status,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Jadwal berhasil dilanjutkan dari bulan lalu!');
    }

    /**
     * Salin jadwal dari bulan lain ke bulan saat ini
     */
    public function copyMonth(Request $request)
    {
        $request->validate([
            'from_month' => 'required|string',
            'to_month' => 'required|string',
        ]);

        $fromMonth = Carbon::parse($request->from_month . '-01');
        $toMonth = Carbon::parse($request->to_month . '-01');

        $startFrom = $fromMonth->copy()->startOfMonth()->format('Y-m-d');
        $endFrom = $fromMonth->copy()->endOfMonth()->format('Y-m-d');

        $sourceSchedules = WorkSchedule::whereBetween('date', [$startFrom, $endFrom])->get();

        if ($sourceSchedules->isEmpty()) {
            return back()->with('error', 'Bulan sumber (' . $fromMonth->translatedFormat('F Y') . ') belum memiliki jadwal.');
        }

        $daysInTarget = $toMonth->daysInMonth;

        DB::transaction(function () use ($sourceSchedules, $toMonth, $daysInTarget) {
            foreach ($sourceSchedules as $source) {
                $dayNumber = (int) $source->date->format('j');
                if ($dayNumber <= $daysInTarget) {
                    $targetDate = $toMonth->copy()->day($dayNumber)->format('Y-m-d');
                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $source->employee_id,
                            'date' => $targetDate,
                        ],
                        [
                            'shift_id' => $source->shift_id,
                            'status' => $source->status,
                            'notes' => $source->notes,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Jadwal kerja berhasil disalin dari ' . $fromMonth->translatedFormat('F Y') . '!');
    }

    /**
     * Reset / Kosongkan jadwal pada bulan aktif
     */
    public function resetMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
        ]);

        $targetMonth = Carbon::parse($request->month . '-01');
        $startDate = $targetMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $targetMonth->copy()->endOfMonth()->format('Y-m-d');

        WorkSchedule::whereBetween('date', [$startDate, $endDate])->delete();

        return back()->with('success', 'Seluruh jadwal bulan ' . $targetMonth->translatedFormat('F Y') . ' berhasil direset.');
    }

    /**
     * Simpan Master Pola Roster
     */
    public function storePattern(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'pattern_data' => 'required|array',
            'description' => 'nullable|string',
        ]);

        RosterPattern::create($validated);

        return back()->with('success', 'Pola roster baru berhasil disimpan!');
    }

    /**
     * Hapus Master Pola Roster
     */
    public function destroyPattern($id)
    {
        $pattern = RosterPattern::findOrFail($id);
        $pattern->delete();

        return back()->with('success', 'Pola roster berhasil dihapus!');
    }

    /**
     * Quick Add Shift langsung dari halaman Jadwal Kerja
     */
    public function quickStoreShift(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = true;
        Shift::create($validated);

        return back()->with('success', 'Shift baru berhasil ditambahkan!');
    }
}
