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

        // 1. Ambil atau Buat Default Tim Khusus jika belum ada
        $teams = SpecialTeam::withCount('members')->where('is_active', true)->get();
        if ($teams->isEmpty()) {
            $defaultTeam = SpecialTeam::create([
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

            $teams = SpecialTeam::withCount('members')->where('is_active', true)->get();
        }

        // Tentukan Tim Aktif
        $currentTeam = null;
        if ($request->filled('team_id')) {
            $currentTeam = SpecialTeam::with('employees')->find($request->team_id);
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

        // 2. Karyawan Aktif (Login User / Switcher)
        $currentEmployee = null;
        if ($user) {
            $currentEmployee = Employee::with(['area'])->where('email', $user->email)->first();
        }
        if ($request->filled('employee_id')) {
            $selected = Employee::with(['area'])->find($request->employee_id);
            if ($selected) {
                $currentEmployee = $selected;
            }
        }
        if (!$currentEmployee) {
            $currentEmployee = Employee::with(['area'])->first();
        }

        // Jika current employee belum terdaftar di tim aktif, auto-assign
        if ($currentEmployee && $currentTeam) {
            SpecialTeamMember::firstOrCreate(
                [
                    'special_team_id' => $currentTeam->id,
                    'employee_id' => $currentEmployee->id,
                ],
                [
                    'role' => $currentEmployee->position ?? 'Anggota',
                    'is_leader' => false,
                ]
            );
        }

        // 3. Absensi Hari Ini untuk Karyawan Aktif
        $todayAttendance = null;
        if ($currentEmployee && $currentTeam) {
            $todayAttendance = SpecialAttendance::where('special_team_id', $currentTeam->id)
                ->where('employee_id', $currentEmployee->id)
                ->where('date', $today)
                ->first();
        }

        // 4. Semua Absensi Hari Ini untuk Tim Aktif (Kolom Kanan)
        $todayAttendances = SpecialAttendance::with('employee')
            ->where('special_team_id', $currentTeam->id)
            ->where('date', $today)
            ->orderBy('clock_in', 'desc')
            ->get();

        // 5. Anggota Tim Aktif
        $teamMembers = SpecialTeamMember::with('employee')
            ->where('special_team_id', $currentTeam->id)
            ->get();

        // 6. Data Riwayat (Tab 2)
        $historyQuery = SpecialAttendance::with(['employee', 'team'])
            ->where('special_team_id', $currentTeam->id);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $historyQuery->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            // Default 30 hari terakhir
            $historyQuery->where('date', '>=', Carbon::today()->subDays(30)->format('Y-m-d'));
        }

        if ($request->filled('filter_employee_id')) {
            $historyQuery->where('employee_id', $request->filter_employee_id);
        }

        if ($request->filled('filter_status')) {
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
            $noClockOut = $memberAtts->whereNotNull('clock_in')->whereNull('clock_out')->count();

            $score = ($presents > 0) ? max(0, ($presents * 100) - ($lateMinutes * 2)) : 0;

            $adminMatrix[] = [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'role' => $member->role,
                'presents' => $presents,
                'lates' => $lates,
                'late_minutes' => $lateMinutes,
                'no_clock_out' => $noClockOut,
                'score' => $score,
            ];
        }

        // 8. Leaderboard & Absen Terbaik (Tab 4): Syarat mutlak harus memiliki catatan hadir
        $leaderboard = collect($adminMatrix)
            ->filter(function ($item) {
                return $item['presents'] > 0 && $item['score'] > 0;
            })
            ->sortByDesc('score')
            ->values();

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
            $tNoOutCount = $tAtts->whereNotNull('clock_in')->whereNull('clock_out')->count();

            $estDeductions = ($tLateCount * (float) $t->late_deduction_amount) + ($tNoOutCount * (float) $t->no_out_deduction_amount);

            $violationRecords = $tAtts->filter(function ($att) {
                return $att->status === 'late' || ($att->clock_in && !$att->clock_out);
            })->map(function ($att) use ($t) {
                $isLate = ($att->status === 'late');
                $isNoOut = (!empty($att->clock_in) && empty($att->clock_out));
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

        // Semua karyawan untuk modal Kelola Anggota
        $allEmployees = Employee::orderBy('name')->get(['id', 'name', 'position', 'branch', 'email']);

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
            'filters' => $request->only(['team_id', 'start_date', 'end_date', 'filter_employee_id', 'filter_status', 'month']),
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

        // Evaluasi Keterlambatan
        $scheduledStartTime = Carbon::parse($today . ' ' . $team->start_time);
        $toleranceMinutes = (int) $team->tolerance_minutes;
        $lateThreshold = $scheduledStartTime->copy()->addMinutes($toleranceMinutes);

        $status = 'present';
        $lateMinutes = 0;

        if ($now->greaterThan($lateThreshold)) {
            $status = 'late';
            $lateMinutes = $scheduledStartTime->diffInMinutes($now);
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
        $now = Carbon::now();
        $currentTimeStr = $now->format('H:i:s');

        $attendance = SpecialAttendance::where('special_team_id', $team->id)
            ->where('employee_id', $request->employee_id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', 'Anda belum melakukan absensi masuk hari ini.');
        }

        // Evaluasi Pulang Awal
        $scheduledEndTime = Carbon::parse($today . ' ' . $team->end_time);
        $earlyMinutes = 0;
        if ($now->lessThan($scheduledEndTime)) {
            $earlyMinutes = $now->diffInMinutes($scheduledEndTime);
        }

        // Simpan Foto Selfie Pulang
        $photoPath = $this->saveBase64Photo(
            $request->photo,
            'special_attendances',
            "out_{$request->employee_id}_{$today}"
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
        $name = $team->name;
        $team->delete();
        return redirect()->route('special-attendances.index')->with('success', "Grup '{$name}' berhasil dihapus.");
    }

    /**
     * Admin Tambah / Koreksi Absensi Manual
     */
    public function adminStoreAttendance(Request $request)
    {
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
        $attendance->delete();
        return back()->with('success', 'Catatan absensi berhasil dihapus.');
    }

    /**
     * Sinkronkan Potongan & Reward Tim Khusus ke Payroll
     */
    public function syncToPayroll(Request $request)
    {
        $request->validate([
            'special_team_id' => 'required|exists:special_teams,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $team = SpecialTeam::findOrFail($request->special_team_id);
        $month = $request->month;
        $monthStart = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

        $attendances = SpecialAttendance::with('employee')
            ->where('special_team_id', $team->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy('employee_id');

        $totalSyncedDeductions = 0;
        $totalSyncedRewards = 0;

        DB::transaction(function () use ($attendances, $team, $month, $monthEnd, &$totalSyncedDeductions, &$totalSyncedRewards) {
            $performance = [];

            foreach ($attendances as $empId => $atts) {
                $emp = $atts->first()->employee;
                if (!$emp || !$emp->email) continue;
                $user = User::where('email', $emp->email)->first();
                if (!$user) continue;

                $presents = $atts->whereNotNull('clock_in')->count();
                $lates = $atts->where('status', 'late')->count();
                $lateMinutes = (int) $atts->sum('late_minutes');
                $noOutCount = $atts->whereNotNull('clock_in')->whereNull('clock_out')->count();

                // 1. Potongan Keterlambatan Tim Khusus
                $totalDeduction = ($lates * (float) $team->late_deduction_amount) + ($noOutCount * (float) $team->no_out_deduction_amount);

                if ($totalDeduction > 0) {
                    $desc = "Potongan Absen {$team->name} Periode {$month} ({$lates}x telat, {$noOutCount}x tanpa absen pulang)";
                    Deduction::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'type' => 'auto',
                            'description' => $desc,
                        ],
                        [
                            'amount' => $totalDeduction,
                            'deduction_date' => $monthEnd,
                            'status' => 'pending',
                        ]
                    );
                    $totalSyncedDeductions++;
                }

                if ($presents > 0) {
                    $performance[] = [
                        'user' => $user,
                        'employee' => $emp,
                        'score' => ($presents * 100) - ($lateMinutes * 2),
                    ];
                }
            }

            // 2. Reward Absen Terbaik Tim Khusus (Top 3)
            $sorted = collect($performance)->sortByDesc('score')->values();
            $nominals = [
                1 => (float) Setting::get('attendance_reward_special_1', 200000),
                2 => (float) Setting::get('attendance_reward_special_2', 150000),
                3 => (float) Setting::get('attendance_reward_special_3', 100000),
            ];

            for ($rank = 1; $rank <= min(3, $sorted->count()); $rank++) {
                $winner = $sorted[$rank - 1];
                $amt = $nominals[$rank] ?? 0;
                if ($amt > 0) {
                    Incentive::updateOrCreate(
                        [
                            'user_id' => $winner['user']->id,
                            'type' => 'auto',
                            'description' => "Reward Absen Terbaik (Juara {$rank} - {$team->name}) Periode {$month}",
                        ],
                        [
                            'amount' => $amt,
                            'incentive_date' => $monthEnd,
                            'status' => 'pending',
                        ]
                    );
                    $totalSyncedRewards++;
                }
            }
        });

        return back()->with('success', "Berhasil menyinkronkan data {$team->name} ke Penggajian: {$totalSyncedDeductions} potongan & {$totalSyncedRewards} reward absen terbaik.");
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
