<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reseller;
use App\Models\ResellerTransaction;
use App\Models\ResellerBalanceRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ResellerReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $areaId = $request->get('area_id');
        $type = $request->get('type', 'all');
        $status = $request->get('status', 'all');
        $sort = $request->get('sort', 'balance_desc');
        $search = $request->get('search');

        // 1. Total Saldo Beredar (Saldo aktif yang dimiliki reseller saat ini)
        $saldoQuery = Reseller::query();
        if ($areaId) {
            $saldoQuery->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
        }
        $totalSaldoBeredar = (float) $saldoQuery->sum('balance');

        // 2. Data Invoices Kasbon Reseller
        $invoicesQuery = Invoice::where('is_reseller_balance', true);
        if ($areaId) {
            $invoicesQuery->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
        }
        if ($startDate && $endDate) {
            $invoicesQuery->whereBetween('issued_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $invoicesQuery->where('issued_date', '>=', $startDate);
        } elseif ($endDate) {
            $invoicesQuery->where('issued_date', '<=', $endDate);
        }

        $invoices = $invoicesQuery->get();
        $totalKasbonInvoices = (float) $invoices->sum('amount');
        
        $verifiedPaymentsQuery = Payment::where('status', 'verified')
            ->whereIn('invoice_id', $invoices->pluck('id'));
        if ($startDate && $endDate) {
            $verifiedPaymentsQuery->whereBetween('payment_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $verifiedPaymentsQuery->where('payment_date', '>=', $startDate);
        } elseif ($endDate) {
            $verifiedPaymentsQuery->where('payment_date', '<=', $endDate);
        }
        $verifiedPayments = (float) $verifiedPaymentsQuery->sum('amount');
            
        $totalPiutangInvoices = max(0, $totalKasbonInvoices - $verifiedPayments);

        // 3. Topup Tunai & Transfer via ResellerBalanceRequest (Bukan kasbon)
        $cashTransferQuery = ResellerBalanceRequest::where('status', 'approved')
            ->whereIn('payment_method', ['transfer', 'cash'])
            ->whereDoesntHave('reseller.customer.invoices', function($iq) {
                $iq->where('is_reseller_balance', true)
                   ->whereHas('payments', fn($pq) => $pq->where('status', 'verified'));
            });

        if ($areaId) {
            $cashTransferQuery->whereHas('reseller.customer', fn($c) => $c->where('area_id', $areaId));
        }
        if ($startDate && $endDate) {
            $cashTransferQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $cashTransferQuery->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $cashTransferQuery->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        $cashTransferTopups = (float) $cashTransferQuery->sum('amount');

        // 4. Modal Awal & Kredit Saldo Masuk Langsung (INIT-*, ACT-*, ADJ-*)
        $initialCreditQuery = ResellerTransaction::where('type', 'credit')
            ->where(function($q) {
                $q->where('reference_id', 'like', 'INIT-%')
                  ->orWhere('reference_id', 'like', 'ACT-%')
                  ->orWhere('reference_id', 'like', 'ADJ-%')
                  ->orWhere('description', 'like', '%Modal Awal%')
                  ->orWhere('description', 'like', '%Registrasi Kemitraan%')
                  ->orWhere('description', 'like', '%Aktivasi Kemitraan%');
            });

        if ($areaId) {
            $initialCreditQuery->whereHas('reseller.customer', fn($c) => $c->where('area_id', $areaId));
        }
        if ($startDate && $endDate) {
            $initialCreditQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $initialCreditQuery->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $initialCreditQuery->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        $initialAndDirectCredit = (float) $initialCreditQuery->sum('amount');

        // Total Pemasukan Riil dari Reseller
        $totalPemasukan = $verifiedPayments + $cashTransferTopups + $initialAndDirectCredit;
        $totalKasbon = $totalKasbonInvoices;
        $totalPiutang = $totalPiutangInvoices;

        // 5. Data per Reseller untuk tabel
        $resellersQuery = Reseller::with(['customer.areaModel', 'user']);

        if ($areaId) {
            $resellersQuery->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
        }

        if ($type === 'pelanggan') {
            $resellersQuery->whereNotNull('customer_id');
        } elseif ($type === 'mitra_user') {
            $resellersQuery->whereNull('customer_id');
        }

        $resellers = $resellersQuery->get()
            ->map(function ($reseller) use ($startDate, $endDate) {
                $name = $reseller->customer?->name 
                    ?? $reseller->user?->name 
                    ?? ('Reseller #' . $reseller->id);
                $code = $reseller->customer?->customer_code ?? $reseller->user?->email ?? ('ID:' . $reseller->id);
                $phone = $reseller->customer?->phone ?? $reseller->user?->phone ?? '-';
                $email = $reseller->user?->email ?? $reseller->customer?->email ?? '-';
                $areaName = $reseller->customer?->areaModel?->name ?? '-';
                $areaId = $reseller->customer?->area_id ?? null;
                $hasCustomer = !empty($reseller->customer_id);
                $type = $hasCustomer ? 'Pelanggan' : 'Mitra User';

                // Hitung kasbon & pembayaran per reseller
                $totalKasbonReseller = 0;
                $verifiedPaidReseller = 0;

                if ($hasCustomer) {
                    $customerInvoicesQuery = Invoice::where('is_reseller_balance', true)
                        ->where('customer_id', $reseller->customer_id);
                    
                    if ($startDate && $endDate) {
                        $customerInvoicesQuery->whereBetween('issued_date', [$startDate, $endDate]);
                    }

                    $customerInvoices = $customerInvoicesQuery->get();
                    $totalKasbonReseller = (float) $customerInvoices->sum('amount');

                    $paymentQuery = Payment::where('status', 'verified')
                        ->whereIn('invoice_id', $customerInvoices->pluck('id'));
                    if ($startDate && $endDate) {
                        $paymentQuery->whereBetween('payment_date', [$startDate, $endDate]);
                    }
                    $verifiedPaidReseller = (float) $paymentQuery->sum('amount');
                }

                // Tambahkan histori permohonan saldo direct tanpa invoice (jika ada)
                $directCashTransferQuery = ResellerBalanceRequest::where('reseller_id', $reseller->id)
                    ->where('status', 'approved')
                    ->whereIn('payment_method', ['transfer', 'cash']);
                if ($startDate && $endDate) {
                    $directCashTransferQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
                $directCashTransfer = (float) $directCashTransferQuery->sum('amount');

                // Tambahkan modal awal registrasi / aktivasi / kredit penyesuaian
                $initialDirectCreditQuery = ResellerTransaction::where('reseller_id', $reseller->id)
                    ->where('type', 'credit')
                    ->where(function($q) {
                        $q->where('reference_id', 'like', 'INIT-%')
                          ->orWhere('reference_id', 'like', 'ACT-%')
                          ->orWhere('reference_id', 'like', 'ADJ-%')
                          ->orWhere('description', 'like', '%Modal Awal%')
                          ->orWhere('description', 'like', '%Registrasi Kemitraan%')
                          ->orWhere('description', 'like', '%Aktivasi Kemitraan%');
                    });
                if ($startDate && $endDate) {
                    $initialDirectCreditQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
                $initialDirectCredit = (float) $initialDirectCreditQuery->sum('amount');

                $totalMasukReseller = $verifiedPaidReseller + $directCashTransfer + $initialDirectCredit;
                $piutangReseller = max(0, $totalKasbonReseller - $verifiedPaidReseller);

                // Hitung total pemakaian saldo (debit) untuk voucher / loket
                $usageQuery = ResellerTransaction::where('reseller_id', $reseller->id)
                    ->where('type', 'debit');
                if ($startDate && $endDate) {
                    $usageQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
                $totalUsage = (float) $usageQuery->sum('amount');

                return [
                    'id' => $reseller->id,
                    'name' => $name,
                    'code' => $code,
                    'phone' => $phone,
                    'email' => $email,
                    'area_name' => $areaName,
                    'area_id' => $areaId,
                    'has_customer' => $hasCustomer,
                    'type' => $type,
                    'balance' => (float) $reseller->balance,
                    'piutang' => $piutangReseller,
                    'total_kasbon' => $totalKasbonReseller,
                    'total_paid' => $totalMasukReseller,
                    'total_usage' => $totalUsage,
                ];
            });

        // 6. Riwayat Transaksi Mutasi Reseller Terakhir
        $trxQuery = ResellerTransaction::whereHas('reseller')
            ->with(['reseller.customer', 'reseller.user']);
        if ($areaId) {
            $trxQuery->whereHas('reseller.customer', fn($c) => $c->where('area_id', $areaId));
        }
        if ($startDate && $endDate) {
            $trxQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $recentTransactions = $trxQuery->latest('id')->take(20)->get();

        // 7. Beban / Pengeluaran Terkait Reseller & Mitra (Bukan beban umum seluruh kantor/tower ISP)
        $resellerExpenseQuery = Expense::where(function($q) {
            $q->where('description', 'like', '%reseller%')
              ->orWhere('description', 'like', '%mitra%')
              ->orWhere('description', 'like', '%voucher%')
              ->orWhere('description', 'like', '%saldo%')
              ->orWhereHas('category', function($cq) {
                  $cq->where('name', 'like', '%reseller%')
                    ->orWhere('name', 'like', '%mitra%')
                    ->orWhere('name', 'like', '%voucher%');
              });
        })->withoutOrderToko();

        if ($areaId) {
            $resellerExpenseQuery->where('area_id', $areaId);
        }
        if ($startDate && $endDate) {
            $resellerExpenseQuery->whereBetween('expense_date', [$startDate, $endDate]);
        }

        $totalPengeluaranReseller = (float) (clone $resellerExpenseQuery)->sum('amount');
        $kasBersih = $totalPemasukan - $totalPengeluaranReseller;

        // 8. Riwayat Pengeluaran Terkait Reseller Terakhir
        $recentExpenses = (clone $resellerExpenseQuery)
            ->with(['user', 'category'])
            ->latest('expense_date')
            ->latest('created_at')
            ->take(20)
            ->get();

        return Inertia::render('ResellerReports/Index', [
            'stats' => [
                'saldo_beredar' => $totalSaldoBeredar,
                'pemasukan' => $totalPemasukan,
                'piutang' => $totalPiutang,
                'pengeluaran' => $totalPengeluaranReseller,
                'kas_bersih' => $kasBersih,
            ],
            'resellers' => $resellers,
            'recent_transactions' => $recentTransactions,
            'recent_expenses' => $recentExpenses,
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'expense_categories' => ExpenseCategory::active()->orderBy('name')->get(['id', 'code', 'name', 'color']),
            'filters' => [
                'area_id' => $areaId ?? '',
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'type' => $type,
                'status' => $status,
                'sort' => $sort,
                'search' => $search ?? '',
            ]
        ]);
    }
}
