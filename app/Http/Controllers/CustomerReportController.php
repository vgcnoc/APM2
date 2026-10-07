<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // 1. Total Pemasukan Bulan Ini (Hanya dari Pelanggan Reguler)
        $pemasukanBulanIni = Payment::where('status', 'verified')
            ->whereHas('customer', function($q) {
                $q->where('is_reseller', false);
            })
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
            })
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        // 2. Total Piutang Keseluruhan (Hanya Pelanggan Reguler)
        $piutangCustomer = Invoice::where('is_reseller_balance', false)
            ->whereHas('customer', function($q) {
                $q->where('is_reseller', false);
            })
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments()->where('status', 'verified')->sum('amount');
                return max(0, $inv->amount - $paid);
            });

        // 3. Grafik Pemasukan Pelanggan (6 Bulan Terakhir)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mDate = now()->subMonths($i);
            
            $pBln = Payment::where('status', 'verified')
                ->whereHas('customer', function($q) {
                    $q->where('is_reseller', false);
                })
                ->whereHas('invoice', function($q) {
                    $q->where('is_reseller_balance', false);
                })
                ->whereMonth('payment_date', $mDate->month)
                ->whereYear('payment_date', $mDate->year)
                ->sum('amount');
            
            $chartData[] = [
                'month' => $mDate->format('M Y'),
                'amount' => $pBln
            ];
        }

        // 4. Riwayat Mutasi (Hanya Transaksi Pelanggan Bulan Ini)
        $ledger = Payment::with(['customer', 'collector', 'invoice'])
            ->where('status', 'verified')
            ->whereHas('customer', function($q) {
                $q->where('is_reseller', false);
            })
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
            })
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->latest('payment_date')
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'date' => Carbon::parse($p->payment_date)->format('Y-m-d H:i:s'),
                    'customer' => $p->customer ? $p->customer->name : 'Unknown',
                    'invoice_number' => $p->invoice ? $p->invoice->invoice_number : '-',
                    'amount' => $p->amount,
                    'method' => $p->payment_method,
                    'collector' => $p->collector ? $p->collector->name : 'System',
                    'notes' => $p->notes
                ];
            });

        return Inertia::render('CustomerReports/Index', [
            'month' => (int)$month,
            'year' => (int)$year,
            'summary' => [
                'pemasukan' => $pemasukanBulanIni,
                'piutang' => $piutangCustomer,
            ],
            'chartData' => $chartData,
            'ledger' => $ledger
        ]);
    }
}
