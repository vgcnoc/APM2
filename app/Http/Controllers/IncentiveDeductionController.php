<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Incentive;
use App\Models\Deduction;
use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class IncentiveDeductionController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'semua');
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $search = $request->query('search', '');
        $areaId = $request->query('area_id', '');
        
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));

        $incentivesQuery = Incentive::with(['user.area'])
            ->whereHas('user', fn($q) => $q->excludeTokoAndDemo())
            ->whereMonth('incentive_date', $month)
            ->whereYear('incentive_date', $year);
            
        $deductionsQuery = Deduction::with(['user.area'])
            ->whereHas('user', fn($q) => $q->excludeTokoAndDemo())
            ->whereMonth('deduction_date', $month)
            ->whereYear('deduction_date', $year);

        // Scope per karyawan jika bukan admin dan tidak memiliki payroll_view_all
        if (!$isSuperAdmin && !$authUser->can('payroll_view_all')) {
            if ($authUser->can('payroll_view_area') && $authUser->area_id) {
                $incentivesQuery->whereHas('user', fn($q) => $q->where('area_id', $authUser->area_id));
                $deductionsQuery->whereHas('user', fn($q) => $q->where('area_id', $authUser->area_id));
            } else {
                $incentivesQuery->where('user_id', $authUser->id);
                $deductionsQuery->where('user_id', $authUser->id);
            }
        }

        if ($areaId) {
            $incentivesQuery->whereHas('user', function($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
            $deductionsQuery->whereHas('user', function($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
        }

        if ($search) {
            $incentivesQuery->where(function($query) use ($search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('description', 'like', "%{$search}%");
            });
            
            $deductionsQuery->where(function($query) use ($search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('description', 'like', "%{$search}%");
            });
        }

        $cleanDescription = function ($desc) {
            if (!$desc) return '';
            return trim(preg_replace('/\s*\([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)/i', '', $desc));
        };

        $incentives = $incentivesQuery->get()->map(function ($item) use ($cleanDescription) {
            return [
                'id' => 'inc_' . $item->id,
                'real_id' => $item->id,
                'user_name' => $item->user->name ?? 'Unknown',
                'area_name' => $item->user?->area?->name ?? 'Pusat / Semua Area',
                'record_type' => 'Insentif',
                'mode' => ucfirst($item->type),
                'amount' => $item->amount,
                'description' => $cleanDescription($item->description),
                'date' => $item->incentive_date,
                'status' => $item->status,
                'created_at' => $item->created_at,
            ];
        });

        $deductions = $deductionsQuery->get()->map(function ($item) use ($cleanDescription) {
            return [
                'id' => 'ded_' . $item->id,
                'real_id' => $item->id,
                'user_name' => $item->user->name ?? 'Unknown',
                'area_name' => $item->user?->area?->name ?? 'Pusat / Semua Area',
                'record_type' => 'Potongan',
                'mode' => ucfirst($item->type),
                'amount' => $item->amount,
                'description' => $cleanDescription($item->description),
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

        $excludedRoles = ['customer', 'pelanggan', 'reseller', 'superadmin', 'super admin', 'toko', 'demo'];
        $usersQuery = User::with('area')->where('is_active', true)
            ->excludeTokoAndDemo()
            ->whereNotIn('role', $excludedRoles)
            ->whereDoesntHave('roles', function($q) use ($excludedRoles) {
                $q->whereIn('name', $excludedRoles);
            })
            ->whereDoesntHave('customer')
            ->whereDoesntHave('reseller')
            ->orderBy('name');

        if (!$isSuperAdmin && !$authUser->can('payroll_view_all')) {
            if ($authUser->can('payroll_view_area') && $authUser->area_id) {
                $usersQuery->where('area_id', $authUser->area_id);
            } else {
                $usersQuery->where('id', $authUser->id);
            }
        }
        $users = $usersQuery->get(['id', 'name', 'area_id']);
        $categories = \App\Models\PayrollCategory::where('is_active', true)->get();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Payroll/IncentivesDeductions', [
            'records' => $paginated,
            'kpi' => [
                'totalIncentive' => $totalIncentive,
                'totalDeduction' => $totalDeduction,
                'countAuto' => $countAuto,
                'countManual' => $countManual,
            ],
            'counts' => [
                'semua' => $incentives->count() + $deductions->count(),
                'insentif' => $incentives->count(),
                'potongan' => $deductions->count(),
                'auto' => $countAuto,
                'manual' => $countManual,
            ],
            'filters' => $request->only(['tab', 'month', 'year', 'search', 'area_id']),
            'users' => $users,
            'payrollCategories' => $categories,
            'areas' => $areas,
        ]);
    }

    public function destroyRecord(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));
        if (!$isSuperAdmin && !$authUser->can('payroll_incentives') && !$authUser->can('payroll_incentives_delete') && !$authUser->can('payroll_manage')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data insentif/potongan.');
        }

        $id = $request->id;
        $deleted = false;
        if (str_starts_with($id, 'inc_')) {
            $realId = str_replace('inc_', '', $id);
            $deleted = Incentive::where('id', $realId)->where('status', 'pending')->delete();
            if ($deleted) {
                \App\Models\TargetMarketLog::where('incentive_id', $realId)->update([
                    'status' => 'approved',
                    'incentive_id' => null,
                ]);
            }
        } elseif (str_starts_with($id, 'ded_')) {
            $realId = str_replace('ded_', '', $id);
            $deleted = Deduction::where('id', $realId)->where('status', 'pending')->delete();
        }

        if ($deleted) {
            return back()->with('success', 'Data insentif/potongan berhasil dihapus.');
        }

        return back()->with('error', 'Gagal menghapus data atau data sudah dicairkan ke payroll.');
    }

    /**
     * Pencairan Insentif Manual Langsung ke Kas Operasional (Buku Kas Umum)
     */
    public function disburseIncentive(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));
        $userRole = strtolower(trim($authUser->role ?? ''));
        $hasAllowedRole = in_array($userRole, ['admin', 'superadmin', 'super admin', 'hrd', 'finance']) 
            || ($authUser->hasAnyRole(['admin', 'superadmin', 'hrd', 'finance']));

        if (!$isSuperAdmin && !$hasAllowedRole && !$authUser->can('payroll_incentives_disburse')) {
            abort(403, 'Akses ditolak. Fitur pencairan insentif hanya dapat diakses oleh akun HRD dan Finance.');
        }

        $ids = $request->input('ids');
        if (empty($ids)) {
            $singleId = $request->input('id');
            if ($singleId) {
                $ids = [$singleId];
            }
        }

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'Pilih minimal satu data insentif yang akan dicairkan.');
        }

        $realIds = array_values(array_filter(array_map(function ($id) {
            if (is_numeric($id)) return (int) $id;
            if (is_string($id) && str_starts_with($id, 'inc_')) {
                return (int) str_replace('inc_', '', $id);
            }
            return null;
        }, $ids)));

        if (empty($realIds)) {
            return back()->with('error', 'Hanya data bertipe Insentif yang dapat dicairkan.');
        }

        return DB::transaction(function () use ($realIds, $authUser) {
            $incentives = Incentive::with('user')
                ->whereIn('id', $realIds)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            if ($incentives->isEmpty()) {
                return back()->with('error', 'Tidak ada data insentif pending yang dapat dicairkan (mungkin sudah dicairkan sebelumnya).');
            }

            // Resolve Expense Category (misal 'Operasional Lapangan', 'Insentif', dll)
            $category = ExpenseCategory::where('is_active', true)
                ->where(function($q) {
                    $q->where('name', 'like', '%insentif%')
                      ->orWhere('name', 'like', '%operasional%')
                      ->orWhere('name', 'like', '%transport%')
                      ->orWhere('name', 'like', '%gaji%');
                })
                ->orderByRaw("CASE 
                    WHEN name LIKE '%insentif%' THEN 1 
                    WHEN name LIKE '%operasional%' THEN 2 
                    WHEN name LIKE '%transport%' THEN 3 
                    ELSE 4 END")
                ->first();

            $totalDisbursed = 0;
            $count = 0;

            foreach ($incentives as $inc) {
                $user = $inc->user;
                if ($user && $user->isExemptFromAttendanceAndPayroll()) {
                    continue;
                }

                $amount = (float) $inc->amount;
                if ($amount <= 0) continue;

                $userName = $user?->name ?? 'Staf';
                $cleanDesc = $inc->description ? " - {$inc->description}" : "";
                $dateStr = $inc->incentive_date ? \Carbon\Carbon::parse($inc->incentive_date)->format('d/m/Y') : date('d/m/Y');
                $expenseDesc = "Pencairan Insentif {$userName}{$cleanDesc} (Tgl: {$dateStr})";

                $expense = Expense::create([
                    'amount' => $amount,
                    'expense_category_id' => $category?->id,
                    'description' => $expenseDesc,
                    'expense_date' => now()->toDateString(),
                    'user_id' => $authUser->id,
                ]);

                $inc->update([
                    'status' => 'paid',
                    'reference_id' => $expense ? 'exp_' . $expense->id : $inc->reference_id,
                ]);

                $totalDisbursed += $amount;
                $count++;
            }

            if ($count === 0) {
                return back()->with('error', 'Tidak ada insentif yang berhasil dicairkan.');
            }

            $formattedTotal = number_format($totalDisbursed, 0, ',', '.');
            return back()->with('success', "Berhasil mencairkan {$count} insentif dengan total Rp {$formattedTotal} ke Buku Kas Umum.");
        });
    }
}
