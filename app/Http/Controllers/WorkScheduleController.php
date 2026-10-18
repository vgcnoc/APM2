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

        if ($request->filled('branch') || $request->filled('area')) {
            $branchVal = $request->branch ?? $request->area;
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
            'areas' => $branches,
            'positions' => $positions,
            'filters' => [
                'branch' => $request->branch ?? $request->area ?? '',
                'area' => $request->area ?? $request->branch ?? '',
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
    /**
     * Generator jadwal otomatis menggunakan pola shift dengan dukungan periode tanggal, fase awal absen, dan proteksi overwrite
     */
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'employee_ids' => 'required|array',
            'pattern_type' => 'nullable|string', // 'weekly_6_1', 'weekly_5_2', 'cycle'
            'pattern_days' => 'nullable|array', // e.g. [1, 1, 1, 1, 1, 1, 'OFF']
            'default_shift_id' => 'nullable|integer',
            'start_offset' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'stagger' => 'nullable|boolean',
            'overwrite_mode' => 'nullable|string', // 'all', 'empty_only', 'preserve_off'
        ]);

        $month = $request->month;
        $currentMonth = Carbon::parse($month . '-01');

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : $currentMonth->copy()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : $currentMonth->copy()->endOfMonth();

        if ($startDate->gt($endDate)) {
            return back()->with('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
        }

        $patternType = $request->pattern_type ?? 'cycle';
        $pattern = $request->pattern_days ?? [];
        $patternLen = count($pattern);
        $startOffset = (int) ($request->start_offset ?? $request->offset ?? 0);
        $defaultShiftId = !empty($request->default_shift_id) ? (int) $request->default_shift_id : (Shift::first()?->id ?? 1);
        $isStagger = (bool) $request->stagger;
        $overwriteMode = $request->input('overwrite_mode', 'all'); // 'all', 'empty_only', 'preserve_off'

        if ($patternType === 'cycle' && $patternLen === 0) {
            return back()->with('error', 'Pola shift tidak boleh kosong.');
        }

        DB::transaction(function () use ($request, $startDate, $endDate, $patternType, $pattern, $patternLen, $startOffset, $defaultShiftId, $isStagger, $overwriteMode) {
            $employeeIds = $request->employee_ids;

            foreach ($employeeIds as $empIdx => $empId) {
                $curDate = $startDate->copy();
                $dayIndex = 0;

                // Hitung fase / offset awal
                $initialOffset = $startOffset + ($isStagger ? ($empIdx % ($patternLen ?: 7)) : 0);
                if ($patternLen > 0) {
                    $initialOffset = $initialOffset % $patternLen;
                }

                while ($curDate->lte($endDate)) {
                    $dateStr = $curDate->format('Y-m-d');
                    $dayOfWeek = $curDate->dayOfWeek; // 0 = Sun, 1 = Mon ... 6 = Sat

                    // Proteksi Overwrite Mode
                    if ($overwriteMode !== 'all') {
                        $existing = WorkSchedule::where('employee_id', $empId)->where('date', $dateStr)->first();
                        if ($overwriteMode === 'empty_only' && $existing) {
                            $curDate->addDay();
                            $dayIndex++;
                            continue;
                        }
                        if ($overwriteMode === 'preserve_off' && $existing && ($existing->status === 'off' || empty($existing->shift_id))) {
                            $curDate->addDay();
                            $dayIndex++;
                            continue;
                        }
                    }

                    if ($patternType === 'weekly_6_1') {
                        // Pola 6 Kerja : 1 Libur
                        $offDayOfWeek = $isStagger ? (($empIdx + $startOffset) % 7) : 0;
                        $isOff = ($dayOfWeek === $offDayOfWeek);
                        $targetShiftId = $isOff ? null : $defaultShiftId;
                        $targetStatus = $isOff ? 'off' : 'work';
                    } elseif ($patternType === 'weekly_5_2') {
                        // Pola 5 Kerja : 2 Libur
                        if ($isStagger) {
                            $firstOff = (($empIdx * 2) + $startOffset) % 7;
                            $secondOff = ($firstOff + 1) % 7;
                            $isOff = ($dayOfWeek === $firstOff || $dayOfWeek === $secondOff);
                        } else {
                            $isOff = ($dayOfWeek === 0 || $dayOfWeek === 6);
                        }
                        $targetShiftId = $isOff ? null : $defaultShiftId;
                        $targetStatus = $isOff ? 'off' : 'work';
                    } else {
                        // Pola Roster Siklus / Bergilir
                        $cycleIdx = ($initialOffset + $dayIndex) % $patternLen;
                        $val = $pattern[$cycleIdx];
                        $isOff = ($val === 'OFF' || empty($val));
                        $targetShiftId = (!$isOff && is_numeric($val)) ? (int) $val : null;
                        $targetStatus = $isOff ? 'off' : 'work';
                    }

                    WorkSchedule::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'date' => $dateStr,
                        ],
                        [
                            'shift_id' => $targetShiftId,
                            'status' => $targetStatus,
                        ]
                    );

                    $curDate->addDay();
                    $dayIndex++;
                }
            }
        });

        $daysCount = $startDate->diffInDays($endDate) + 1;
        return back()->with('success', "Jadwal kerja berhasil digenerate otomatis untuk {$daysCount} hari ({$startDate->translatedFormat('d M')} - {$endDate->translatedFormat('d M Y')})!");
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

        // Ambil riwayat jadwal bulan lalu diurutkan ASC
        $prevSchedules = WorkSchedule::whereBetween('date', [$startDatePrev, $endDatePrev])
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('employee_id');

        if ($prevSchedules->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan data jadwal pada bulan sebelumnya (' . $prevMonth->translatedFormat('F Y') . ').');
        }

        DB::transaction(function () use ($prevSchedules, $targetMonth, $daysInTarget) {
            foreach ($prevSchedules as $empId => $schedList) {
                // Analisis karakteristik jadwal bulan lalu karyawan
                $sundayOffCount = 0;
                $saturdayOffCount = 0;
                $totalOffCount = 0;
                $activeShifts = [];
                $sequence = [];

                foreach ($schedList as $sc) {
                    $dow = $sc->date->dayOfWeek;
                    if ($sc->status === 'off' || empty($sc->shift_id)) {
                        $totalOffCount++;
                        if ($dow === 0) $sundayOffCount++;
                        if ($dow === 6) $saturdayOffCount++;
                        $sequence[] = 'OFF';
                    } else {
                        $activeShifts[] = $sc->shift_id;
                        $sequence[] = (int) $sc->shift_id;
                    }
                }

                $sundaysInPrevMonth = 0;
                $cur = $schedList->first()->date->copy()->startOfMonth();
                $endPrev = $cur->copy()->endOfMonth();
                while ($cur->lte($endPrev)) {
                    if ($cur->dayOfWeek === 0) $sundaysInPrevMonth++;
                    $cur->addDay();
                }

                $isWeekly5_2 = ($sundayOffCount >= ($sundaysInPrevMonth - 1)) && ($saturdayOffCount >= ($sundaysInPrevMonth - 1));
                $isWeekly6_1 = ($sundayOffCount >= ($sundaysInPrevMonth - 1)) && ($totalOffCount <= ($sundaysInPrevMonth + 2));

                $mostFrequentShiftId = !empty($activeShifts) 
                    ? (int) array_search(max(array_count_values($activeShifts)), array_count_values($activeShifts))
                    : 1;

                if ($isWeekly5_2) {
                    // Pola 5-2: Senin-Jumat Kerja, Sabtu-Minggu Libur
                    for ($d = 1; $d <= $daysInTarget; $d++) {
                        $dateObj = $targetMonth->copy()->day($d);
                        $dow = $dateObj->dayOfWeek;
                        $isWeekend = ($dow === 0 || $dow === 6);

                        WorkSchedule::updateOrCreate(
                            ['employee_id' => $empId, 'date' => $dateObj->format('Y-m-d')],
                            [
                                'shift_id' => $isWeekend ? null : $mostFrequentShiftId,
                                'status' => $isWeekend ? 'off' : 'work',
                            ]
                        );
                    }
                } elseif ($isWeekly6_1) {
                    // Pola 6-1: Senin-Sabtu Kerja, Minggu Libur
                    for ($d = 1; $d <= $daysInTarget; $d++) {
                        $dateObj = $targetMonth->copy()->day($d);
                        $dow = $dateObj->dayOfWeek;
                        $isSunday = ($dow === 0);

                        WorkSchedule::updateOrCreate(
                            ['employee_id' => $empId, 'date' => $dateObj->format('Y-m-d')],
                            [
                                'shift_id' => $isSunday ? null : $mostFrequentShiftId,
                                'status' => $isSunday ? 'off' : 'work',
                            ]
                        );
                    }
                } else {
                    // Pola Roster Bergilir / Rotasi Siklus (seperti CS atau teknisi 24/7)
                    $detectedPeriod = null;
                    $seqLen = count($sequence);

                    for ($p = 2; $p <= 10; $p++) {
                        if ($seqLen >= $p * 2) {
                            $matches = true;
                            for ($k = $seqLen - $p; $k < $seqLen; $k++) {
                                if ($sequence[$k] !== $sequence[$k - $p]) {
                                    $matches = false;
                                    break;
                                }
                            }
                            if ($matches) {
                                $detectedPeriod = $p;
                                break;
                            }
                        }
                    }

                    if ($detectedPeriod) {
                        $cyclePattern = array_slice($sequence, $seqLen - $detectedPeriod, $detectedPeriod);
                    } else {
                        $cyclePattern = array_slice($sequence, max(0, $seqLen - 7));
                    }

                    $pLen = count($cyclePattern);
                    $cIdx = 0;

                    for ($d = 1; $d <= $daysInTarget; $d++) {
                        $dateObj = $targetMonth->copy()->day($d);
                        $val = $cyclePattern[$cIdx % $pLen];
                        $isOff = ($val === 'OFF' || empty($val));

                        WorkSchedule::updateOrCreate(
                            ['employee_id' => $empId, 'date' => $dateObj->format('Y-m-d')],
                            [
                                'shift_id' => $isOff ? null : (int) $val,
                                'status' => $isOff ? 'off' : 'work',
                            ]
                        );

                        $cIdx++;
                    }
                }
            }
        });

        return back()->with('success', 'Jadwal kerja berhasil dilanjutkan secara cerdas dari bulan lalu!');
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
     * Reset / Kosongkan jadwal secara fleksibel & cerdas
     */
    public function resetMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'scope' => 'nullable|string', // 'all', 'employee', 'date_range', 'single_day', 'branch', 'shift'
            'employee_ids' => 'nullable|array',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'date' => 'nullable|date_format:Y-m-d',
            'shift_id' => 'nullable',
            'branch' => 'nullable|string',
        ]);

        $targetMonth = Carbon::parse($request->month . '-01');
        $startDate = $targetMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $targetMonth->copy()->endOfMonth()->format('Y-m-d');

        $query = WorkSchedule::query();
        $scope = $request->input('scope', 'all');

        if ($scope === 'date_range' && $request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($scope === 'single_day' && $request->filled('date')) {
            $query->where('date', $request->date);
        } else {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if (!empty($request->employee_ids)) {
            $query->whereIn('employee_id', $request->employee_ids);
        }

        if ($request->filled('branch')) {
            $branch = $request->branch;
            $query->whereHas('employee', function ($eq) use ($branch) {
                if (is_numeric($branch)) {
                    $eq->where('area_id', $branch);
                } else {
                    $eq->where('branch', $branch)
                       ->orWhereHas('area', function ($aq) use ($branch) {
                           $aq->where('name', $branch);
                       });
                }
            });
        }

        if ($request->filled('shift_id')) {
            if ($request->shift_id === 'OFF') {
                $query->where(function ($sq) {
                    $sq->whereNull('shift_id')->orWhere('status', 'off');
                });
            } else {
                $query->where('shift_id', (int) $request->shift_id);
            }
        }

        $deletedCount = $query->delete();

        return back()->with('success', "Berhasil menghapus {$deletedCount} data jadwal kerja.");
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
