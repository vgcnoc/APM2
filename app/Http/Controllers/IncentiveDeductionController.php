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
        $userId = $request->query('user_id', '');
        $type = $request->query('type', '');         // 'insentif' | 'potongan'
        $mode = $request->query('mode', '');         // 'auto' | 'manual'
        $status = $request->query('status', '');     // 'pending' | 'disbursed'
        $category = $request->query('category', ''); // 'psb', 'survey', 'booking', 'izin', etc.
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');
        
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));

        $incentivesQuery = Incentive::with(['user.area'])
            ->whereHas('user', fn($q) => $q->excludeTokoAndDemo());
            
        $deductionsQuery = Deduction::with(['user.area'])
            ->whereHas('user', fn($q) => $q->excludeTokoAndDemo());

        // 1. Filter Tanggal (Rentang Spesifik atau Bulan/Tahun)
        if (!empty($startDate) && !empty($endDate)) {
            $incentivesQuery->whereBetween('incentive_date', [$startDate, $endDate]);
            $deductionsQuery->whereBetween('deduction_date', [$startDate, $endDate]);
        } else {
            if ($month) {
                $incentivesQuery->whereMonth('incentive_date', $month);
                $deductionsQuery->whereMonth('deduction_date', $month);
            }
            if ($year) {
                $incentivesQuery->whereYear('incentive_date', $year);
                $deductionsQuery->whereYear('deduction_date', $year);
            }
        }

        // 2. Scope per karyawan jika bukan admin dan tidak memiliki payroll_view_all
        if (!$isSuperAdmin && !$authUser->can('payroll_view_all')) {
            if ($authUser->can('payroll_view_area') && $authUser->area_id) {
                $incentivesQuery->whereHas('user', fn($q) => $q->where('area_id', $authUser->area_id));
                $deductionsQuery->whereHas('user', fn($q) => $q->where('area_id', $authUser->area_id));
            } else {
                $incentivesQuery->where('user_id', $authUser->id);
                $deductionsQuery->where('user_id', $authUser->id);
            }
        }

        // 3. Filter Spesifik Karyawan
        if (!empty($userId)) {
            $incentivesQuery->where('user_id', $userId);
            $deductionsQuery->where('user_id', $userId);
        }

        // 4. Filter Area / Wilayah
        if (!empty($areaId)) {
            $incentivesQuery->whereHas('user', function($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
            $deductionsQuery->whereHas('user', function($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
        }

        // 5. Filter Mode (Otomatis / Manual)
        $effectiveMode = $mode ?: (in_array($tab, ['auto', 'manual']) ? $tab : '');
        if (!empty($effectiveMode)) {
            $incentivesQuery->where('type', strtolower($effectiveMode));
            $deductionsQuery->where('type', strtolower($effectiveMode));
        }

        // 6. Filter Status Pencairan
        $effectiveStatus = $status ?: (in_array($tab, ['pending', 'disbursed']) ? $tab : '');
        if (!empty($effectiveStatus)) {
            if ($effectiveStatus === 'pending') {
                $incentivesQuery->where('status', 'pending');
                $deductionsQuery->where(function($q) {
                    $q->where('status', 'pending')->orWhereNull('status');
                });
            } elseif ($effectiveStatus === 'disbursed') {
                $incentivesQuery->whereIn('status', ['disbursed', 'approved', 'paid']);
                $deductionsQuery->whereIn('status', ['disbursed', 'approved', 'paid']);
            }
        }

        // 7. Filter Kategori Biaya / Transaksi
        if (!empty($category)) {
            $catKeyword = match($category) {
                'psb' => 'Fee Pasang',
                'survey' => 'Fee Survey',
                'booking' => 'Fee Booking',
                'weekly' => 'UANG MINGGUAN',
                'transport' => 'UANG TRANSPORT',
                'izin' => 'Izin Tidak Masuk',
                'sakit' => 'Sakit Tanpa Surat',
                'late' => 'Terlambat',
                'alpha' => 'Alpha',
                'special' => 'TIM SERBU',
                'kasbon' => 'Kasbon',
                default => $category,
            };
            $incentivesQuery->where('description', 'like', "%{$catKeyword}%");
            $deductionsQuery->where('description', 'like', "%{$catKeyword}%");
        }

        // 8. Pencarian Teks Bebas
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
                'user_id' => $item->user_id,
                'user_name' => $item->user->name ?? 'Unknown',
                'area_name' => $item->user?->area?->name ?? 'Pusat / Semua Area',
                'record_type' => 'Insentif',
                'mode' => ucfirst($item->type),
                'amount' => $item->amount,
                'description' => $cleanDescription($item->description),
                'date' => $item->incentive_date,
                'status' => $item->status,
                'disbursed_at' => $item->disbursed_at ? \Carbon\Carbon::parse($item->disbursed_at)->toDateString() : null,
                'created_at' => $item->created_at,
            ];
        });

        $deductions = $deductionsQuery->get()->map(function ($item) use ($cleanDescription) {
            return [
                'id' => 'ded_' . $item->id,
                'real_id' => $item->id,
                'user_id' => $item->user_id,
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

        // 9. Tab & Type Resolution
        $effectiveType = $type ?: ($tab === 'insentif' ? 'insentif' : ($tab === 'potongan' ? 'potongan' : ''));

        $allRecords = collect();
        if ($effectiveType === 'insentif') {
            $allRecords = $incentives;
        } elseif ($effectiveType === 'potongan') {
            $allRecords = $deductions;
        } else {
            $allRecords = $incentives->concat($deductions);
        }

        // Filter tab khusus jika tab auto / manual / pending / disbursed dipilih
        if ($tab === 'auto') {
            $allRecords = $allRecords->where('mode', 'Auto');
        } elseif ($tab === 'manual') {
            $allRecords = $allRecords->where('mode', 'Manual');
        } elseif ($tab === 'pending') {
            $allRecords = $allRecords->filter(fn($r) => $r['status'] === 'pending' || empty($r['status']));
        } elseif ($tab === 'disbursed') {
            $allRecords = $allRecords->filter(fn($r) => in_array($r['status'], ['disbursed', 'approved', 'paid']));
        }

        $allRecords = $allRecords->sortByDesc('created_at')->values();

        // 10. Calculate KPI & Counts
        $countInsentif = $incentives->count();
        $countPotongan = $deductions->count();
        $countSemua = $countInsentif + $countPotongan;

        $totalIncentive = $incentives->sum('amount');
        $totalDeduction = $deductions->sum('amount');
        $countAuto = $incentives->where('mode', 'Auto')->count() + $deductions->where('mode', 'Auto')->count();
        $countManual = $incentives->where('mode', 'Manual')->count() + $deductions->where('mode', 'Manual')->count();
        
        $countPending = $incentives->where('status', 'pending')->count() + $deductions->filter(fn($d) => $d['status'] === 'pending' || empty($d['status']))->count();
        $countDisbursed = $incentives->filter(fn($i) => in_array($i['status'], ['disbursed', 'approved', 'paid']))->count()
            + $deductions->filter(fn($d) => in_array($d['status'], ['disbursed', 'approved', 'paid']))->count();

        $totalPendingIncentive = $incentives->where('status', 'pending')->sum('amount');
        $totalDisbursedIncentive = $incentives->filter(fn($i) => in_array($i['status'], ['disbursed', 'approved', 'paid']))->sum('amount');

        $perPage = 15;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginated = new LengthAwarePaginator(
            $allRecords->forPage($page, $perPage)->values(),
            $allRecords->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $existingUserIds = Incentive::whereMonth('incentive_date', $month)->whereYear('incentive_date', $year)->pluck('user_id')
            ->concat(Deduction::whereMonth('deduction_date', $month)->whereYear('deduction_date', $year)->pluck('user_id'))
            ->unique()->filter()->values();

        $excludedRoles = ['customer', 'pelanggan', 'reseller', 'superadmin', 'super admin', 'toko', 'demo', 'affiliate', 'mitra', 'affiliator'];
        $usersQuery = User::forCurrentCompany()->with('area')->where('is_active', true)
            ->excludeTokoAndDemo()
            ->where(function($q) use ($excludedRoles, $existingUserIds) {
                $q->where(function($sub) use ($excludedRoles) {
                    $sub->whereNotIn('role', $excludedRoles)
                        ->whereDoesntHave('roles', function($rq) use ($excludedRoles) {
                            $rq->whereIn('name', $excludedRoles);
                        })
                        ->whereDoesntHave('customer')
                        ->whereDoesntHave('reseller')
                        ->where(function($sq) {
                            $sq->where('role', '!=', 'sales')
                              ->orWhere('base_salary', '>', 0)
                              ->orWhereHas('employee')
                              ->orWhereHas('payrolls');
                        });
                })->orWhereIn('id', $existingUserIds);
            })
            ->orderBy('name');

        if (!$isSuperAdmin && !$authUser->can('payroll_view_all')) {
            if ($authUser->can('payroll_view_area') && $authUser->area_id) {
                $usersQuery->where('area_id', $authUser->area_id);
            } else {
                $usersQuery->where('id', $authUser->id);
            }
        }
        $users = $usersQuery->get(['id', 'name', 'area_id', 'role']);
        $categories = \App\Models\PayrollCategory::where('is_active', true)->get();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        $availableCategories = [
            ['id' => 'psb', 'name' => 'Fee Pasang Pelanggan (PSB)'],
            ['id' => 'survey', 'name' => 'Fee Survey Pelanggan'],
            ['id' => 'booking', 'name' => 'Fee Booking Pelanggan'],
            ['id' => 'weekly', 'name' => 'Uang Mingguan'],
            ['id' => 'transport', 'name' => 'Uang Transport'],
            ['id' => 'izin', 'name' => 'Potongan Izin Tidak Masuk'],
            ['id' => 'sakit', 'name' => 'Potongan Sakit Tanpa Surat Dokter'],
            ['id' => 'late', 'name' => 'Potongan Terlambat'],
            ['id' => 'alpha', 'name' => 'Potongan Alpha / Mangkir'],
            ['id' => 'special', 'name' => 'Potongan Absen Tim Khusus / Serbu'],
            ['id' => 'kasbon', 'name' => 'Kasbon / Pinjaman'],
        ];

        return Inertia::render('Payroll/IncentivesDeductions', [
            'records' => $paginated,
            'kpi' => [
                'totalIncentive' => $totalIncentive,
                'totalDeduction' => $totalDeduction,
                'countAuto' => $countAuto,
                'countManual' => $countManual,
                'totalPendingIncentive' => $totalPendingIncentive,
                'totalDisbursedIncentive' => $totalDisbursedIncentive,
                'countPending' => $countPending,
                'countDisbursed' => $countDisbursed,
            ],
            'counts' => [
                'semua' => $countSemua,
                'insentif' => $countInsentif,
                'potongan' => $countPotongan,
                'auto' => $countAuto,
                'manual' => $countManual,
                'pending' => $countPending,
                'disbursed' => $countDisbursed,
            ],
            'filters' => $request->only([
                'tab', 'month', 'year', 'search', 'area_id', 'user_id', 
                'type', 'mode', 'status', 'category', 'start_date', 'end_date'
            ]),
            'users' => $users,
            'payrollCategories' => $categories,
            'availableCategories' => $availableCategories,
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

        // Tanggal pencairan kas yang dipilih user (default: hari ini)
        $disburseDateInput = $request->input('disburse_date');
        $disburseDate = now()->toDateString();
        if (!empty($disburseDateInput)) {
            try {
                $disburseDate = \Carbon\Carbon::parse($disburseDateInput)->toDateString();
            } catch (\Throwable $e) {
                $disburseDate = now()->toDateString();
            }
        }

        // Cek ketat jika ada data yang sudah dicairkan sebelumnya agar tidak bisa dicairkan ulang
        $alreadyPaid = Incentive::whereIn('id', $realIds)->where('status', '!=', 'pending')->get();
        if ($alreadyPaid->isNotEmpty()) {
            if (count($realIds) === 1) {
                $paidItem = $alreadyPaid->first();
                $cairInfo = $paidItem->disbursed_at ? ' pada tanggal ' . \Carbon\Carbon::parse($paidItem->disbursed_at)->format('d/m/Y') : '';
                return back()->with('error', "Insentif ini sudah dicairkan sebelumnya{$cairInfo} dan tidak dapat dicairkan ulang.");
            }
        }

        return DB::transaction(function () use ($realIds, $authUser, $disburseDate) {
            $incentives = Incentive::with('user')
                ->whereIn('id', $realIds)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            if ($incentives->isEmpty()) {
                return back()->with('error', 'Semua data insentif yang dipilih sudah dicairkan sebelumnya atau tidak berstatus pending.');
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
            $cairDateStr = \Carbon\Carbon::parse($disburseDate)->format('d/m/Y');

            foreach ($incentives as $inc) {
                // Perlindungan ganda: jika status bukan pending, skip
                if ($inc->status !== 'pending') {
                    continue;
                }

                $user = $inc->user;
                if ($user && $user->isExemptFromAttendanceAndPayroll()) {
                    continue;
                }

                $amount = (float) $inc->amount;
                if ($amount <= 0) continue;

                $userName = $user?->name ?? 'Staf';
                $cleanDesc = $inc->description ? " - {$inc->description}" : "";
                $dateStr = $inc->incentive_date ? \Carbon\Carbon::parse($inc->incentive_date)->format('d/m/Y') : date('d/m/Y');
                $expenseDesc = "Pencairan Insentif {$userName}{$cleanDesc} (Periode: {$dateStr}, Dicairkan: {$cairDateStr})";

                $expense = Expense::create([
                    'amount' => $amount,
                    'expense_category_id' => $category?->id,
                    'description' => $expenseDesc,
                    'expense_date' => $disburseDate,
                    'user_id' => $authUser->id,
                ]);

                $inc->update([
                    'status' => 'paid',
                    'disbursed_at' => $disburseDate,
                    'reference_id' => $inc->reference_id ?: ($expense ? 'exp_' . $expense->id : null),
                ]);

                \App\Models\TargetMarketLog::where('incentive_id', $inc->id)->update([
                    'status' => 'paid',
                ]);

                $totalDisbursed += $amount;
                $count++;
            }

            if ($count === 0) {
                return back()->with('error', 'Tidak ada insentif yang berhasil dicairkan (mungkin sudah dicairkan sebelumnya).');
            }

            $formattedTotal = number_format($totalDisbursed, 0, ',', '.');
            return back()->with('success', "Berhasil mencairkan {$count} insentif dengan total Rp {$formattedTotal} ke Buku Kas Umum pada tanggal {$cairDateStr}.");
        });
    }
}
