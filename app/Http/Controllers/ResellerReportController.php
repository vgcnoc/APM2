<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ResellerReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Total Saldo Beredar (Saldo yang dimiliki reseller saat ini)
        $totalSaldoBeredar = Reseller::sum('balance');

        // 2. Total Pemasukan (Uang Kasbon yang sudah Lunas/Diterima & Tunai)
        $totalPemasukan = Payment::where('status', 'verified')
            ->whereHas('invoice', function ($q) {
                $q->where('is_reseller_balance', true);
            })->sum('amount');

        // 3. Total Piutang (Kasbon yang belum dibayar / belum divalidasi)
        // Cara hitung: (Total Amount Invoice Kasbon) - (Total Verified Payments)
        $invoices = Invoice::where('is_reseller_balance', true)->get();
        $totalKasbon = $invoices->sum('amount');
        $totalPiutang = $totalKasbon - $totalPemasukan;

        // 4. Data per Reseller untuk tabel
        $resellers = Reseller::with(['customer', 'user'])->get()->map(function ($reseller) {
            $customerInvoices = Invoice::where('is_reseller_balance', true)
                ->where('customer_id', $reseller->customer_id)
                ->get();
            
            $totalKasbonReseller = $customerInvoices->sum('amount');
            $verifiedPaid = Payment::where('status', 'verified')
                ->whereIn('invoice_id', $customerInvoices->pluck('id'))
                ->sum('amount');
                
            $piutangReseller = $totalKasbonReseller - $verifiedPaid;

            return [
                'id' => $reseller->id,
                'name' => $reseller->customer ? $reseller->customer->name : 'Unknown',
                'balance' => $reseller->balance,
                'piutang' => $piutangReseller,
                'total_kasbon' => $totalKasbonReseller,
                'total_paid' => $verifiedPaid,
            ];
        });

        // 5. Riwayat Pemasukan Terakhir (Verified Payments)
        $recentPayments = Payment::with(['invoice', 'customer'])
            ->where('status', 'verified')
            ->whereHas('invoice', function ($q) {
                $q->where('is_reseller_balance', true);
            })
            ->latest('updated_at')
            ->take(5)
            ->get();

        return Inertia::render('ResellerReports/Index', [
            'stats' => [
                'saldo_beredar' => $totalSaldoBeredar,
                'pemasukan' => $totalPemasukan,
                'piutang' => $totalPiutang,
            ],
            'resellers' => $resellers,
            'recent_payments' => $recentPayments
        ]);
    }
}
