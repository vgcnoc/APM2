<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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
        
        // Pemasukan: Hanya pembayaran yang sudah verified (Eager load relations to prevent N+1)
        $paymentsThisMonth = Payment::with(['invoice.customer', 'customer', 'collector'])
            ->where('status', 'verified')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();
            
        $omzetCustomer = $paymentsThisMonth->filter(function($p) {
            return $p->invoice && !$p->invoice->is_reseller_balance;
        })->sum('amount');
        
        $omzetReseller = $paymentsThisMonth->filter(function($p) {
            return $p->invoice && $p->invoice->is_reseller_balance;
        })->sum('amount');
        
        $totalOmzet = $omzetCustomer + $omzetReseller;

        // Pengeluaran
        $totalPengeluaran = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        // Laba Bersih
        $labaBersih = $totalOmzet - $totalPengeluaran;

        // Piutang Keseluruhan (Tunggakan Aktif + Kasbon yang belum lunas) - Eager load payments to prevent N+1 queries!
        $piutangCustomer = Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
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
            
        $piutangReseller = Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', true)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereHas('customer', fn($c) => $c->where('is_reseller', true))
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments->sum('amount');
                return max(0, $inv->amount - $paid);
            });

        // Piutang Pelanggan Stop Permanen (Bad Debt)
        $piutangTerminated = Invoice::with(['payments' => fn($q) => $q->where('status', 'verified')])
            ->where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial'])
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
                
            $cCust = $pBln->filter(fn($p) => $p->invoice && !$p->invoice->is_reseller_balance)->sum('amount');
            $cRes = $pBln->filter(fn($p) => $p->invoice && $p->invoice->is_reseller_balance)->sum('amount');
            $cExp = Expense::whereBetween('expense_date', [$mStart, $mEnd])->sum('amount');
            
            $chartData[] = [
                'month' => $mDate->translatedFormat('M Y'),
                'customer' => $cCust,
                'reseller' => $cRes,
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
            ]);
        }
        
        $expensesThisMonth = Expense::with(['creator', 'category', 'materialTransaction.items.material'])
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
            ]);
        }
        
        // Sort ledger by date desc initially
        $ledger = $ledger->sortByDesc('date')->values();

        // 4. Filter Lengkap Buku Kas Umum
        $search = trim($request->get('search', ''));
        $filterType = $request->get('type', 'all'); // 'all', 'income', 'expense'
        $filterCategory = $request->get('category', 'all'); // 'all', 'customer', 'reseller', 'material', or category_id

        if ($search !== '') {
            $searchLower = strtolower($search);
            $ledger = $ledger->filter(function($item) use ($searchLower) {
                $desc = strtolower($item['description'] ?? '');
                $person = strtolower($item['person'] ?? '');
                $user = strtolower($item['user'] ?? '');
                $poNum = strtolower($item['material_order']['transaction_number'] ?? '');
                $area = strtolower($item['material_order']['area_name'] ?? '');
                $catName = strtolower($item['category_name'] ?? '');
                $catCode = strtolower($item['category_code'] ?? '');

                return str_contains($desc, $searchLower)
                    || str_contains($person, $searchLower)
                    || str_contains($user, $searchLower)
                    || str_contains($poNum, $searchLower)
                    || str_contains($area, $searchLower)
                    || str_contains($catName, $searchLower)
                    || str_contains($catCode, $searchLower);
            });
        }

        if ($filterType !== 'all' && in_array($filterType, ['income', 'expense'])) {
            $ledger = $ledger->filter(fn($item) => $item['type'] === $filterType);
        }

        if ($filterCategory !== 'all' && $filterCategory !== '') {
            $ledger = $ledger->filter(function($item) use ($filterCategory) {
                if ($filterCategory === 'customer') {
                    return $item['type'] === 'income' && $item['category'] === 'customer';
                }
                if ($filterCategory === 'reseller') {
                    return $item['type'] === 'income' && $item['category'] === 'reseller';
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
                    'total_collected' => $group->sum('amount'),
                    'count' => $group->count()
                ];
            })->sortByDesc('total_collected')->values()->all();

        return Inertia::render('FinancialReports/Index', [
            'month' => (int)$month,
            'year' => (int)$year,
            'summary' => [
                'omzet_customer' => $omzetCustomer,
                'omzet_reseller' => $omzetReseller,
                'total_omzet' => $totalOmzet,
                'total_pengeluaran' => $totalPengeluaran,
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
                'date_from' => $dateFrom ?: '',
                'date_to' => $dateTo ?: '',
                'per_page' => $perPage,
            ],
            'kpi' => $collectorKpi,
            'expense_categories' => ExpenseCategory::active()->orderBy('name')->get(['id', 'code', 'name', 'color']),
        ]);
    }
}
