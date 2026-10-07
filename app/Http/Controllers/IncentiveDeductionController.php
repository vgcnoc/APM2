<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Incentive;
use App\Models\Deduction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class IncentiveDeductionController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'semua');
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $search = $request->query('search', '');
        
        $incentivesQuery = Incentive::with('user')
            ->whereMonth('incentive_date', $month)
            ->whereYear('incentive_date', $year);
            
        $deductionsQuery = Deduction::with('user')
            ->whereMonth('deduction_date', $month)
            ->whereYear('deduction_date', $year);

        if ($search) {
            $incentivesQuery->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('description', 'like', "%{$search}%");
            
            $deductionsQuery->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('description', 'like', "%{$search}%");
        }

        $incentives = $incentivesQuery->get()->map(function ($item) {
            return [
                'id' => 'inc_' . $item->id,
                'real_id' => $item->id,
                'user_name' => $item->user->name ?? 'Unknown',
                'record_type' => 'Insentif',
                'mode' => ucfirst($item->type),
                'amount' => $item->amount,
                'description' => $item->description,
                'date' => $item->incentive_date,
                'status' => $item->status,
                'created_at' => $item->created_at,
            ];
        });

        $deductions = $deductionsQuery->get()->map(function ($item) {
            return [
                'id' => 'ded_' . $item->id,
                'real_id' => $item->id,
                'user_name' => $item->user->name ?? 'Unknown',
                'record_type' => 'Potongan',
                'mode' => ucfirst($item->type),
                'amount' => $item->amount,
                'description' => $item->description,
                'date' => $item->deduction_date,
                'status' => $item->status,
                'created_at' => $item->created_at,
            ];
        });

        $allRecords = collect();
        if ($tab === 'semua') {
            $allRecords = $incentives->concat($deductions);
        } elseif ($tab === 'insentif') {
            $allRecords = $incentives;
        } elseif ($tab === 'potongan') {
            $allRecords = $deductions;
        } elseif ($tab === 'auto') {
            $allRecords = $incentives->where('mode', 'Auto')->concat($deductions->where('mode', 'Auto'));
        } elseif ($tab === 'manual') {
            $allRecords = $incentives->where('mode', 'Manual')->concat($deductions->where('mode', 'Manual'));
        }

        $allRecords = $allRecords->sortByDesc('created_at')->values();

        // Calculate KPI
        $totalIncentive = $incentives->sum('amount');
        $totalDeduction = $deductions->sum('amount');
        $countAuto = $incentives->where('mode', 'Auto')->count() + $deductions->where('mode', 'Auto')->count();
        $countManual = $incentives->where('mode', 'Manual')->count() + $deductions->where('mode', 'Manual')->count();

        $perPage = 15;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginated = new LengthAwarePaginator(
            $allRecords->forPage($page, $perPage)->values(),
            $allRecords->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Payroll/IncentivesDeductions', [
            'records' => $paginated,
            'kpi' => [
                'totalIncentive' => $totalIncentive,
                'totalDeduction' => $totalDeduction,
                'countAuto' => $countAuto,
                'countManual' => $countManual,
            ],
            'filters' => $request->only(['tab', 'month', 'year', 'search']),
            'users' => $users,
        ]);
    }

    public function destroyRecord(Request $request)
    {
        $id = $request->id;
        if (str_starts_with($id, 'inc_')) {
            $realId = str_replace('inc_', '', $id);
            Incentive::where('id', $realId)->where('status', 'pending')->delete();
        } elseif (str_starts_with($id, 'ded_')) {
            $realId = str_replace('ded_', '', $id);
            Deduction::where('id', $realId)->where('status', 'pending')->delete();
        }
        return back()->with('success', 'Data berhasil dihapus.');
    }
}
