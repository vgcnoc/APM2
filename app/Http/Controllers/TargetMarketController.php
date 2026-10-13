<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\InternetPackage;
use App\Models\TargetMarketLog;
use App\Models\TargetMarketProgram;
use App\Models\User;
use App\Services\TargetMarketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TargetMarketController extends Controller
{
    public function index(Request $request): Response
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $tab = $request->query('tab', 'leaderboard'); // leaderboard, programs, transactions

        // Program untuk periode ini
        $programs = TargetMarketProgram::where('period_month', $month)
            ->where('period_year', $year)
            ->with('creator')
            ->orderByDesc('id')
            ->get();

        // Jika belum ada program tapi ada program bulan berjalan, buatkan default jika baru pertama kali
        if ($programs->isEmpty() && TargetMarketProgram::count() === 0) {
            TargetMarketProgram::create([
                'title' => 'Program Akselerasi Target Q4 ' . $year,
                'concept' => 'hybrid',
                'period_month' => $month,
                'period_year' => $year,
                'target_quota' => 15,
                'target_role' => null, // semua karyawan
                'reward_flat' => 25000,
                'reward_bonus_target' => 500000,
                'rules' => [
                    'tiers' => [
                        ['min' => 1, 'max' => 5, 'reward' => 25000],
                        ['min' => 6, 'max' => 15, 'reward' => 50000],
                        ['min' => 16, 'max' => null, 'reward' => 75000],
                    ],
                    'target_areas' => [],
                    'target_packages' => [],
                ],
                'description' => 'Program komisi target market hybrid untuk seluruh karyawan (Tier kuota + Flat per closing + Bonus tembus target).',
                'is_active' => true,
                'auto_disburse' => true,
                'created_by' => auth()->id(),
            ]);

            $programs = TargetMarketProgram::where('period_month', $month)
                ->where('period_year', $year)
                ->get();
        }

        // Ambil logs untuk periode ini
        $logs = TargetMarketLog::where('period_month', $month)
            ->where('period_year', $year)
            ->with(['user', 'customer.package', 'customer.areaModel', 'program', 'incentive'])
            ->orderByDesc('id')
            ->get();

        // Ringkasan KPI Global
        $totalBonusPeriod = (float) $logs->sum('total_reward');
        $totalClosings = $logs->count();
        $totalEmployeesParticipating = $logs->pluck('user_id')->unique()->count();
        $activeProgramsCount = $programs->where('is_active', true)->count();

        // Bangun data Leaderboard
        $targetQuota = $programs->where('is_active', true)->max('target_quota') ?: 15;
        $rewardBonusTarget = $programs->where('is_active', true)->max('reward_bonus_target') ?: 0;

        $userGroups = $logs->groupBy('user_id');
        $leaderboard = $userGroups->map(function ($items, $userId) use ($targetQuota) {
            $user = $items->first()->user;
            $count = $items->count();
            $percentage = $targetQuota > 0 ? min(100, round(($count / $targetQuota) * 100)) : 0;
            $isAchieved = $count >= $targetQuota;

            return [
                'user_id' => $userId,
                'user_name' => $user?->name ?? 'Unknown',
                'user_email' => $user?->email ?? '-',
                'user_role' => $user?->role ?? 'karyawan',
                'closings_count' => $count,
                'target_quota' => $targetQuota,
                'percentage' => $percentage,
                'is_achieved' => $isAchieved,
                'base_reward' => (float) $items->sum('base_reward'),
                'tier_reward' => (float) $items->sum('tier_reward'),
                'area_reward' => (float) $items->sum('area_reward'),
                'package_reward' => (float) $items->sum('package_reward'),
                'target_achieved_reward' => (float) $items->sum('target_achieved_reward'),
                'total_reward' => (float) $items->sum('total_reward'),
                'is_synced' => $items->every(fn($i) => $i->status === 'synced_payroll'),
            ];
        })->sortByDesc('closings_count')->values();

        // Master data untuk modal
        $areas = Area::select('id', 'name')->orderBy('name')->get();
        $packages = InternetPackage::select('id', 'name', 'price', 'speed_mbps')->orderBy('price')->get();
        $employees = User::select('id', 'name', 'role')->orderBy('name')->get();

        return Inertia::render('TargetMarket/Index', [
            'month' => $month,
            'year' => $year,
            'tab' => $tab,
            'programs' => $programs,
            'logs' => $logs,
            'leaderboard' => $leaderboard,
            'kpi' => [
                'total_bonus' => $totalBonusPeriod,
                'total_closings' => $totalClosings,
                'total_employees' => $totalEmployeesParticipating,
                'active_programs' => $activeProgramsCount,
                'target_quota' => $targetQuota,
                'reward_bonus_target' => $rewardBonusTarget,
            ],
            'master' => [
                'areas' => $areas,
                'packages' => $packages,
                'employees' => $employees,
            ],
        ]);
    }

    public function storeProgram(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'concept' => 'required|in:milestone_volume,area_expansion,package_tier,hybrid',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2035',
            'target_quota' => 'required|integer|min:1',
            'target_role' => 'nullable|string',
            'reward_flat' => 'required|numeric|min:0',
            'reward_bonus_target' => 'nullable|numeric|min:0',
            'rules' => 'nullable|array',
            'description' => 'nullable|string',
            'auto_disburse' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = true;
        $validated['reward_bonus_target'] = $validated['reward_bonus_target'] ?? 0;

        $program = TargetMarketProgram::create($validated);

        // Langsung lakukan kalkulasi ulang agar aktivasi di bulan ini otomatis terdeteksi
        TargetMarketService::recalculatePeriod($program->period_month, $program->period_year);

        return back()->with('success', "Program target market '{$program->title}' berhasil dibuat dan dihitung otomatis.");
    }

    public function updateProgram(Request $request, TargetMarketProgram $program)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'concept' => 'required|in:milestone_volume,area_expansion,package_tier,hybrid',
            'target_quota' => 'required|integer|min:1',
            'target_role' => 'nullable|string',
            'reward_flat' => 'required|numeric|min:0',
            'reward_bonus_target' => 'nullable|numeric|min:0',
            'rules' => 'nullable|array',
            'description' => 'nullable|string',
            'auto_disburse' => 'boolean',
        ]);

        $validated['reward_bonus_target'] = $validated['reward_bonus_target'] ?? 0;
        $program->update($validated);

        TargetMarketService::recalculatePeriod($program->period_month, $program->period_year);

        return back()->with('success', "Program target market '{$program->title}' berhasil diperbarui.");
    }

    public function toggleProgram(TargetMarketProgram $program)
    {
        $program->is_active = !$program->is_active;
        $program->save();

        TargetMarketService::recalculatePeriod($program->period_month, $program->period_year);

        $statusStr = $program->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Program '{$program->title}' berhasil {$statusStr}.");
    }

    public function destroyProgram(TargetMarketProgram $program)
    {
        $title = $program->title;
        $month = $program->period_month;
        $year = $program->period_year;

        $program->delete();

        TargetMarketService::recalculatePeriod($month, $year);

        return back()->with('success', "Program '{$title}' berhasil dihapus.");
    }

    public function recalculate(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $result = TargetMarketService::recalculatePeriod($month, $year);

        $nominalFormatted = number_format($result['total_bonus'], 0, ',', '.');
        return back()->with('success', "Berhasil menghitung ulang {$result['processed']} closing pelanggan. Total bonus terhitung: Rp {$nominalFormatted}.");
    }

    public function syncToPayroll(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $result = TargetMarketService::syncToPayroll($month, $year);

        if ($result['synced_users'] === 0) {
            return back()->with('info', 'Tidak ada data bonus baru yang perlu disinkronkan ke Payroll.');
        }

        $nominalFormatted = number_format($result['total_amount'], 0, ',', '.');
        return back()->with('success', "Berhasil mentransfer bonus {$result['synced_users']} karyawan ke modul Insentif Payroll (Total: Rp {$nominalFormatted}). Bonus akan otomatis cair saat penggajian!");
    }
}
