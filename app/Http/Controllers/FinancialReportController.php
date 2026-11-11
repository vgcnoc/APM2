<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\MaterialTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Check if custom date range is provided
        if (!empty($dateFrom) && !empty($dateTo)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = Carbon::parse($dateTo)->endOfDay();
        } elseif (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        } elseif (!empty($dateTo)) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::parse($dateTo)->endOfDay();
        } else {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        }

        // 1. Grand Summary Cards (Period Selected)
        
        // Pemasukan: Pembayaran tagihan terverifikasi + Pemasukan Kas Lainnya
        $paymentsThisMonth = Payment::with(['invoice.customer.areaModel', 'customer.areaModel', 'collector'])
            ->where('status', 'verified')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();
            
        $omzetCustomer = (float) $paymentsThisMonth->filter(function($p) {
            return $p->invoice && !$p->invoice->is_reseller_balance;
        })->sum('amount');
        
        $omzetReseller = (float) $paymentsThisMonth->filter(function($p) {
            return $p->invoice && $p->invoice->is_reseller_balance;
        })->sum('amount');

        // Pemasukan Lain-lain / Non-Tagihan (Manual Incomes)
        $incomesThisMonth = Income::with(['creator', 'category', 'areaModel'])
            ->whereBetween('income_date', [$startDate, $endDate])
            ->get();
        $omzetManual = (float) $incomesThisMonth->sum('amount');
        
        $totalOmzet = $omzetCustomer + $omzetReseller + $omzetManual;

        // Pengeluaran
        $totalPengeluaran = (float) Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        // Rincian Pengeluaran: Belanja Material (Order Toko) vs Operasional/Umum
        $pengeluaranMaterial = (float) Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where(function($q) {
                $q->whereHas('materialTransaction')
                  ->orWhereHas('category', fn($c) => $c->where('code', 'MAT'))
                  ->orWhere('description', 'like', '%Order Toko%');
            })
            ->sum('amount');

        $pengeluaranOperasional = max(0, $totalPengeluaran - $pengeluaranMaterial);

        // Laba Bersih
        $labaBersih = $totalOmzet - $totalPengeluaran;

        // Piutang Keseluruhan (Tunggakan Aktif + Kasbon yang belum lunas) - Eager load payments to prevent N+1 queries!
        $piutangCustomer = (float) Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', false)
            ->where('invoice_number', 'not like', 'INV-MIG-%')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where(function($q) use ($endDate) {
                $q->whereNull('issued_date')
                  ->orWhereDate('issued_date', '<=', $endDate);
            })
            ->whereHas('customer', function($q) {
                $q->whereNotIn('status', ['terminated', 'canceled'])
                  ->whereNotIn('service_status', ['gratis', 'free_home']);
            })
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments->sum('amount');
                return max(0, $inv->amount - $paid);
            });
            
        $piutangReseller = (float) Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', true)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereHas('customer', fn($c) => $c->where('is_reseller', true))
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments->sum('amount');
                return max(0, $inv->amount - $paid);
            });

        // Piutang Pelanggan Stop Permanen (Bad Debt)
        $piutangTerminated = (float) Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', false)
            ->where('invoice_number', 'not like', 'INV-MIG-%')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where(function($q) use ($endDate) {
                $q->whereNull('issued_date')
                  ->orWhereDate('issued_date', '<=', $endDate);
            })
            ->whereHas('customer', function($q) {
                $q->where('status', 'terminated')
                  ->whereNotIn('service_status', ['gratis', 'free_home']);
            })
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments->sum('amount');
                return max(0, $inv->amount - $paid);
            });
            
        $totalPiutang = $piutangCustomer + $piutangReseller;

        // 2. Grafik Pertumbuhan Bulanan (6 Bulan Terakhir)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mDate = now()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth();
            $mEnd = $mDate->copy()->endOfMonth();
            
            $pBln = Payment::with('invoice')
                ->where('status', 'verified')
                ->whereBetween('payment_date', [$mStart, $mEnd])
                ->get();
                
            $cCust = (float) $pBln->filter(fn($p) => $p->invoice && !$p->invoice->is_reseller_balance)->sum('amount');
            $cRes = (float) $pBln->filter(fn($p) => $p->invoice && $p->invoice->is_reseller_balance)->sum('amount');
            $cManual = (float) Income::whereBetween('income_date', [$mStart, $mEnd])->sum('amount');
            $cExp = (float) Expense::whereBetween('expense_date', [$mStart, $mEnd])->sum('amount');
            
            $chartData[] = [
                'month' => $mDate->translatedFormat('M Y'),
                'customer' => $cCust,
                'reseller' => $cRes,
                'manual_income' => $cManual,
                'total_income' => $cCust + $cRes + $cManual,
                'expense' => $cExp
            ];
        }

        // 3. Buku Kas Umum (Unified Timeline)
        // Combine Payments (Income) and Expenses (Outcome)
        $ledger = collect([]);
        
        foreach ($paymentsThisMonth as $p) {
            $type = ($p->invoice && $p->invoice->is_reseller_balance) ? 'reseller' : 'customer';
            $custName = $p->customer?->name ?? ($p->invoice?->customer?->name ?? 'Pelanggan');
            $collectorName = $p->collector?->name ?? 'System';
            $custArea = $p->customer?->areaModel?->name ?? $p->customer?->area ?? ($p->invoice?->customer?->areaModel?->name ?? $p->invoice?->customer?->area ?? null);
            $custAreaId = $p->customer?->area_id ?? ($p->invoice?->customer?->area_id ?? null);

            $ledger->push([
                'id' => 'p_'.$p->id,
                'date' => Carbon::parse($p->payment_date)->format('Y-m-d H:i:s'),
                'type' => 'income',
                'category' => $type,
                'category_id' => null,
                'category_name' => $type === 'reseller' ? 'Reseller Voucher' : 'Pelanggan Reguler',
                'category_code' => $type === 'reseller' ? 'RSL' : 'CUS',
                'category_color' => 'emerald',
                'amount' => (float)$p->amount,
                'description' => "Pembayaran " . ($type === 'reseller' ? 'Reseller' : 'Pelanggan') . " ({$custName})" . ($p->notes ? " - {$p->notes}" : ""),
                'person' => $custName,
                'user' => $collectorName,
                'material_order' => null,
                'area_id' => $custAreaId,
                'area' => $custArea,
            ]);
        }

        foreach ($incomesThisMonth as $inc) {
            $catName = $inc->category ? $inc->category->name : 'Pemasukan Lainnya';
            $catCode = $inc->category ? $inc->category->code : 'INC';
            $catColor = $inc->category ? $inc->category->color : 'emerald';
            $incArea = $inc->area ?: ($inc->areaModel?->name ?: null);

            $ledger->push([
                'id' => 'inc_'.$inc->id,
                'income_id' => $inc->id,
                'date' => Carbon::parse($inc->income_date)->format('Y-m-d H:i:s'),
                'type' => 'income',
                'is_manual' => true,
                'category' => $inc->income_category_id ? (string)$inc->income_category_id : 'manual_income',
                'category_id' => $inc->income_category_id,
                'category_name' => $catName,
                'category_code' => $catCode,
                'category_color' => $catColor,
                'amount' => (float)$inc->amount,
                'description' => $inc->description,
                'source' => $inc->source,
                'payment_method' => $inc->payment_method,
                'reference_no' => $inc->reference_no,
                'person' => $inc->source ?: '-',
                'user' => $inc->creator ? $inc->creator->name : 'Admin',
                'material_order' => null,
                'area_id' => $inc->area_id,
                'area' => $incArea,
            ]);
        }
        
        $expensesThisMonth = Expense::with(['creator', 'category', 'areaModel', 'materialTransaction.items.material'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();

        foreach ($expensesThisMonth as $e) {
            $catName = $e->category ? $e->category->name : 'Operasional';
            $catCode = $e->category ? $e->category->code : null;
            $catColor = $e->category ? $e->category->color : 'rose';

            $po = $e->materialTransaction;
            // Fallback cari berdasarkan No PO di deskripsi jika relasi belum terpasang
            if (!$po && preg_match('/\[(PO-[^\]]+)\]/', $e->description, $matches)) {
                $po = MaterialTransaction::with('items.material')->where('transaction_number', $matches[1])->first();
                if ($po && !$po->expense_id) {
                    $po->update(['expense_id' => $e->id]);
                }
            }

            $materialDetails = null;
            if ($po) {
                $areaName = $po->technician_name ?: ($po->area ?: '-');
                if (($areaName === '-' || empty($areaName)) && preg_match('/Wilayah\s+([^:]+)/', $e->description, $aMatch)) {
                    $areaName = trim($aMatch[1]);
                }

                $materialDetails = [
                    'id' => $po->id,
                    'transaction_number' => $po->transaction_number,
                    'area_name' => $areaName,
                    'date' => $po->date ? Carbon::parse($po->date)->translatedFormat('d F Y') : Carbon::parse($po->created_at)->translatedFormat('d F Y'),
                    'notes' => $po->notes,
                    'total_cost' => (float)$po->total_cost,
                    'items_count' => $po->items ? $po->items->count() : 0,
                    'items' => $po->items ? $po->items->map(function($it) {
                        return [
                            'id' => $it->id,
                            'material_name' => $it->material ? $it->material->name : 'Item Material',
                            'category' => $it->material ? ($it->material->category ?: 'Umum') : 'Umum',
                            'quantity' => (float)$it->quantity,
                            'unit' => $it->unit ?: 'pcs',
                            'price_per_unit' => (float)$it->price_per_unit,
                            'total_price' => (float)$it->total_price,
                        ];
                    })->values()->all() : [],
                ];
            }

            $expAreaName = $e->area ?: ($e->areaModel?->name ?: ($materialDetails['area_name'] ?? null));
            $expAreaId = $e->area_id ?: ($po?->area_id ?? null);

            $ledger->push([
                'id' => 'e_'.$e->id,
                'expense_id' => $e->id,
                'date' => Carbon::parse($e->expense_date)->format('Y-m-d H:i:s'),
                'type' => 'expense',
                'category' => $materialDetails ? 'material' : ($e->expense_category_id ? (string)$e->expense_category_id : 'operational'),
                'category_id' => $e->expense_category_id,
                'category_name' => $materialDetails ? 'Belanja Material' : $catName,
                'category_code' => $materialDetails ? 'MAT' : $catCode,
                'category_color' => $materialDetails ? 'indigo' : $catColor,
                'amount' => (float)$e->amount,
                'description' => $e->description,
                'person' => '-',
                'user' => $e->creator ? $e->creator->name : 'Unknown',
                'material_order' => $materialDetails,
                'area_id' => $expAreaId,
                'area' => $expAreaName,
            ]);
        }
        
        // Sort ledger by date desc initially
        $ledger = $ledger->sortByDesc('date')->values();

        // 4. Filter Lengkap Buku Kas Umum
        $search = trim($request->get('search', ''));
        $filterType = $request->get('type', 'all'); // 'all', 'income', 'expense'
        $filterCategory = $request->get('category', 'all'); // 'all', 'customer', 'reseller', 'material', or category_id
        $filterArea = $request->get('area', 'all'); // 'all', or area_id / area_name

        if ($search !== '') {
            $searchLower = strtolower($search);
            $ledger = $ledger->filter(function($item) use ($searchLower) {
                $desc = strtolower($item['description'] ?? '');
                $person = strtolower($item['person'] ?? '');
                $user = strtolower($item['user'] ?? '');
                $poNum = strtolower($item['material_order']['transaction_number'] ?? '');
                $area = strtolower($item['area'] ?? ($item['material_order']['area_name'] ?? ''));
                $catName = strtolower($item['category_name'] ?? '');
                $catCode = strtolower($item['category_code'] ?? '');
                $source = strtolower($item['source'] ?? '');
                $refNo = strtolower($item['reference_no'] ?? '');

                return str_contains($desc, $searchLower)
                    || str_contains($person, $searchLower)
                    || str_contains($user, $searchLower)
                    || str_contains($poNum, $searchLower)
                    || str_contains($area, $searchLower)
                    || str_contains($catName, $searchLower)
                    || str_contains($catCode, $searchLower)
                    || str_contains($source, $searchLower)
                    || str_contains($refNo, $searchLower);
            });
        }

        if ($filterType !== 'all' && in_array($filterType, ['income', 'expense'])) {
            $ledger = $ledger->filter(fn($item) => $item['type'] === $filterType);
        }

        if ($filterCategory !== 'all' && $filterCategory !== '') {
            $ledger = $ledger->filter(function($item) use ($filterCategory) {
                if ($filterCategory === 'customer') {
                    return $item['type'] === 'income' && ($item['category'] ?? '') === 'customer';
                }
                if ($filterCategory === 'reseller') {
                    return $item['type'] === 'income' && ($item['category'] ?? '') === 'reseller';
                }
                if ($filterCategory === 'manual_income') {
                    return $item['type'] === 'income' && !empty($item['is_manual']);
                }
                if ($filterCategory === 'material') {
                    return !empty($item['material_order']) || ($item['category_code'] ?? '') === 'MAT' || $item['category'] === 'material';
                }
                // Match by category_id or category_code
                return ($item['category_id'] ?? null) == $filterCategory 
                    || ($item['category_code'] ?? null) == $filterCategory
                    || ($item['category'] ?? null) == $filterCategory;
            });
        }

        if ($filterArea !== 'all' && $filterArea !== '' && !empty($filterArea)) {
            $ledger = $ledger->filter(function($item) use ($filterArea) {
                return (string)($item['area_id'] ?? '') === (string)$filterArea 
                    || strtolower(trim($item['area'] ?? '')) === strtolower(trim($filterArea));
            });
        }

        // Subtotal calculated for filtered ledger
        $filteredTotalIncome = (float) $ledger->where('type', 'income')->sum('amount');
        $filteredTotalExpense = (float) $ledger->where('type', 'expense')->sum('amount');
        $filteredNet = $filteredTotalIncome - $filteredTotalExpense;
        $totalLedgerCount = $ledger->count();

        // 5. Paginasi 10 (Sesuai Permintaan User: buatkan paginasi 10)
        $perPage = (int) $request->get('per_page', 10);
        if ($perPage <= 0 || $perPage > 200) {
            $perPage = 10;
        }
        $page = (int) $request->get('page', 1);
        if ($page <= 0) {
            $page = 1;
        }

        $paginatedItems = $ledger->forPage($page, $perPage)->values()->all();

        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $totalLedgerCount,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // 6. Papan Skor Penagih (Collector KPI)
        $collectorKpi = Payment::with('collector')
            ->where('status', 'verified')
            ->whereNotNull('collected_by')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get()
            ->groupBy('collected_by')
            ->map(function($group) {
                return [
                    'name' => $group->first()->collector ? $group->first()->collector->name : 'Unknown',
                    'total_collected' => (float)$group->sum('amount'),
                    'count' => $group->count()
                ];
            })->sortByDesc('total_collected')->values()->all();

        return Inertia::render('FinancialReports/Index', [
            'month' => (int)$month,
            'year' => (int)$year,
            'summary' => [
                'omzet_customer' => $omzetCustomer,
                'omzet_reseller' => $omzetReseller,
                'omzet_manual' => $omzetManual,
                'total_omzet' => $totalOmzet,
                'total_pengeluaran' => $totalPengeluaran,
                'pengeluaran_material' => $pengeluaranMaterial,
                'pengeluaran_operasional' => $pengeluaranOperasional,
                'laba_bersih' => $labaBersih,
                'piutang_customer' => $piutangCustomer,
                'piutang_reseller' => $piutangReseller,
                'piutang_terminated' => $piutangTerminated,
                'total_piutang' => $totalPiutang,
            ],
            'chartData' => $chartData,
            'ledger' => $paginator,
            'ledger_summary' => [
                'total_income' => $filteredTotalIncome,
                'total_expense' => $filteredTotalExpense,
                'net' => $filteredNet,
                'total_count' => $totalLedgerCount,
            ],
            'filters' => [
                'search' => $search,
                'type' => $filterType,
                'category' => $filterCategory,
                'area' => $filterArea,
                'date_from' => $dateFrom ?: '',
                'date_to' => $dateTo ?: '',
                'per_page' => $perPage,
            ],
            'kpi' => $collectorKpi,
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'income_categories' => IncomeCategory::active()->orderBy('name')->get(['id', 'code', 'name', 'color']),
            'expense_categories' => ExpenseCategory::active()->orderBy('name')->get(['id', 'code', 'name', 'color']),
        ]);
    }

    /**
     * Halaman Cetak Laporan Keuangan & Buku Kas Umum
     */
    public function print(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!empty($dateFrom) && !empty($dateTo)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = Carbon::parse($dateTo)->endOfDay();
        } elseif (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        } elseif (!empty($dateTo)) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::parse($dateTo)->endOfDay();
        } else {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        }

        // Verified Payments
        $paymentsThisMonth = Payment::with(['invoice.customer.areaModel', 'customer.areaModel', 'collector'])
            ->where('status', 'verified')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();
            
        $omzetCustomer = (float) $paymentsThisMonth->filter(fn($p) => $p->invoice && !$p->invoice->is_reseller_balance)->sum('amount');
        $omzetReseller = (float) $paymentsThisMonth->filter(fn($p) => $p->invoice && $p->invoice->is_reseller_balance)->sum('amount');

        $incomesThisMonth = Income::with(['creator', 'category', 'areaModel'])
            ->whereBetween('income_date', [$startDate, $endDate])
            ->get();
        $omzetManual = (float) $incomesThisMonth->sum('amount');
        $totalOmzet = $omzetCustomer + $omzetReseller + $omzetManual;

        // Expenses
        $totalPengeluaran = (float) Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
        $pengeluaranMaterial = (float) Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where(function($q) {
                $q->whereHas('materialTransaction')
                  ->orWhereHas('category', fn($c) => $c->where('code', 'MAT'))
                  ->orWhere('description', 'like', '%Order Toko%');
            })
            ->sum('amount');
        $pengeluaranOperasional = max(0, $totalPengeluaran - $pengeluaranMaterial);
        $labaBersih = $totalOmzet - $totalPengeluaran;

        // Piutang
        $piutangCustomer = (float) Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereHas('customer', function($q) {
                $q->where('status', '!=', 'terminated')
                  ->whereNotIn('service_status', ['gratis', 'free_home']);
            })
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments->sum('amount');
                return max(0, $inv->amount - $paid);
            });
            
        $piutangReseller = (float) Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', true)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereHas('customer', fn($c) => $c->where('is_reseller', true))
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments->sum('amount');
                return max(0, $inv->amount - $paid);
            });
            
        $totalPiutang = $piutangCustomer + $piutangReseller;

        // Ledger
        $ledger = collect([]);
        foreach ($paymentsThisMonth as $p) {
            $type = ($p->invoice && $p->invoice->is_reseller_balance) ? 'reseller' : 'customer';
            $custName = $p->customer?->name ?? ($p->invoice?->customer?->name ?? 'Pelanggan');
            $collectorName = $p->collector?->name ?? 'System';
            $custArea = $p->customer?->areaModel?->name ?? $p->customer?->area ?? ($p->invoice?->customer?->areaModel?->name ?? $p->invoice?->customer?->area ?? null);
            $custAreaId = $p->customer?->area_id ?? ($p->invoice?->customer?->area_id ?? null);

            $ledger->push([
                'id' => 'p_'.$p->id,
                'date' => Carbon::parse($p->payment_date)->format('Y-m-d H:i:s'),
                'type' => 'income',
                'category' => $type,
                'category_id' => null,
                'category_name' => $type === 'reseller' ? 'Reseller Voucher' : 'Pelanggan Reguler',
                'category_code' => $type === 'reseller' ? 'RSL' : 'CUS',
                'amount' => (float)$p->amount,
                'description' => "Pembayaran " . ($type === 'reseller' ? 'Reseller' : 'Pelanggan') . " ({$custName})" . ($p->notes ? " - {$p->notes}" : ""),
                'person' => $custName,
                'user' => $collectorName,
                'po_number' => null,
                'area_id' => $custAreaId,
                'area' => $custArea,
            ]);
        }

        foreach ($incomesThisMonth as $inc) {
            $catName = $inc->category ? $inc->category->name : 'Pemasukan Lainnya';
            $catCode = $inc->category ? $inc->category->code : 'INC';
            $catColor = $inc->category ? $inc->category->color : 'emerald';
            $incArea = $inc->area ?: ($inc->areaModel?->name ?: null);

            $ledger->push([
                'id' => 'inc_'.$inc->id,
                'income_id' => $inc->id,
                'date' => Carbon::parse($inc->income_date)->format('Y-m-d H:i:s'),
                'type' => 'income',
                'is_manual' => true,
                'category' => $inc->income_category_id ? (string)$inc->income_category_id : 'manual_income',
                'category_id' => $inc->income_category_id,
                'category_name' => $catName,
                'category_code' => $catCode,
                'category_color' => $catColor,
                'amount' => (float)$inc->amount,
                'description' => $inc->description,
                'source' => $inc->source,
                'payment_method' => $inc->payment_method,
                'reference_no' => $inc->reference_no,
                'person' => $inc->source ?: '-',
                'user' => $inc->creator ? $inc->creator->name : 'Admin',
                'material_order' => null,
                'area_id' => $inc->area_id,
                'area' => $incArea,
            ]);
        }

        $expensesThisMonth = Expense::with(['creator', 'category', 'areaModel', 'materialTransaction'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();

        foreach ($expensesThisMonth as $e) {
            $catName = $e->category ? $e->category->name : 'Operasional';
            $catCode = $e->category ? $e->category->code : null;
            $poNum = null;
            $orderArea = null;
            if ($e->materialTransaction) {
                $poNum = $e->materialTransaction->transaction_number;
                $orderArea = $e->materialTransaction->area ?: $e->materialTransaction->technician_name;
            } elseif (preg_match('/\[(PO-[^\]]+)\]/', $e->description, $matches)) {
                $poNum = $matches[1];
            }

            $expArea = $e->area ?: ($e->areaModel?->name ?: $orderArea);
            $expAreaId = $e->area_id ?: ($e->materialTransaction?->area_id ?? null);
            $isMaterial = !empty($poNum) || $catCode === 'MAT';

            $ledger->push([
                'id' => 'e_'.$e->id,
                'date' => Carbon::parse($e->expense_date)->format('Y-m-d H:i:s'),
                'type' => 'expense',
                'category' => $isMaterial ? 'material' : ($e->expense_category_id ? (string)$e->expense_category_id : 'operational'),
                'category_id' => $e->expense_category_id,
                'category_name' => $isMaterial ? 'Belanja Material' : $catName,
                'category_code' => $isMaterial ? 'MAT' : $catCode,
                'amount' => (float)$e->amount,
                'description' => $e->description,
                'person' => '-',
                'user' => $e->creator ? $e->creator->name : 'Unknown',
                'po_number' => $poNum,
                'area_id' => $expAreaId,
                'area' => $expArea,
            ]);
        }

        $ledger = $ledger->sortByDesc('date')->values();

        // Filters applied if any
        $search = trim($request->get('search', ''));
        $filterType = $request->get('type', 'all');
        $filterCategory = $request->get('category', 'all');
        $filterArea = $request->get('area', 'all');

        if ($search !== '') {
            $searchLower = strtolower($search);
            $ledger = $ledger->filter(function($item) use ($searchLower) {
                return str_contains(strtolower($item['description'] ?? ''), $searchLower)
                    || str_contains(strtolower($item['person'] ?? ''), $searchLower)
                    || str_contains(strtolower($item['user'] ?? ''), $searchLower)
                    || str_contains(strtolower($item['po_number'] ?? ''), $searchLower)
                    || str_contains(strtolower($item['area'] ?? ''), $searchLower)
                    || str_contains(strtolower($item['source'] ?? ''), $searchLower)
                    || str_contains(strtolower($item['category_name'] ?? ''), $searchLower);
            });
        }

        if ($filterType !== 'all' && in_array($filterType, ['income', 'expense'])) {
            $ledger = $ledger->filter(fn($item) => $item['type'] === $filterType);
        }

        if ($filterCategory !== 'all' && $filterCategory !== '') {
            $ledger = $ledger->filter(function($item) use ($filterCategory) {
                if ($filterCategory === 'customer') {
                    return $item['type'] === 'income' && ($item['category'] ?? '') === 'customer';
                }
                if ($filterCategory === 'reseller') {
                    return $item['type'] === 'income' && ($item['category'] ?? '') === 'reseller';
                }
                if ($filterCategory === 'manual_income') {
                    return $item['type'] === 'income' && !empty($item['is_manual']);
                }
                if ($filterCategory === 'material') {
                    return !empty($item['po_number']) || ($item['category_code'] ?? '') === 'MAT' || $item['category'] === 'material';
                }
                return ($item['category_id'] ?? null) == $filterCategory 
                    || ($item['category_code'] ?? null) == $filterCategory
                    || ($item['category'] ?? null) == $filterCategory;
            });
        }

        if ($filterArea !== 'all' && $filterArea !== '' && !empty($filterArea)) {
            $ledger = $ledger->filter(function($item) use ($filterArea) {
                return (string)($item['area_id'] ?? '') === (string)$filterArea 
                    || strtolower(trim($item['area'] ?? '')) === strtolower(trim($filterArea));
            });
        }

        $filteredIncome = (float) $ledger->where('type', 'income')->sum('amount');
        $filteredExpense = (float) $ledger->where('type', 'expense')->sum('amount');
        $filteredNet = $filteredIncome - $filteredExpense;

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $periodLabel = $monthNames[$month] . ' ' . $year;
        if (!empty($dateFrom) && !empty($dateTo)) {
            $periodLabel = Carbon::parse($dateFrom)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($dateTo)->translatedFormat('d M Y');
        }

        return view('print.financial-report', [
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => [
                'total_omzet' => $totalOmzet,
                'omzet_customer' => $omzetCustomer,
                'omzet_reseller' => $omzetReseller,
                'omzet_manual' => $omzetManual,
                'total_pengeluaran' => $totalPengeluaran,
                'pengeluaran_material' => $pengeluaranMaterial,
                'pengeluaran_operasional' => $pengeluaranOperasional,
                'laba_bersih' => $labaBersih,
                'total_piutang' => $totalPiutang,
                'piutang_customer' => $piutangCustomer,
                'piutang_reseller' => $piutangReseller,
            ],
            'filteredTotals' => [
                'income' => $filteredIncome,
                'expense' => $filteredExpense,
                'net' => $filteredNet,
                'count' => $ledger->count(),
            ],
            'ledger' => $ledger,
            'filters' => [
                'search' => $search,
                'type' => $filterType,
                'category' => $filterCategory,
                'area' => $filterArea,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'printedBy' => auth()->user() ? auth()->user()->name : 'Super Admin',
            'printDate' => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ]);
    }
}
