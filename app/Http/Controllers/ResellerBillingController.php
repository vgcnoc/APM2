<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResellerBillingController extends Controller
{
    /**
     * Menu Penagihan (Untuk Penagih/Kolektor)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'unpaid');
        if (!in_array($activeTab, ['unpaid', 'partial', 'promise', 'overdue', 'paid'])) {
            $activeTab = 'unpaid';
        }

        $userAccessibleAreaIds = auth()->user()->getAccessibleAreaIds();

        // Helper untuk membangun query dengan filter area, tanggal, dan search
        $buildFilteredQuery = function ($tabType) use ($request, $userAccessibleAreaIds) {
            $q = Invoice::where('is_reseller_balance', true)
                ->whereHas('customer', fn($c) => $c->where('is_reseller', true));

            if (!empty($userAccessibleAreaIds)) {
                $q->whereHas('customer', fn($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
            }

            if ($request->filled('area_id')) {
                $areaId = $request->area_id;
                $q->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $q->where(function($sq) use ($search) {
                    $sq->where('invoice_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function($cq) use ($search) {
                           $cq->where('name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%");
                       });
                });
            }

            // Filter Tanggal
            if ($tabType === 'paid') {
                if ($request->filled('start_date')) {
                    $q->whereHas('payments', fn($pq) => $pq->where('status', 'verified')->whereDate('payment_date', '>=', $request->start_date));
                }
                if ($request->filled('end_date')) {
                    $q->whereHas('payments', fn($pq) => $pq->where('status', 'verified')->whereDate('payment_date', '<=', $request->end_date));
                }
                $q->where('status', 'paid');
            } elseif ($tabType === 'overdue') {
                if ($request->filled('start_date')) {
                    $q->whereDate('due_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('due_date', '<=', $request->end_date);
                }
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('due_date', '<', Carbon::today());
            } elseif ($tabType === 'partial') {
                if ($request->filled('start_date')) {
                    $q->whereDate('due_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('due_date', '<=', $request->end_date);
                }
                $q->where('status', 'partial');
            } elseif ($tabType === 'promise') {
                if ($request->filled('start_date')) {
                    $q->whereDate('promise_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('promise_date', '<=', $request->end_date);
                }
                $q->whereNotNull('promise_date')->where('status', '!=', 'paid');
            } else { // unpaid
                if ($request->filled('start_date')) {
                    $q->whereDate('due_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('due_date', '<=', $request->end_date);
                }
                $q->whereIn('status', ['unpaid', 'partial']);
            }

            return $q;
        };

        // Hitung statistik untuk kelima tab sesuai filter aktif
        $unpaidItems = $buildFilteredQuery('unpaid')->with('payments')->get(['id', 'amount', 'status']);
        $unpaidCount = $unpaidItems->count();
        $unpaidTotal = (float) $unpaidItems->sum(function($inv) {
            $paid = $inv->payments->where('status', 'verified')->sum('amount');
            return max(0, $inv->amount - $paid);
        });

        $partialItems = $buildFilteredQuery('partial')->with('payments')->get(['id', 'amount', 'status']);
        $partialCount = $partialItems->count();
        $partialTotal = (float) $partialItems->sum(function($inv) {
            $paid = $inv->payments->where('status', 'verified')->sum('amount');
            return max(0, $inv->amount - $paid);
        });

        $promiseItems = $buildFilteredQuery('promise')->with('payments')->get(['id', 'amount', 'status']);
        $promiseCount = $promiseItems->count();
        $promiseTotal = (float) $promiseItems->sum(function($inv) {
            $paid = $inv->payments->where('status', 'verified')->sum('amount');
            return max(0, $inv->amount - $paid);
        });

        $overdueItems = $buildFilteredQuery('overdue')->with('payments')->get(['id', 'amount', 'status']);
        $overdueCount = $overdueItems->count();
        $overdueTotal = (float) $overdueItems->sum(function($inv) {
            $paid = $inv->payments->where('status', 'verified')->sum('amount');
            return max(0, $inv->amount - $paid);
        });

        $paidItems = $buildFilteredQuery('paid')->with('payments')->get(['id', 'amount', 'status']);
        $paidCount = $paidItems->count();
        $paidTotal = (float) $paidItems->sum('amount');

        // Uang yang sudah diterima hari ini dari reseller
        $todayPaymentsQuery = Payment::whereDate('payment_date', Carbon::today())
            ->whereHas('invoice', fn($q) => $q->where('is_reseller_balance', true));

        if (!empty($userAccessibleAreaIds)) {
            $todayPaymentsQuery->whereHas('customer', fn($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
        }

        if ($request->filled('area_id')) {
            $todayPaymentsQuery->whereHas('customer', fn($c) => $c->where('area_id', $request->area_id));
        }

        $todayVerifiedAmount = (float) (clone $todayPaymentsQuery)->where('status', 'verified')->sum('amount');
        $todayVerifiedCount = (clone $todayPaymentsQuery)->where('status', 'verified')->count();
        $todayPendingAmount = (float) (clone $todayPaymentsQuery)->where('status', 'pending')->sum('amount');
        $todayPendingCount = (clone $todayPaymentsQuery)->where('status', 'pending')->count();

        // Data Invoice dengan pagination
        $invoices = $buildFilteredQuery($activeTab)
            ->with(['customer.areaModel', 'payments'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($invoice) {
                $verifiedPaid = $invoice->payments->where('status', 'verified')->sum('amount');
                $pendingPaid = $invoice->payments->where('status', 'pending')->sum('amount');
                
                return array_merge($invoice->toArray(), [
                    'remaining' => max(0, $invoice->amount - $verifiedPaid),
                    'total_paid' => $verifiedPaid,
                    'pending_amount' => $pendingPaid,
                    'effective_remaining' => max(0, $invoice->amount - $verifiedPaid - $pendingPaid)
                ]);
            });

        // Daftar Area untuk dropdown
        $areasQuery = Area::orderBy('name');
        if (!empty($userAccessibleAreaIds)) {
            $areasQuery->whereIn('id', $userAccessibleAreaIds);
        }
        $areas = $areasQuery->get(['id', 'name']);

        return Inertia::render('ResellerBilling/Index', [
            'invoices' => $invoices,
            'activeTab' => $activeTab,
            'areas' => $areas,
            'filters' => [
                'tab' => $activeTab,
                'area_id' => $request->area_id ?? '',
                'start_date' => $request->start_date ?? '',
                'end_date' => $request->end_date ?? '',
                'search' => $request->search ?? '',
            ],
            'stats' => [
                'unpaid' => [
                    'total' => $unpaidTotal,
                    'count' => $unpaidCount,
                ],
                'partial' => [
                    'total' => $partialTotal,
                    'count' => $partialCount,
                ],
                'promise' => [
                    'total' => $promiseTotal,
                    'count' => $promiseCount,
                ],
                'overdue' => [
                    'total' => $overdueTotal,
                    'count' => $overdueCount,
                ],
                'paid' => [
                    'total' => $paidTotal,
                    'count' => $paidCount,
                ],
                'today' => [
                    'verified_amount' => $todayVerifiedAmount,
                    'verified_count' => $todayVerifiedCount,
                    'pending_amount' => $todayPendingAmount,
                    'pending_count' => $todayPendingCount,
                    'total_amount' => $todayVerifiedAmount + $todayPendingAmount,
                ],
            ]
        ]);
    }

    /**
     * Penagih menerima uang (Submit Pembayaran Pending)
     */
    public function collect(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'is_direct_payment' => 'nullable|boolean'
        ]);

        if ($validated['amount'] > $invoice->remaining) {
            return back()->with('error', 'Nominal melebihi sisa tagihan.');
        }

        $isDirect = !empty($validated['is_direct_payment']);

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'],
                'status' => $isDirect ? 'verified' : 'pending',
                'collected_by' => auth()->id()
            ]);

            if ($isDirect) {
                // If it's a direct payment at the office, settle immediately
                $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
                if ($totalPaid >= $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'partial']);
                }
                
                DB::commit();
                return back()->with('success', 'Pembayaran langsung dikantor berhasil diproses dan invoice diupdate.');
            }

            DB::commit();
            return back()->with('success', 'Penerimaan pembayaran berhasil dicatat. Menunggu pelunasan dari perusahaan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menu Pelunasan (Untuk Perusahaan/Admin)
     */
    public function settlements(Request $request)
    {
        $activeTab = $request->get('tab', 'pending');
        
        $query = Payment::with(['invoice.payments', 'customer', 'collector'])
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', true);
            })
            ->whereHas('customer', function($q) {
                $q->where('is_reseller', true);
            });

        if ($activeTab === 'verified') {
            $query->where('status', 'verified');
        } else {
            $query->where('status', 'pending');
        }
            
        $payments = $query->latest()->paginate(10)
            ->through(function ($payment) {
                if ($payment->invoice) {
                    $verifiedPaid = $payment->invoice->payments->where('status', 'verified')->sum('amount');
                    $pendingPaid = $payment->invoice->payments->where('status', 'pending')->sum('amount');
                    
                    $payment->invoice->setAttribute('remaining', $payment->invoice->amount - $verifiedPaid);
                    $payment->invoice->setAttribute('total_paid', $verifiedPaid);
                    $payment->invoice->setAttribute('pending_amount', $pendingPaid);
                }
                return $payment;
            });

        return Inertia::render('ResellerBilling/Settlements', [
            'payments' => $payments,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Perusahaan menyetujui / melunasi pembayaran
     */
    public function approve(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Pembayaran ini sudah tidak berstatus pending.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'verified'
            ]);

            $invoice = $payment->invoice;
            
            // Check if invoice needs to change status
            $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
            
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => 'partial']);
            }

            // AUTO INCENTIVE GENERATION
            if ($payment->collector && $payment->collector->incentive_rate > 0) {
                \App\Models\Incentive::create([
                    'user_id' => $payment->collector_id,
                    'amount' => $payment->collector->incentive_rate,
                    'type' => 'auto',
                    'description' => 'Insentif penagihan Reseller Invoice: ' . $invoice->invoice_number,
                    'reference_id' => 'payment_' . $payment->id,
                    'incentive_date' => now(),
                    'status' => 'pending'
                ]);
            }

            DB::commit();
            return back()->with('success', 'Pelunasan berhasil disetujui. Saldo tagihan telah berkurang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pelunasan: ' . $e->getMessage());
        }
    }

    /**
     * Admin membatalkan setoran reseller
     */
    public function cancel(Request $request, Payment $payment)
    {
        DB::beginTransaction();
        try {
            $invoice = $payment->invoice;

            // Hapus insentif pending jika ada yang terhubung
            \App\Models\Incentive::where('reference_id', 'payment_' . $payment->id)
                ->where('status', 'pending')
                ->delete();

            $isVerified = ($payment->status === 'verified');

            // Hapus record pembayaran
            $payment->delete();

            // Jika pembayaran tadinya sudah verified, sinkronkan ulang status invoice
            if ($isVerified && $invoice) {
                $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
                if ($totalPaid >= $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'partial']);
                } else {
                    $invoice->update(['status' => 'unpaid']);
                }
            }

            DB::commit();
            return back()->with('success', 'Setoran penagih reseller berhasil dibatalkan/dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan setoran: ' . $e->getMessage());
        }
    }
}
