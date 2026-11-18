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
        
        $verifiedPayments = Payment::where('status', 'verified')
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->sum('amount');
            
        $totalPiutangInvoices = max(0, $totalKasbonInvoices - (float)$verifiedPayments);

        // 3. Topup Tunai & Transfer via ResellerBalanceRequest (termasuk untuk Mitra User / direct topup)
        $cashTransferTopups = (float) ResellerBalanceRequest::where('status', 'approved')
            ->whereIn('payment_method', ['transfer', 'cash'])
            ->sum('amount');

        // Total Pemasukan Riil dari Reseller (Setoran Kasbon Lunas + Topup Tunai/Transfer)
        $totalPemasukan = (float)$verifiedPayments + $cashTransferTopups;
        $totalKasbon = $totalKasbonInvoices;
        $totalPiutang = $totalPiutangInvoices;

        // 4. Data per Reseller untuk tabel
        $resellers = Reseller::with(['customer', 'user'])
            ->orderByDesc('balance')
            ->get()
            ->map(function ($reseller) {
                $name = $reseller->customer?->name 
                    ?? $reseller->user?->name 
                    ?? ('Reseller #' . $reseller->id);
                $code = $reseller->customer?->customer_code ?? $reseller->user?->email ?? ('ID:' . $reseller->id);
                $phone = $reseller->customer?->phone ?? $reseller->user?->phone ?? '-';
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

                // Tambahkan histori permohonan saldo direct (jika ada)
                $directCashTransfer = (float) ResellerBalanceRequest::where('reseller_id', $reseller->id)
                    ->where('status', 'approved')
                    ->whereIn('payment_method', ['transfer', 'cash'])
                    ->sum('amount');
                $verifiedPaidReseller += $directCashTransfer;

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
                    'has_customer' => $hasCustomer,
                    'type' => $type,
                    'balance' => (float) $reseller->balance,
                    'piutang' => $piutangReseller,
                    'total_kasbon' => $totalKasbonReseller,
                    'total_paid' => $verifiedPaidReseller,
                    'total_usage' => $totalUsage,
                ];
            });

        // 5. Riwayat Transaksi Mutasi Reseller Terakhir (eager load user & customer)
        $recentTransactions = ResellerTransaction::whereHas('reseller')
            ->with(['reseller.customer', 'reseller.user'])
            ->latest('id')
            ->take(15)
            ->get();

        // 6. Beban / Pengeluaran Terkait Reseller & Mitra (Bukan beban umum seluruh kantor/tower ISP)
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

        $totalPengeluaranReseller = (float) (clone $resellerExpenseQuery)->sum('amount');
        $kasBersih = $totalPemasukan - $totalPengeluaranReseller;

        // 7. Riwayat Pengeluaran Terkait Reseller Terakhir
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
        ]);
    }
}
