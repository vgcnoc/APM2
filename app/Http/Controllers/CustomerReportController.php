<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Area;
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
        $areaId = $request->get('area_id');
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $userAccessibleAreaIds = auth()->check() ? auth()->user()->getAccessibleAreaIds() : [];

        // Base area filter helper
        $applyAreaFilter = function($query, $relation = 'customer') use ($areaId, $userAccessibleAreaIds) {
            if (!empty($userAccessibleAreaIds)) {
                $query->whereHas($relation, fn($cq) => $cq->whereIn('area_id', $userAccessibleAreaIds));
            }
            if ($areaId) {
                $query->whereHas($relation, fn($cq) => $cq->where('area_id', $areaId));
            }
        };

        // 1. Total Pemasukan Bulan Ini (Hanya dari Pelanggan Internet)
        $pemasukanQuery = Payment::where('status', 'verified')
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
            })
            ->whereBetween('payment_date', [$startDate, $endDate]);
        $applyAreaFilter($pemasukanQuery, 'customer');
        $pemasukanBulanIni = (float) $pemasukanQuery->sum('amount');

        // 2. Total Piutang Berjalan (HANYA Pelanggan Aktif/Reguler, TIDAK termasuk yang sudah Stop Permanen)
        $piutangActiveQuery = Invoice::where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereHas('customer', function($q) {
                $q->where('status', '!=', 'terminated')
                  ->whereNotIn('service_status', ['gratis', 'free_home']);
            });
        $applyAreaFilter($piutangActiveQuery, 'customer');

        $activeInvoices = (clone $piutangActiveQuery)->with('payments')->get();
        $piutangCustomer = (float) $activeInvoices->sum(function($inv) {
            $paid = $inv->payments->where('status', 'verified')->sum('amount');
            return max(0, $inv->amount - $paid);
        });

        // 3. Piutang Pelanggan Stop Permanen (Bad Debt / Piutang Macet)
        $piutangTerminatedQuery = Invoice::where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereHas('customer', function($q) {
                $q->where('status', 'terminated')
                  ->whereNotIn('service_status', ['gratis', 'free_home']);
            });
        $applyAreaFilter($piutangTerminatedQuery, 'customer');

        $terminatedInvoices = (clone $piutangTerminatedQuery)->with('payments')->get();
        $piutangTerminated = (float) $terminatedInvoices->sum(function($inv) {
            $paid = $inv->payments->where('status', 'verified')->sum('amount');
            return max(0, $inv->amount - $paid);
        });

        // 4. Grafik Pemasukan Pelanggan (6 Bulan Terakhir)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $mDate = now()->subMonths($i);
            
            $chartQuery = Payment::where('status', 'verified')
                ->whereHas('invoice', function($q) {
                    $q->where('is_reseller_balance', false);
                })
                ->whereMonth('payment_date', $mDate->month)
                ->whereYear('payment_date', $mDate->year);
            $applyAreaFilter($chartQuery, 'customer');
            
            $chartData[] = [
                'month' => $mDate->format('M Y'),
                'amount' => (float) $chartQuery->sum('amount')
            ];
        }

        // 5. Data Tabel
        // A. Riwayat Pembayaran (Pemasukan Bulan Ini)
        $ledgerQuery = Payment::with(['customer.areaModel', 'collector', 'invoice'])
            ->where('status', 'verified')
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
            })
            ->whereBetween('payment_date', [$startDate, $endDate]);
        $applyAreaFilter($ledgerQuery, 'customer');

        $ledger = $ledgerQuery->latest('payment_date')->get()->map(function($p) {
            return [
                'id' => $p->id,
                'date' => Carbon::parse($p->payment_date)->format('Y-m-d H:i:s'),
                'customer' => $p->customer ? $p->customer->name : 'Unknown',
                'customer_code' => $p->customer ? $p->customer->customer_code : '-',
                'area' => $p->customer && $p->customer->areaModel ? $p->customer->areaModel->name : '-',
                'invoice_number' => $p->invoice ? $p->invoice->invoice_number : '-',
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'collector' => $p->collector ? $p->collector->name : 'System',
                'notes' => $p->notes
            ];
        });

        // B. Daftar Tunggakan Aktif (Piutang Berjalan)
        $activePiutangList = (clone $piutangActiveQuery)
            ->with(['customer.areaModel', 'payments'])
            ->latest('due_date')
            ->get()
            ->map(function($inv) {
                $paid = (float) $inv->payments->where('status', 'verified')->sum('amount');
                $remaining = max(0, $inv->amount - $paid);
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'customer' => $inv->customer ? $inv->customer->name : 'Unknown',
                    'customer_code' => $inv->customer ? $inv->customer->customer_code : '-',
                    'area' => $inv->customer && $inv->customer->areaModel ? $inv->customer->areaModel->name : '-',
                    'phone' => $inv->customer ? $inv->customer->phone : '-',
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : '-',
                    'amount' => (float) $inv->amount,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'status' => $inv->status,
                ];
            });

        // C. Daftar Piutang Pelanggan Stop Permanen
        $terminatedPiutangList = (clone $piutangTerminatedQuery)
            ->with(['customer.areaModel', 'payments'])
            ->latest('due_date')
            ->get()
            ->map(function($inv) {
                $paid = (float) $inv->payments->where('status', 'verified')->sum('amount');
                $remaining = max(0, $inv->amount - $paid);
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'customer' => $inv->customer ? $inv->customer->name : 'Unknown',
                    'customer_code' => $inv->customer ? $inv->customer->customer_code : '-',
                    'area' => $inv->customer && $inv->customer->areaModel ? $inv->customer->areaModel->name : '-',
                    'phone' => $inv->customer ? $inv->customer->phone : '-',
                    'cancel_date' => $inv->customer && $inv->customer->cancel_date ? Carbon::parse($inv->customer->cancel_date)->format('Y-m-d') : '-',
                    'cancel_reason' => $inv->customer ? $inv->customer->cancel_reason : '-',
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : '-',
                    'amount' => (float) $inv->amount,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'status' => $inv->status,
                ];
            });

        $areas = Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('CustomerReports/Index', [
            'month' => (int)$month,
            'year' => (int)$year,
            'area_id' => $areaId ? (int)$areaId : '',
            'areas' => $areas,
            'summary' => [
                'pemasukan' => $pemasukanBulanIni,
                'piutang' => $piutangCustomer,
                'piutang_terminated' => $piutangTerminated,
            ],
            'chartData' => $chartData,
            'ledger' => $ledger,
            'activePiutangList' => $activePiutangList,
            'terminatedPiutangList' => $terminatedPiutangList,
        ]);
    }
}
