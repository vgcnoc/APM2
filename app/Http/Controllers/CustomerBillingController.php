<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Area;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerBillingController extends Controller
{
    /**
     * Menu Penagihan Lapangan (Untuk Penagih/Kolektor Pelanggan Reguler)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'unpaid');
        if (!in_array($activeTab, ['unpaid', 'overdue', 'paid'])) {
            $activeTab = 'unpaid';
        }

        $userAccessibleAreaIds = auth()->user()->getAccessibleAreaIds();

        // Helper untuk membangun query dengan filter area, desa, tanggal, dan search
        $buildFilteredQuery = function ($tabType) use ($request, $userAccessibleAreaIds) {
            $q = Invoice::where('is_reseller_balance', false);

            if (!empty($userAccessibleAreaIds)) {
                $q->whereHas('customer', fn($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
            }

            if ($request->filled('area_id')) {
                $areaId = $request->area_id;
                $q->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
            }

            // Filter Desa / Kelurahan berdasarkan alamat pelanggan
            if ($request->filled('desa')) {
                $desa = trim($request->desa);
                $desaNoSpace = str_replace(' ', '', $desa);
                $q->whereHas('customer', function($cq) use ($desa, $desaNoSpace) {
                    $cq->where(function($sub) use ($desa, $desaNoSpace) {
                        $sub->where('address', 'like', "%{$desa}%")
                            ->orWhere('address', 'like', "%{$desaNoSpace}%");
                    });
                });
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $q->where(function($sq) use ($search) {
                    $sq->where('invoice_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function($cq) use ($search) {
                           $cq->where('name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%")
                              ->orWhere('address', 'like', "%{$search}%");
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

        // Hitung statistik untuk ketiga tab sesuai filter aktif
        $unpaidItems = $buildFilteredQuery('unpaid')->with('payments')->get(['id', 'amount', 'status']);
        $unpaidCount = $unpaidItems->count();
        $unpaidTotal = (float) $unpaidItems->sum(function($inv) {
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

        // Uang yang sudah diterima hari ini
        $todayPaymentsQuery = Payment::whereDate('payment_date', Carbon::today())
            ->whereHas('invoice', fn($q) => $q->where('is_reseller_balance', false));

        if (!empty($userAccessibleAreaIds)) {
            $todayPaymentsQuery->whereHas('customer', fn($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
        }

        if ($request->filled('area_id')) {
            $todayPaymentsQuery->whereHas('customer', fn($c) => $c->where('area_id', $request->area_id));
        }

        if ($request->filled('desa')) {
            $desa = trim($request->desa);
            $desaNoSpace = str_replace(' ', '', $desa);
            $todayPaymentsQuery->whereHas('customer', function($cq) use ($desa, $desaNoSpace) {
                $cq->where(function($sub) use ($desa, $desaNoSpace) {
                    $sub->where('address', 'like', "%{$desa}%")
                        ->orWhere('address', 'like', "%{$desaNoSpace}%");
                });
            });
        }

        $todayVerifiedAmount = (float) (clone $todayPaymentsQuery)->where('status', 'verified')->sum('amount');
        $todayVerifiedCount = (clone $todayPaymentsQuery)->where('status', 'verified')->count();
        $todayPendingAmount = (float) (clone $todayPaymentsQuery)->where('status', 'pending')->sum('amount');
        $todayPendingCount = (clone $todayPaymentsQuery)->where('status', 'pending')->count();

        // Data Invoice dengan pagination dan eager loading customer detail
        $invoices = $buildFilteredQuery($activeTab)
            ->with(['customer.areaModel', 'customer.package', 'customer.surveys', 'customer.ont', 'payments'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($invoice) {
                $verifiedPaid = $invoice->payments->where('status', 'verified')->sum('amount');
                $pendingPaid = $invoice->payments->where('status', 'pending')->sum('amount');
                $customer = $invoice->customer;

                // Ekstraksi Foto KTP
                $identityPhotoUrl = null;
                if ($customer?->identity_photo) {
                    $identityPhotoUrl = str_starts_with($customer->identity_photo, 'http')
                        ? $customer->identity_photo
                        : asset('storage/' . $customer->identity_photo);
                }

                // Ekstraksi Foto Rumah (dari survey atau ONT)
                $housePhotoUrl = null;
                if ($customer?->surveys) {
                    foreach ($customer->surveys as $survey) {
                        if (is_array($survey->photos)) {
                            foreach ($survey->photos as $p) {
                                $label = is_array($p) ? ($p['label'] ?? '') : '';
                                $path = is_array($p) ? ($p['path'] ?? '') : (is_string($p) ? $p : '');
                                if (stripos($label, 'rumah') !== false && !empty($path)) {
                                    $housePhotoUrl = str_starts_with($path, 'http') ? $path : asset('storage/' . $path);
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if (!$housePhotoUrl && $customer?->ont?->photo_customer) {
                    $path = $customer->ont->photo_customer;
                    $housePhotoUrl = str_starts_with($path, 'http') ? $path : asset('storage/' . $path);
                }
                if (!$housePhotoUrl && $customer?->ont?->photo_installation) {
                    $path = $customer->ont->photo_installation;
                    $housePhotoUrl = str_starts_with($path, 'http') ? $path : asset('storage/' . $path);
                }

                $customerDetails = [
                    'id' => $customer?->id,
                    'customer_code' => $customer?->customer_code,
                    'name' => $customer?->name,
                    'phone' => $customer?->phone,
                    'address' => $customer?->address,
                    'area' => $customer?->areaModel?->name ?? $customer?->area,
                    'package' => $customer?->package?->name,
                    'latitude' => $customer?->latitude,
                    'longitude' => $customer?->longitude,
                    'identity_photo_url' => $identityPhotoUrl,
                    'house_photo_url' => $housePhotoUrl,
                ];
                
                return array_merge($invoice->toArray(), [
                    'remaining' => max(0, $invoice->amount - $verifiedPaid),
                    'total_paid' => $verifiedPaid,
                    'pending_amount' => $pendingPaid,
                    'effective_remaining' => max(0, $invoice->amount - $verifiedPaid - $pendingPaid),
                    'customer_details' => $customerDetails,
                ]);
            });

        // Daftar Area untuk dropdown
        $areasQuery = Area::orderBy('name');
        if (!empty($userAccessibleAreaIds)) {
            $areasQuery->whereIn('id', $userAccessibleAreaIds);
        }
        $areas = $areasQuery->get(['id', 'name']);

        // Mapping desa dinamis mengikuti data pelanggan yang di-input saat booking per area
        $areaDesasMap = [];
        $allDesasCollection = collect();

        foreach ($areas as $areaItem) {
            $areaId = $areaItem->id;
            $dList = collect();

            // Pindai data pelanggan hasil booking di area ini (cocokkan via area_id atau nama area)
            $areaCustAddrs = Customer::where(function($q) use ($areaId, $areaItem) {
                $q->where('area_id', $areaId)
                  ->orWhere('area', $areaItem->name);
            })->whereNotNull('address')->pluck('address');

            foreach ($areaCustAddrs as $addr) {
                // Ekstraksi pola desa yang diinput saat booking: Kel. / Kelurahan / Desa <nama_desa>
                if (preg_match_all('/(?:Kel\.?|Kelurahan|Desa)\s*([a-zA-Z0-9\s]+?)(?=\s+(?:Kec|Kab|RT|RW|No|\d)|[,\n\r\.\-]|$)/i', $addr, $matches)) {
                    foreach ($matches[1] as $m) {
                        $val = trim($m);
                        if (strlen($val) >= 2 && strlen($val) <= 40 && !in_array(strtolower($val), ['kec', 'kab', 'rt', 'rw', 'jalan', 'jl'])) {
                            $dList->push(ucwords(strtolower($val)));
                        }
                    }
                }
            }

            $cleanList = $dList->unique()->sort()->values()->all();
            $areaDesasMap[$areaId] = $cleanList;
            $allDesasCollection = $allDesasCollection->merge($cleanList);
        }

        // Pindai juga semua pelanggan lain untuk pilihan "Semua Wilayah"
        $allCustAddrs = Customer::whereNotNull('address')->pluck('address');
        foreach ($allCustAddrs as $addr) {
            if (preg_match_all('/(?:Kel\.?|Kelurahan|Desa)\s*([a-zA-Z0-9\s]+?)(?=\s+(?:Kec|Kab|RT|RW|No|\d)|[,\n\r\.\-]|$)/i', $addr, $matches)) {
                foreach ($matches[1] as $m) {
                    $val = trim($m);
                    if (strlen($val) >= 2 && strlen($val) <= 40 && !in_array(strtolower($val), ['kec', 'kab', 'rt', 'rw', 'jalan', 'jl'])) {
                        $allDesasCollection->push(ucwords(strtolower($val)));
                    }
                }
            }
        }

        $allDesas = $allDesasCollection->unique()->sort()->values()->all();

        // Jika ada filter area_id aktif, opsi desa disesuaikan dengan area tersebut
        $activeDesas = $request->filled('area_id') && isset($areaDesasMap[$request->area_id])
            ? $areaDesasMap[$request->area_id]
            : $allDesas;

        return Inertia::render('CustomerBilling/Index', [
            'invoices' => $invoices,
            'activeTab' => $activeTab,
            'areas' => $areas,
            'desas' => $activeDesas,
            'area_desas' => $areaDesasMap,
            'filters' => [
                'tab' => $activeTab,
                'area_id' => $request->area_id ?? '',
                'desa' => $request->desa ?? '',
                'start_date' => $request->start_date ?? '',
                'end_date' => $request->end_date ?? '',
                'search' => $request->search ?? '',
            ],
            'stats' => [
                'unpaid' => [
                    'total' => $unpaidTotal,
                    'count' => $unpaidCount,
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
     * Penagih menerima uang di lapangan (Submit Pembayaran Pending)
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
            return back()->with('success', 'Penerimaan pembayaran dari pelanggan berhasil dicatat. Menunggu validasi admin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menu Validasi Setoran Pelanggan (Untuk Admin)
     */
    public function settlements(Request $request)
    {
        $activeTab = $request->get('tab', 'pending');
        
        $query = Payment::with(['invoice.payments', 'customer', 'collector'])
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
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

        return Inertia::render('CustomerBilling/Settlements', [
            'payments' => $payments,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Admin menyetujui setoran penagih
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
            
            // Sync status invoice
            $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
            
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => 'partial']);
            }

            // AUTO INCENTIVE GENERATION
            // Jika penagih (collector) memiliki incentive_rate > 0, berikan insentif
            if ($payment->collector && $payment->collector->incentive_rate > 0) {
                \App\Models\Incentive::create([
                    'user_id' => $payment->collector_id,
                    'amount' => $payment->collector->incentive_rate,
                    'type' => 'auto',
                    'description' => 'Insentif penagihan Invoice: ' . $invoice->invoice_number,
                    'reference_id' => 'payment_' . $payment->id,
                    'incentive_date' => now(),
                    'status' => 'pending'
                ]);
            }

            DB::commit();
            return back()->with('success', 'Setoran pelanggan berhasil divalidasi dan tersinkronisasi ke Invoice.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses validasi: ' . $e->getMessage());
        }
    }
}
