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
                ->where(function($sub) {
                    $sub->whereHas('customer', function($cq) {
                        $cq->where('is_reseller', true)
                           ->orWhereHas('reseller');
                    })->orWhereDoesntHave('customer');
                });

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
                       ->orWhere('notes', 'like', "%{$search}%")
                       ->orWhereHas('customer', function($cq) use ($search) {
                           $cq->where('name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%")
                              ->orWhereHas('reseller.user', function($ruq) use ($search) {
                                  $ruq->where('name', 'like', "%{$search}%")
                                      ->orWhere('phone', 'like', "%{$search}%");
                              });
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
            ->with(['customer.areaModel', 'customer.reseller.user', 'payments.collector'])
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

        $verifiedPaid = (float) $invoice->payments()->where('status', 'verified')->sum('amount');
        $pendingPaid = (float) $invoice->payments()->where('status', 'pending')->sum('amount');
        $effectiveRemaining = max(0, (float) $invoice->amount - $verifiedPaid - $pendingPaid);

        if ($validated['amount'] > $effectiveRemaining) {
            $msg = 'Nominal melebihi sisa tagihan yang dapat diterima (Rp ' . number_format($effectiveRemaining, 0, ',', '.') . ').';
            if ($pendingPaid > 0) {
                $msg .= ' Ada setoran pending sebesar Rp ' . number_format($pendingPaid, 0, ',', '.') . ' yang menunggu validasi.';
            }
            return back()->with('error', $msg);
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
        $user = auth()->user();
        $userAccessibleAreaIds = [];
        if ($user && method_exists($user, 'accessibleAreas')) {
            $userAccessibleAreaIds = $user->accessibleAreas()->pluck('areas.id')->toArray();
        } elseif ($user && !empty($user->area_id)) {
            $userAccessibleAreaIds = [$user->area_id];
        }

        $activeTab = $request->get('tab', 'pending');
        
        $baseQuery = Payment::whereHas('invoice', function($q) {
            $q->where('is_reseller_balance', true);
        });

        if (!empty($userAccessibleAreaIds) && !$user->hasRole(['super admin', 'admin', 'keuangan'])) {
            $baseQuery->whereHas('customer', fn($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
        }

        if ($request->filled('area_id')) {
            $baseQuery->whereHas('customer', fn($c) => $c->where('area_id', $request->area_id));
        }

        $todayStr = now()->toDateString();
        $stats = [
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'pending_amount' => (float) (clone $baseQuery)->where('status', 'pending')->sum('amount'),
            'today_verified_count' => (clone $baseQuery)->where('status', 'verified')->whereDate('updated_at', $todayStr)->count(),
            'today_verified_amount' => (float) (clone $baseQuery)->where('status', 'verified')->whereDate('updated_at', $todayStr)->sum('amount'),
            'month_verified_count' => (clone $baseQuery)->where('status', 'verified')->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->count(),
            'month_verified_amount' => (float) (clone $baseQuery)->where('status', 'verified')->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'total_verified_count' => (clone $baseQuery)->where('status', 'verified')->count(),
            'total_verified_amount' => (float) (clone $baseQuery)->where('status', 'verified')->sum('amount'),
        ];

        $query = Payment::with([
                'invoice.payments', 
                'customer.areaModel', 
                'customer.reseller.user', 
                'collector'
            ])
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', true);
            });

        if (!empty($userAccessibleAreaIds) && !$user->hasRole(['super admin', 'admin', 'keuangan'])) {
            $query->whereHas('customer', fn($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
        }

        if ($request->filled('area_id')) {
            $query->whereHas('customer', fn($c) => $c->where('area_id', $request->area_id));
        }

        if ($activeTab === 'verified') {
            $query->where('status', 'verified');
        } else {
            $query->where('status', 'pending');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('customer_code', 'like', "%{$search}%")
                       ->orWhereHas('reseller.user', function($ruq) use ($search) {
                           $ruq->where('name', 'like', "%{$search}%")
                               ->orWhere('phone', 'like', "%{$search}%");
                       });
                })->orWhereHas('invoice', function($iq) use ($search) {
                    $iq->where('invoice_number', 'like', "%{$search}%")
                       ->orWhere('notes', 'like', "%{$search}%");
                })->orWhereHas('collector', function($colq) use ($search) {
                    $colq->where('name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Filter Petugas Penagih
        if ($request->filled('collector_id')) {
            $query->where('collected_by', $request->collector_id);
        }

        // Filter Metode Pembayaran
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('payment_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('payment_date', '<=', $request->end_date);
        }
            
        $payments = $query->latest('id')->paginate(15)
            ->withQueryString()
            ->through(function ($payment) {
                if ($payment->invoice) {
                    $verifiedPaid = (float) $payment->invoice->payments->where('status', 'verified')->sum('amount');
                    $pendingPaid = (float) $payment->invoice->payments->where('status', 'pending')->sum('amount');
                    
                    $payment->invoice->setAttribute('remaining', max(0, (float)$payment->invoice->amount - $verifiedPaid));
                    $payment->invoice->setAttribute('total_paid', $verifiedPaid);
                    $payment->invoice->setAttribute('pending_amount', $pendingPaid);
                    $payment->invoice->setAttribute('effective_remaining', max(0, (float)$payment->invoice->amount - $verifiedPaid - $pendingPaid));
                }

                $resellerName = $payment->customer?->name 
                    ?? $payment->invoice?->customer?->name 
                    ?? $payment->customer?->reseller?->user?->name 
                    ?? $payment->invoice?->customer?->reseller?->user?->name;

                $resellerPhone = $payment->customer?->phone 
                    ?? $payment->invoice?->customer?->phone 
                    ?? $payment->customer?->reseller?->user?->phone 
                    ?? $payment->invoice?->customer?->reseller?->user?->phone;

                $resellerCode = $payment->customer?->customer_code
                    ?? $payment->invoice?->customer?->customer_code
                    ?? ('MITRA-' . ($payment->customer_id ?? $payment->id));

                $payment->setAttribute('reseller_name', $resellerName ?: ('Reseller #' . ($payment->customer_id ?? 'Unknown')));
                $payment->setAttribute('reseller_phone', $resellerPhone ?: '-');
                $payment->setAttribute('reseller_code', $resellerCode);

                return $payment;
            });

        // Daftar penagih untuk opsi filter
        $collectors = \App\Models\User::forAssignment()
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereHas('roles', fn($rq) => $rq->whereIn('name', ['penagih', 'admin', 'super admin', 'biller', 'teknisi']))
                  ->orWhereIn('id', Payment::select('collected_by')->whereNotNull('collected_by'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $areasQuery = Area::orderBy('name');
        if (!empty($userAccessibleAreaIds)) {
            $areasQuery->whereIn('id', $userAccessibleAreaIds);
        }
        $areas = $areasQuery->get(['id', 'name']);

        return Inertia::render('ResellerBilling/Settlements', [
            'payments' => $payments,
            'activeTab' => $activeTab,
            'stats' => $stats,
            'filters' => $request->only(['search', 'collector_id', 'payment_method', 'start_date', 'end_date', 'tab', 'area_id']),
            'collectors' => $collectors,
            'areas' => $areas,
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
            if ($invoice) {
                $totalPaid = (float) $invoice->payments()->where('status', 'verified')->sum('amount');
                
                if ($totalPaid >= (float) $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'partial']);
                }
            }

            // AUTO INCENTIVE GENERATION
            $collectorId = $payment->collected_by ?? $payment->collector?->id;
            if ($payment->collector && $payment->collector->incentive_rate > 0 && $collectorId) {
                \App\Models\Incentive::create([
                    'user_id' => $collectorId,
                    'amount' => $payment->collector->incentive_rate,
                    'type' => 'auto',
                    'description' => 'Insentif penagihan Reseller Invoice: ' . ($invoice ? $invoice->invoice_number : ('#' . $payment->id)),
                    'reference_id' => 'payment_' . $payment->id,
                    'incentive_date' => now(),
                    'status' => 'pending'
                ]);
            }

            DB::commit();
            return back()->with('success', 'Pelunasan setoran sebesar Rp ' . number_format($payment->amount, 0, ',', '.') . ' berhasil disetujui. Saldo tagihan telah berkurang.');
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
            $amount = $payment->amount;

            // Hapus insentif pending jika ada yang terhubung
            \App\Models\Incentive::where('reference_id', 'payment_' . $payment->id)
                ->where('status', 'pending')
                ->delete();

            $isVerified = ($payment->status === 'verified');

            // Hapus record pembayaran
            $payment->delete();

            // Jika pembayaran tadinya sudah verified, sinkronkan ulang status invoice
            if ($isVerified && $invoice) {
                $totalPaid = (float) $invoice->payments()->where('status', 'verified')->sum('amount');
                if ($totalPaid >= (float) $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'partial']);
                } else {
                    $invoice->update(['status' => 'unpaid']);
                }
            }

            DB::commit();
            return back()->with('success', 'Setoran penagih reseller sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil dibatalkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan setoran: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Tagihan Kasbon Reseller
     */
    public function destroy(Request $request, Invoice $invoice)
    {
        DB::beginTransaction();
        try {
            $adjustBalance = filter_var($request->input('adjust_balance', false), FILTER_VALIDATE_BOOLEAN);

            // 1. Jika ada pembayaran atau cicilan terkait, hapus record dan insentifnya
            if ($invoice->payments()->exists()) {
                foreach ($invoice->payments as $payment) {
                    \App\Models\Incentive::where('reference_id', 'payment_' . $payment->id)->delete();
                }
                $invoice->payments()->delete();
            }

            // 2. Jika dipilih tarik saldo reseller
            if ($adjustBalance) {
                $reseller = \App\Models\Reseller::where('customer_id', $invoice->customer_id)->first();
                if ($reseller) {
                    if ($reseller->balance < $invoice->amount) {
                        return back()->with('error', 'Tidak dapat menarik saldo: Saldo reseller saat ini (Rp ' . number_format($reseller->balance, 0, ',', '.') . ') lebih kecil dari nominal kasbon (Rp ' . number_format($invoice->amount, 0, ',', '.') . '). Kurang Rp ' . number_format($invoice->amount - $reseller->balance, 0, ',', '.') . '.');
                    }
                    $reseller->balance -= $invoice->amount;
                    $reseller->save();

                    // Hapus transaksi mutasi kredit terkait kasbon jika ada
                    if (preg_match('/(?:REQ|TOPUP)-(\d+)/', $invoice->notes ?? '', $matches)) {
                        $reqId = $matches[1];
                        \App\Models\ResellerTransaction::where('reseller_id', $reseller->id)
                            ->where(function($q) use ($reqId) {
                                $q->where('reference_id', 'TOPUP-' . $reqId)
                                  ->orWhere('reference_id', 'REQ-' . $reqId);
                            })->delete();

                        \App\Models\ResellerBalanceRequest::where('id', $reqId)->update([
                            'status' => 'rejected',
                            'notes' => trim(($invoice->notes ?? '') . ' [Tagihan Kasbon Dihapus]')
                        ]);
                    }
                }
            }

            $invoiceNumber = $invoice->invoice_number;
            $invoice->delete();

            DB::commit();
            return back()->with('success', "Tagihan kasbon #{$invoiceNumber} berhasil dihapus" . ($adjustBalance ? ' dan saldo reseller telah disesuaikan.' : '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus tagihan kasbon: ' . $e->getMessage());
        }
    }
}
