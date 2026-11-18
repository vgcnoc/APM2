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
        // 1. Total Saldo Beredar (Saldo aktif yang dimiliki reseller saat ini)
        $totalSaldoBeredar = (float) Reseller::sum('balance');

        // 2. Data Invoices Kasbon Reseller
        $invoices = Invoice::where('is_reseller_balance', true)->get();
        $totalKasbonInvoices = (float) $invoices->sum('amount');
        
        $verifiedPayments = (float) Payment::where('status', 'verified')
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->sum('amount');
            
        $totalPiutangInvoices = max(0, $totalKasbonInvoices - $verifiedPayments);

        // 3. Topup Tunai & Transfer via ResellerBalanceRequest (Bukan kasbon)
        $cashTransferTopups = (float) ResellerBalanceRequest::where('status', 'approved')
            ->whereIn('payment_method', ['transfer', 'cash'])
            ->whereDoesntHave('reseller.customer.invoices', function($iq) {
                $iq->where('is_reseller_balance', true)
                   ->whereHas('payments', fn($pq) => $pq->where('status', 'verified'));
            })
            ->sum('amount');

        // 4. Modal Awal & Kredit Saldo Masuk Langsung (INIT-*, ACT-*, ADJ-*)
        $initialAndDirectCredit = (float) ResellerTransaction::where('type', 'credit')
            ->where(function($q) {
                $q->where('reference_id', 'like', 'INIT-%')
                  ->orWhere('reference_id', 'like', 'ACT-%')
                  ->orWhere('reference_id', 'like', 'ADJ-%')
                  ->orWhere('description', 'like', '%Modal Awal%')
                  ->orWhere('description', 'like', '%Registrasi Kemitraan%')
                  ->orWhere('description', 'like', '%Aktivasi Kemitraan%');
            })
            ->sum('amount');

        // Total Pemasukan Riil dari Reseller (Setoran Kasbon Lunas + Topup Tunai/Transfer + Modal Awal)
        $totalPemasukan = $verifiedPayments + $cashTransferTopups + $initialAndDirectCredit;
        $totalKasbon = $totalKasbonInvoices;
        $totalPiutang = $totalPiutangInvoices;

        // 5. Data per Reseller untuk tabel
        $resellersQuery = Reseller::with(['customer.areaModel', 'user'])
            ->orderByDesc('balance');

        if ($request->filled('area_id')) {
            $resellersQuery->whereHas('customer', fn($c) => $c->where('area_id', $request->area_id));
        }

        $resellers = $resellersQuery->get()
            ->map(function ($reseller) {
                $name = $reseller->customer?->name 
                    ?? $reseller->user?->name 
                    ?? ('Reseller #' . $reseller->id);
                $code = $reseller->customer?->customer_code ?? $reseller->user?->email ?? ('ID:' . $reseller->id);
                $phone = $reseller->customer?->phone ?? $reseller->user?->phone ?? '-';
                $areaName = $reseller->customer?->areaModel?->name ?? '-';
                $hasCustomer = !empty($reseller->customer_id);
                $type = $hasCustomer ? 'Pelanggan' : 'Mitra User';

                // Hitung kasbon & pembayaran per reseller
                $totalKasbonReseller = 0;
                $verifiedPaidReseller = 0;

                if ($hasCustomer) {
                    $customerInvoices = Invoice::where('is_reseller_balance', true)
                        ->where('customer_id', $reseller->customer_id)
                        ->get();
                    
                    $totalKasbonReseller = (float) $customerInvoices->sum('amount');
                    $verifiedPaidReseller = (float) Payment::where('status', 'verified')
                        ->whereIn('invoice_id', $customerInvoices->pluck('id'))
                        ->sum('amount');
                }

                // Tambahkan histori permohonan saldo direct tanpa invoice (jika ada)
                $directCashTransfer = (float) ResellerBalanceRequest::where('reseller_id', $reseller->id)
                    ->where('status', 'approved')
                    ->whereIn('payment_method', ['transfer', 'cash'])
                    ->sum('amount');

                // Tambahkan modal awal registrasi / aktivasi / kredit penyesuaian
                $initialDirectCredit = (float) ResellerTransaction::where('reseller_id', $reseller->id)
                    ->where('type', 'credit')
                    ->where(function($q) {
                        $q->where('reference_id', 'like', 'INIT-%')
                          ->orWhere('reference_id', 'like', 'ACT-%')
                          ->orWhere('reference_id', 'like', 'ADJ-%')
                          ->orWhere('description', 'like', '%Modal Awal%')
                          ->orWhere('description', 'like', '%Registrasi Kemitraan%')
                          ->orWhere('description', 'like', '%Aktivasi Kemitraan%');
                    })
                    ->sum('amount');

                $totalMasukReseller = $verifiedPaidReseller + $directCashTransfer + $initialDirectCredit;
                $piutangReseller = max(0, $totalKasbonReseller - $verifiedPaidReseller);

                // Hitung total pemakaian saldo (debit) untuk voucher / loket
                $totalUsage = (float) ResellerTransaction::where('reseller_id', $reseller->id)
                    ->where('type', 'debit')
                    ->sum('amount');

                return [
                    'id' => $reseller->id,
                    'name' => $name,
                    'code' => $code,
                    'phone' => $phone,
                    'area_name' => $areaName,
                    'has_customer' => $hasCustomer,
                    'type' => $type,
                    'balance' => (float) $reseller->balance,
                    'piutang' => $piutangReseller,
                    'total_kasbon' => $totalKasbonReseller,
                    'total_paid' => $totalMasukReseller,
                    'total_usage' => $totalUsage,
                ];
            });

        // 6. Riwayat Transaksi Mutasi Reseller Terakhir (eager load user & customer)
        $recentTransactions = ResellerTransaction::whereHas('reseller')
            ->with(['reseller.customer', 'reseller.user'])
            ->latest('id')
            ->take(15)
            ->get();

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

        if ($request->filled('area_id')) {
            $resellerExpenseQuery->where('area_id', $request->area_id);
        }

        $totalPengeluaranReseller = (float) (clone $resellerExpenseQuery)->sum('amount');
        $kasBersih = $totalPemasukan - $totalPengeluaranReseller;

        // 8. Riwayat Pengeluaran Terkait Reseller Terakhir
        $recentExpenses = (clone $resellerExpenseQuery)
            ->with(['user', 'category'])
            ->latest('expense_date')
            ->latest('created_at')
            ->take(15)
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
                'area_id' => $request->area_id ?? '',
            ]
        ]);
    }
}
