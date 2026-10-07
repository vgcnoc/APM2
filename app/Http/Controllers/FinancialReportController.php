<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // 1. Grand Summary Cards (Current Month)
        
        // Pemasukan: Hanya pembayaran yang sudah verified
        $paymentsThisMonth = Payment::with('invoice.customer')
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

        // Piutang Keseluruhan (Tunggakan + Kasbon yang belum lunas) - Global, tidak terikat bulan
        $piutangCustomer = Invoice::where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments()->where('status', 'verified')->sum('amount');
                return max(0, $inv->amount - $paid);
            });
            
        $piutangReseller = Invoice::where('is_reseller_balance', true)
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function($inv) {
                $paid = $inv->payments()->where('status', 'verified')->sum('amount');
                return max(0, $inv->amount - $paid);
            });
            
        $totalPiutang = $piutangCustomer + $piutangReseller;

        // 2. Grafik Pertumbuhan Bulanan (6 Bulan Terakhir)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mDate = now()->subMonths($i);
            
            $pBln = Payment::with('invoice')
                ->where('status', 'verified')
                ->whereMonth('payment_date', $mDate->month)
                ->whereYear('payment_date', $mDate->year)
                ->get();
                
            $cCust = $pBln->filter(fn($p) => $p->invoice && !$p->invoice->is_reseller_balance)->sum('amount');
            $cRes = $pBln->filter(fn($p) => $p->invoice && $p->invoice->is_reseller_balance)->sum('amount');
            $cExp = Expense::whereMonth('expense_date', $mDate->month)->whereYear('expense_date', $mDate->year)->sum('amount');
            
            $chartData[] = [
                'month' => $mDate->format('M Y'),
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
            $custName = $p->customer ? $p->customer->name : 'Unknown';
            $ledger->push([
                'id' => 'p_'.$p->id,
                'date' => Carbon::parse($p->payment_date)->format('Y-m-d H:i:s'),
                'type' => 'income',
                'category' => $type,
                'amount' => $p->amount,
                'description' => "Pembayaran {$type} ({$custName})" . ($p->notes ? " - {$p->notes}" : ""),
                'person' => $custName,
                'user' => $p->collector ? $p->collector->name : 'System'
            ]);
        }
        
        $expensesThisMonth = Expense::with('creator')->whereBetween('expense_date', [$startDate, $endDate])->get();
        foreach ($expensesThisMonth as $e) {
            $ledger->push([
                'id' => 'e_'.$e->id,
                'date' => Carbon::parse($e->expense_date)->format('Y-m-d H:i:s'),
                'type' => 'expense',
                'category' => 'operational',
                'amount' => $e->amount,
                'description' => $e->description,
                'person' => '-',
                'user' => $e->creator ? $e->creator->name : 'Unknown'
            ]);
        }
        
        // Sort ledger by date desc
        $ledger = $ledger->sortByDesc('date')->values()->all();

        // 4. Papan Skor Penagih (Collector KPI)
        // Group verified payments this month by collected_by
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
                'total_piutang' => $totalPiutang,
            ],
            'chartData' => $chartData,
            'ledger' => $ledger,
            'kpi' => $collectorKpi
        ]);
    }
}
