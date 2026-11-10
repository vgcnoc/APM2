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
        if (!in_array($activeTab, ['unpaid', 'partial', 'promise', 'overdue', 'paid'])) {
            $activeTab = 'unpaid';
        }

        $userAccessibleAreaIds = auth()->user()->getAccessibleAreaIds();

        // Helper untuk membangun query dengan filter area, desa, tanggal, dan search
        $buildFilteredQuery = function ($tabType) use ($request, $userAccessibleAreaIds) {
            $q = Invoice::where('is_reseller_balance', false)
                ->where('invoice_number', 'not like', 'INV-MIG-%')
                ->where('status', '!=', 'cancelled')
                ->where(function($sq) {
                    $sq->where('period_year', '>', 2026)
                      ->orWhere(function($sub) {
                          $sub->where('period_year', 2026)->where('period_month', '>', 10);
                      })
                      ->orWhere(function($sub) {
                          $sub->where('is_prorata', true)
                              ->orWhere('billing_type', 'prorata');
                      });
                })
                ->whereHas('customer', fn($c) => $c->where('status', '!=', 'terminated')->whereNotIn('service_status', ['gratis', 'free_home']));

            // Terapkan penyamaran data penagihan jika aktif untuk akun ini
            \App\Services\DataPrivacyMaskingService::applyInvoiceFilter($q, 'invoices.customer_id', null, 'billing');

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

        // ── Evaluasi Hak Akses Admin vs Karyawan & Nominal Terima Setoran Petugas ──
        $user = auth()->user();
        $userId = $user ? $user->id : 0;
        $userRole = strtolower($user->role ?? '');
        $isAdmin = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin', 'finance', 'manager', 'management'])
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super admin', 'finance', 'manager', 'management']));

        // Hitung nominal uang yang ditagih / diterima oleh akun petugas ini (collected_by)
        $myPaymentsQuery = Payment::where('collected_by', $userId)
            ->whereHas('invoice', fn($q) => $q->where('is_reseller_balance', false));

        $todayDate = Carbon::today()->toDateString();
        $currentMonth = Carbon::today()->month;
        $currentYear = Carbon::today()->year;

        // 1. Terima Setoran Hari Ini - Menunggu Validasi (Fisik uang tunai di tangan penagih)
        $myTodayPendingAmount = (float) (clone $myPaymentsQuery)
            ->whereDate('payment_date', $todayDate)
            ->where('status', 'pending')
            ->sum('amount');
        $myTodayPendingCount = (clone $myPaymentsQuery)
            ->whereDate('payment_date', $todayDate)
            ->where('status', 'pending')
            ->count();

        // 2. Terima Setoran Hari Ini - Terverifikasi (Sudah disetorkan & divalidasi kantor hari ini)
        $myTodayVerifiedAmount = (float) (clone $myPaymentsQuery)
            ->whereDate('payment_date', $todayDate)
            ->where('status', 'verified')
            ->sum('amount');
        $myTodayVerifiedCount = (clone $myPaymentsQuery)
            ->whereDate('payment_date', $todayDate)
            ->where('status', 'verified')
            ->count();

        // 3. Total Terima Setoran Hari Ini (Pending + Verified)
        $myTodayTotalAmount = $myTodayPendingAmount + $myTodayVerifiedAmount;
        $myTodayTotalCount = $myTodayPendingCount + $myTodayVerifiedCount;

        // 4. Terima Setoran Bulan Ini
        $myMonthAmount = (float) (clone $myPaymentsQuery)
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', $currentMonth)
            ->sum('amount');
        $myMonthCount = (clone $myPaymentsQuery)
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', $currentMonth)
            ->count();

        // 5. Total Seluruh Riwayat Setoran Petugas
        $myAllAmount = (float) (clone $myPaymentsQuery)->sum('amount');
        $myAllCount = (clone $myPaymentsQuery)->count();

        $collectorStats = [
            'today_pending_amount' => $myTodayPendingAmount,
            'today_pending_count' => $myTodayPendingCount,
            'today_verified_amount' => $myTodayVerifiedAmount,
            'today_verified_count' => $myTodayVerifiedCount,
            'today_total_amount' => $myTodayTotalAmount,
            'today_total_count' => $myTodayTotalCount,
            'month_amount' => $myMonthAmount,
            'month_count' => $myMonthCount,
            'all_amount' => $myAllAmount,
            'all_count' => $myAllCount,
        ];

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

                // Ekstraksi Foto Rumah (dari customer langsung, atau survey, atau ONT)
                $housePhotoUrl = null;
                if ($customer?->house_photo) {
                    $housePhotoUrl = str_starts_with($customer->house_photo, 'http')
                        ? $customer->house_photo
                        : asset('storage/' . $customer->house_photo);
                }
                if (!$housePhotoUrl && $customer?->surveys) {
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
            'is_admin' => $isAdmin,
            'collector_stats' => $collectorStats,
            'filters' => [
                'tab' => $activeTab,
                'area_id' => $request->area_id ?? '',
                'desa' => $request->desa ?? '',
                'start_date' => $request->start_date ?? '',
                'end_date' => $request->end_date ?? '',
                'search' => $request->search ?? '',
            ],
            'stats' => [
                // Jika bukan admin (akun karyawan/petugas), sembunyikan nominal total belum lunas perusahaan
                'unpaid' => [
                    'total' => $isAdmin ? $unpaidTotal : 0,
                    'count' => $unpaidCount,
                ],
                'partial' => [
                    'total' => $isAdmin ? $partialTotal : 0,
                    'count' => $partialCount,
                ],
                'promise' => [
                    'total' => $isAdmin ? $promiseTotal : 0,
                    'count' => $promiseCount,
                ],
                'overdue' => [
                    'total' => $isAdmin ? $overdueTotal : 0,
                    'count' => $overdueCount,
                ],
                'paid' => [
                    'total' => $isAdmin ? $paidTotal : 0,
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
                    if ($invoice->customer) app(\App\Services\BillingIsolationService::class)->releaseAfterPayment($invoice->customer);
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
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $isAdmin = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin', 'finance', 'manager', 'management'])
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super admin', 'finance', 'manager', 'management']));

        $activeTab = $request->get('tab', 'pending');
        
        $baseQuery = Payment::whereHas('invoice', function($q) {
            $q->where('is_reseller_balance', false);
        });

        if (!$isAdmin && $user) {
            $baseQuery->where('collected_by', $user->id);
        }

        // Hitung statistik untuk cards ringkasan
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

        $query = Payment::with(['invoice.payments', 'customer', 'collector'])
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
            });

        if (!$isAdmin && $user) {
            $query->where('collected_by', $user->id);
        }

        if ($activeTab === 'verified') {
            $query->where('status', 'verified');
        } else {
            $query->where('status', 'pending');
        }

        // Filter Pencarian (nama pelanggan, no invoice, penagih, catatan)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('customer_code', 'like', "%{$search}%");
                })->orWhereHas('invoice', function($iq) use ($search) {
                    $iq->where('invoice_number', 'like', "%{$search}%");
                })->orWhereHas('collector', function($colq) use ($search) {
                    $colq->where('name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
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

        // Filter Rentang Tanggal Terima
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('payment_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('payment_date', '<=', $request->end_date);
        }
            
        $payments = $query->latest('id')->paginate(10)
            ->withQueryString()
            ->through(function ($payment) {
                if ($payment->invoice) {
                    $verifiedPaid = $payment->invoice->payments->where('status', 'verified')->sum('amount');
                    $pendingPaid = $payment->invoice->payments->where('status', 'pending')->sum('amount');
                    
                    $payment->invoice->setAttribute('remaining', max(0, $payment->invoice->amount - $verifiedPaid));
                    $payment->invoice->setAttribute('total_paid', $verifiedPaid);
                    $payment->invoice->setAttribute('pending_amount', $pendingPaid);
                }
                return $payment;
            });

        // Daftar penagih untuk opsi filter
        $collectors = \App\Models\User::where('is_active', true)
            ->where(function($q) {
                $q->whereHas('roles', fn($rq) => $rq->whereIn('name', ['penagih', 'admin', 'super admin', 'biller', 'teknisi']))
                  ->orWhereIn('id', Payment::select('collected_by')->whereNotNull('collected_by'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('CustomerBilling/Settlements', [
            'payments' => $payments,
            'activeTab' => $activeTab,
            'stats' => $stats,
            'is_admin' => $isAdmin,
            'filters' => $request->only(['search', 'collector_id', 'payment_method', 'start_date', 'end_date', 'tab']),
            'collectors' => $collectors,
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
                if ($invoice->customer) app(\App\Services\BillingIsolationService::class)->releaseAfterPayment($invoice->customer);
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

    /**
     * Admin membatalkan setoran penagih
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
            return back()->with('success', 'Setoran penagih berhasil dibatalkan/dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan setoran: ' . $e->getMessage());
        }
    }

    /**
     * Cetak (Print) & Download PDF Laporan Penagihan Pelanggan / Lembar Kerja Kolektor / Rekap Setoran
     */
    public function exportReport(Request $request)
    {
        $activeTab = $request->get('tab', 'unpaid');
        if (!in_array($activeTab, ['unpaid', 'partial', 'promise', 'overdue', 'paid'])) {
            $activeTab = 'unpaid';
        }

        $scope = $request->get('scope', 'active');
        if (!in_array($scope, ['active', 'collector_sheet', 'my_collections', 'all_summary'])) {
            $scope = 'active';
        }

        $isPdf = ($request->get('mode') === 'pdf');
        $user = auth()->user();
        $userId = $user ? $user->id : 0;
        $userRole = strtolower($user->role ?? '');
        $isAdmin = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin', 'finance', 'manager', 'management'])
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super admin', 'finance', 'manager', 'management']));

        $userAccessibleAreaIds = $user ? $user->getAccessibleAreaIds() : [];

        $buildFilteredQuery = function ($tabType) use ($request, $userAccessibleAreaIds) {
            $q = Invoice::where('is_reseller_balance', false)
                ->where('invoice_number', 'not like', 'INV-MIG-%')
                ->where('status', '!=', 'cancelled')
                ->where(function ($sq) {
                    $sq->where('period_year', '>', 2026)
                        ->orWhere(function ($sub) {
                            $sub->where('period_year', 2026)->where('period_month', '>', 10);
                        })
                        ->orWhere(function ($sub) {
                            $sub->where('is_prorata', true)
                                ->orWhere('billing_type', 'prorata');
                        });
                })
                ->whereHas('customer', fn ($c) => $c->where('status', '!=', 'terminated')->whereNotIn('service_status', ['gratis', 'free_home']));

            if (!empty($userAccessibleAreaIds)) {
                $q->whereHas('customer', fn ($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
            }

            if ($request->filled('area_id')) {
                $areaId = $request->area_id;
                $q->whereHas('customer', fn ($c) => $c->where('area_id', $areaId));
            }

            if ($request->filled('desa')) {
                $desa = trim($request->desa);
                $desaNoSpace = str_replace(' ', '', $desa);
                $q->whereHas('customer', function ($cq) use ($desa, $desaNoSpace) {
                    $cq->where(function ($sub) use ($desa, $desaNoSpace) {
                        $sub->where('address', 'like', "%{$desa}%")
                            ->orWhere('address', 'like', "%{$desaNoSpace}%");
                    });
                });
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $q->where(function ($sq) use ($search) {
                    $sq->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        });
                });
            }

            if ($tabType === 'paid') {
                if ($request->filled('start_date')) {
                    $q->whereHas('payments', fn ($pq) => $pq->where('status', 'verified')->whereDate('payment_date', '>=', $request->start_date));
                }
                if ($request->filled('end_date')) {
                    $q->whereHas('payments', fn ($pq) => $pq->where('status', 'verified')->whereDate('payment_date', '<=', $request->end_date));
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
            } else {
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

        // Statistik KPI
        $unpaidItems = $buildFilteredQuery('unpaid')->with('payments')->get(['id', 'amount', 'status']);
        $unpaidCount = $unpaidItems->count();
        $unpaidTotal = (float) $unpaidItems->sum(fn ($inv) => max(0, $inv->amount - $inv->payments->where('status', 'verified')->sum('amount')));

        $partialItems = $buildFilteredQuery('partial')->with('payments')->get(['id', 'amount', 'status']);
        $partialCount = $partialItems->count();
        $partialTotal = (float) $partialItems->sum(fn ($inv) => max(0, $inv->amount - $inv->payments->where('status', 'verified')->sum('amount')));

        $promiseItems = $buildFilteredQuery('promise')->with('payments')->get(['id', 'amount', 'status']);
        $promiseCount = $promiseItems->count();
        $promiseTotal = (float) $promiseItems->sum(fn ($inv) => max(0, $inv->amount - $inv->payments->where('status', 'verified')->sum('amount')));

        $overdueItems = $buildFilteredQuery('overdue')->with('payments')->get(['id', 'amount', 'status']);
        $overdueCount = $overdueItems->count();
        $overdueTotal = (float) $overdueItems->sum(fn ($inv) => max(0, $inv->amount - $inv->payments->where('status', 'verified')->sum('amount')));

        $paidItems = $buildFilteredQuery('paid')->with('payments')->get(['id', 'amount', 'status']);
        $paidCount = $paidItems->count();
        $paidTotal = (float) $paidItems->sum('amount');

        $todayPaymentsQuery = Payment::whereDate('payment_date', Carbon::today())
            ->whereHas('invoice', fn ($q) => $q->where('is_reseller_balance', false));
        if (!empty($userAccessibleAreaIds)) {
            $todayPaymentsQuery->whereHas('customer', fn ($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
        }
        if ($request->filled('area_id')) {
            $todayPaymentsQuery->whereHas('customer', fn ($c) => $c->where('area_id', $request->area_id));
        }
        if ($request->filled('desa')) {
            $desa = trim($request->desa);
            $desaNoSpace = str_replace(' ', '', $desa);
            $todayPaymentsQuery->whereHas('customer', function ($cq) use ($desa, $desaNoSpace) {
                $cq->where(function ($sub) use ($desa, $desaNoSpace) {
                    $sub->where('address', 'like', "%{$desa}%")
                        ->orWhere('address', 'like', "%{$desaNoSpace}%");
                });
            });
        }

        $todayVerifiedAmount = (float) (clone $todayPaymentsQuery)->where('status', 'verified')->sum('amount');
        $todayVerifiedCount = (clone $todayPaymentsQuery)->where('status', 'verified')->count();
        $todayPendingAmount = (float) (clone $todayPaymentsQuery)->where('status', 'pending')->sum('amount');
        $todayPendingCount = (clone $todayPaymentsQuery)->where('status', 'pending')->count();

        $myPaymentsQuery = Payment::where('collected_by', $userId)
            ->whereHas('invoice', fn ($q) => $q->where('is_reseller_balance', false));
        $todayDate = Carbon::today()->toDateString();
        $currentMonth = Carbon::today()->month;
        $currentYear = Carbon::today()->year;

        $myTodayPendingAmount = (float) (clone $myPaymentsQuery)->whereDate('payment_date', $todayDate)->where('status', 'pending')->sum('amount');
        $myTodayPendingCount = (clone $myPaymentsQuery)->whereDate('payment_date', $todayDate)->where('status', 'pending')->count();
        $myTodayVerifiedAmount = (float) (clone $myPaymentsQuery)->whereDate('payment_date', $todayDate)->where('status', 'verified')->sum('amount');
        $myTodayVerifiedCount = (clone $myPaymentsQuery)->whereDate('payment_date', $todayDate)->where('status', 'verified')->count();
        $myMonthAmount = (float) (clone $myPaymentsQuery)->whereYear('payment_date', $currentYear)->whereMonth('payment_date', $currentMonth)->sum('amount');
        $myMonthCount = (clone $myPaymentsQuery)->whereYear('payment_date', $currentYear)->whereMonth('payment_date', $currentMonth)->count();
        $myAllAmount = (float) (clone $myPaymentsQuery)->sum('amount');
        $myAllCount = (clone $myPaymentsQuery)->count();

        $collectorStats = [
            'today_pending_amount' => $myTodayPendingAmount,
            'today_pending_count' => $myTodayPendingCount,
            'today_verified_amount' => $myTodayVerifiedAmount,
            'today_verified_count' => $myTodayVerifiedCount,
            'today_total_amount' => $myTodayPendingAmount + $myTodayVerifiedAmount,
            'today_total_count' => $myTodayPendingCount + $myTodayVerifiedCount,
            'month_amount' => $myMonthAmount,
            'month_count' => $myMonthCount,
            'all_amount' => $myAllAmount,
            'all_count' => $myAllCount,
        ];

        $stats = [
            'unpaid' => ['total' => $unpaidTotal, 'count' => $unpaidCount],
            'partial' => ['total' => $partialTotal, 'count' => $partialCount],
            'promise' => ['total' => $promiseTotal, 'count' => $promiseCount],
            'overdue' => ['total' => $overdueTotal, 'count' => $overdueCount],
            'paid' => ['total' => $paidTotal, 'count' => $paidCount],
            'today' => [
                'verified_amount' => $todayVerifiedAmount,
                'verified_count' => $todayVerifiedCount,
                'pending_amount' => $todayPendingAmount,
                'pending_count' => $todayPendingCount,
                'total_amount' => $todayVerifiedAmount + $todayPendingAmount,
            ],
        ];

        $mapInvoiceToRow = function ($invoice) {
            $verifiedPaid = (float) $invoice->payments->where('status', 'verified')->sum('amount');
            $pendingPaid = (float) $invoice->payments->where('status', 'pending')->sum('amount');
            $customer = $invoice->customer;
            $lastPaidPayment = $invoice->payments->where('status', 'verified')->sortByDesc('payment_date')->first();

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'period_label' => $invoice->period_label,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
                'total_paid' => $verifiedPaid,
                'pending_amount' => $pendingPaid,
                'remaining' => max(0, (float) $invoice->amount - $verifiedPaid),
                'due_date_formatted' => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d/m/Y') : '-',
                'promise_date' => $invoice->promise_date,
                'promise_date_formatted' => $invoice->promise_date ? Carbon::parse($invoice->promise_date)->format('d/m/Y') : null,
                'paid_date_formatted' => $lastPaidPayment && $lastPaidPayment->payment_date ? Carbon::parse($lastPaidPayment->payment_date)->format('d/m/Y') : null,
                'customer_name' => $customer?->name ?? 'Pelanggan',
                'customer_code' => $customer?->customer_code ?? '-',
                'customer_phone' => $customer?->phone ?? '-',
                'customer_address' => $customer?->address ?? '-',
                'customer_area' => $customer?->areaModel?->name ?? $customer?->area ?? '-',
                'customer_package' => $customer?->package?->name ?? '-',
            ];
        };

        // Data baris untuk tab aktif
        $invoiceRows = [];
        if ($scope === 'active' || $scope === 'all_summary') {
            $invoiceRows = $buildFilteredQuery($activeTab)
                ->with(['customer.areaModel', 'customer.package', 'payments'])
                ->latest('id')
                ->limit(600)
                ->get()
                ->map($mapInvoiceToRow)
                ->values()
                ->all();
        }

        // Data baris untuk Lembar Kerja Kolektor Lapangan (Tagihan Belum Lunas)
        $collectorSheetInvoices = [];
        if ($scope === 'collector_sheet') {
            $sheetTab = in_array($activeTab, ['unpaid', 'partial', 'promise', 'overdue']) ? $activeTab : 'unpaid';
            $collectorSheetInvoices = $buildFilteredQuery($sheetTab)
                ->with(['customer.areaModel', 'customer.package', 'payments'])
                ->get()
                ->sortBy(fn ($inv) => ($inv->customer?->areaModel?->name ?? '') . '|' . ($inv->customer?->address ?? '') . '|' . ($inv->customer?->name ?? ''))
                ->take(600)
                ->map($mapInvoiceToRow)
                ->values()
                ->all();
        }

        // Data baris untuk Rekap Setoran Saya / Petugas
        $myCollectionRows = [];
        if ($scope === 'my_collections' || $scope === 'all_summary') {
            $colQuery = Payment::with(['invoice', 'customer.areaModel', 'collector'])
                ->whereHas('invoice', fn ($q) => $q->where('is_reseller_balance', false));

            if (!$isAdmin) {
                $colQuery->where('collected_by', $userId);
            } elseif (!empty($userAccessibleAreaIds)) {
                $colQuery->whereHas('customer', fn ($c) => $c->whereIn('area_id', $userAccessibleAreaIds));
            }

            if ($request->filled('area_id')) {
                $colQuery->whereHas('customer', fn ($c) => $c->where('area_id', $request->area_id));
            }
            if ($request->filled('desa')) {
                $desa = trim($request->desa);
                $desaNoSpace = str_replace(' ', '', $desa);
                $colQuery->whereHas('customer', function ($cq) use ($desa, $desaNoSpace) {
                    $cq->where(function ($sub) use ($desa, $desaNoSpace) {
                        $sub->where('address', 'like', "%{$desa}%")
                            ->orWhere('address', 'like', "%{$desaNoSpace}%");
                    });
                });
            }
            if ($request->filled('start_date')) {
                $colQuery->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $colQuery->whereDate('payment_date', '<=', $request->end_date);
            }
            if (!$request->filled('start_date') && !$request->filled('end_date')) {
                // Default tampilkan bulan berjalan agar ringkas dan relevan
                $colQuery->whereYear('payment_date', $currentYear)->whereMonth('payment_date', $currentMonth);
            }

            $myCollectionRows = $colQuery->latest('payment_date')->latest('id')->limit(500)->get()->map(function ($pay) {
                return [
                    'id' => $pay->id,
                    'payment_date_formatted' => $pay->payment_date ? Carbon::parse($pay->payment_date)->format('d/m/Y') : '-',
                    'invoice_number' => $pay->invoice?->invoice_number ?? '-',
                    'customer_name' => $pay->customer?->name ?? 'Pelanggan',
                    'customer_code' => $pay->customer?->customer_code ?? '-',
                    'customer_phone' => $pay->customer?->phone ?? '-',
                    'customer_area' => $pay->customer?->areaModel?->name ?? $pay->customer?->area ?? '-',
                    'payment_method' => $pay->payment_method ?? 'cash',
                    'collector_name' => $pay->collector?->name ?? 'Kasir / Admin',
                    'status' => $pay->status,
                    'amount' => (float) $pay->amount,
                ];
            })->values()->all();
        }

        $tabLabels = [
            'unpaid' => 'Belum Lunas',
            'partial' => 'Bayar Sebagian (Cicilan)',
            'promise' => 'Janji Bayar',
            'overdue' => 'Jatuh Tempo (Lewat Tempo)',
            'paid' => 'Sudah Lunas',
        ];
        $activeTabLabel = $tabLabels[$activeTab] ?? 'Belum Lunas';

        $scopeTitles = [
            'active' => 'LAPORAN DAFTAR TAGIHAN PELANGGAN (' . strtoupper($activeTabLabel) . ')',
            'collector_sheet' => 'LEMBAR KERJA PENAGIHAN LAPANGAN (FORM TAGIH KOLEKTOR)',
            'my_collections' => 'BERITA ACARA & REKAPITULASI SETORAN PENAGIH',
            'all_summary' => 'LAPORAN LENGKAP PENAGIHAN & SETORAN PELANGGAN',
        ];
        $reportTitle = $scopeTitles[$scope] ?? $scopeTitles['active'];

        $filterAreaName = 'Semua Wilayah / Area';
        if ($request->filled('area_id')) {
            $areaObj = Area::find($request->area_id);
            if ($areaObj) {
                $filterAreaName = $areaObj->name;
            }
        }
        $filterDesaName = $request->filled('desa') ? $request->desa : 'Semua Desa / Kelurahan';
        $filterDateRange = 'Semua Tanggal';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $filterDateRange = Carbon::parse($request->start_date)->format('d/m/Y') . ' s/d ' . Carbon::parse($request->end_date)->format('d/m/Y');
        } elseif ($request->filled('start_date')) {
            $filterDateRange = 'Mulai ' . Carbon::parse($request->start_date)->format('d/m/Y');
        } elseif ($request->filled('end_date')) {
            $filterDateRange = 'Sampai ' . Carbon::parse($request->end_date)->format('d/m/Y');
        }

        $company = [
            'name' => \App\Models\Setting::get('company_name', config('app.name', 'VIZBILL ISP NETWORK')),
            'address' => \App\Models\Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => \App\Models\Setting::get('company_phone', ''),
            'email' => \App\Models\Setting::get('company_email', ''),
            'logo' => \App\Models\Setting::get('company_logo', null),
        ];
        $logoBase64 = $this->resolveCompanyLogoBase64($company['logo'] ?? null);

        $viewData = [
            'isPdf' => $isPdf,
            'scope' => $scope,
            'activeTab' => $activeTab,
            'activeTabLabel' => $activeTabLabel,
            'reportTitle' => $reportTitle,
            'printDate' => now()->format('d/m/Y H:i'),
            'operatorName' => $user?->name ?? 'Petugas Penagihan',
            'isAdmin' => $isAdmin,
            'company' => $company,
            'logoBase64' => $logoBase64,
            'filters' => $request->all(),
            'filterAreaName' => $filterAreaName,
            'filterDesaName' => $filterDesaName,
            'filterDateRange' => $filterDateRange,
            'stats' => $stats,
            'collectorStats' => $collectorStats,
            'invoiceRows' => $invoiceRows,
            'collectorSheetInvoices' => $collectorSheetInvoices,
            'myCollectionRows' => $myCollectionRows,
        ];

        if ($isPdf) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.customer-billing-report', $viewData)
                ->setPaper('a4', 'landscape');
            $slug = match ($scope) {
                'collector_sheet' => 'Lembar_Kerja_Kolektor',
                'my_collections' => 'Rekap_Setoran_Penagih',
                'all_summary' => 'Rekap_Lengkap_Penagihan',
                default => 'Daftar_Penagihan_' . ucfirst($activeTab),
            };
            return $pdf->download($slug . '_' . now()->format('Ymd_His') . '.pdf');
        }

        return view('print.customer-billing-report', $viewData);
    }

    /**
     * Cetak (Print) & Download PDF Nota Tagihan / Kwitansi Pembayaran dari Menu Penagihan Pelanggan
     */
    public function printInvoice(Request $request, Invoice $invoice)
    {
        return app(InvoiceController::class)->print($invoice, $request);
    }

    /**
     * Cetak (Print) & Download PDF Laporan Validasi Setoran Pelanggan (/customer-settlements/export)
     */
    public function exportSettlements(Request $request)
    {
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');
        $isAdmin = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin', 'finance', 'manager', 'management'])
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super admin', 'finance', 'manager', 'management']));

        $scope = $request->get('scope', $request->get('tab', 'pending'));
        if (!in_array($scope, ['pending', 'verified', 'all'])) {
            $scope = 'pending';
        }
        $isPdf = ($request->get('mode') === 'pdf');

        $baseQuery = Payment::whereHas('invoice', function ($q) {
            $q->where('is_reseller_balance', false);
        });

        if (!$isAdmin && $user) {
            $baseQuery->where('collected_by', $user->id);
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

        $query = Payment::with(['invoice.payments', 'customer', 'collector'])
            ->whereHas('invoice', function ($q) {
                $q->where('is_reseller_balance', false);
            });

        if (!$isAdmin && $user) {
            $query->where('collected_by', $user->id);
        }

        if ($scope === 'verified') {
            $query->where('status', 'verified');
        } elseif ($scope === 'pending') {
            $query->where('status', 'pending');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                })->orWhereHas('invoice', function ($iq) use ($search) {
                    $iq->where('invoice_number', 'like', "%{$search}%");
                })->orWhereHas('collector', function ($colq) use ($search) {
                    $colq->where('name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $collectorFilterName = null;
        if ($request->filled('collector_id')) {
            $query->where('collected_by', $request->collector_id);
            $colUser = \App\Models\User::find($request->collector_id);
            if ($colUser) {
                $collectorFilterName = $colUser->name;
            }
        }

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

        $paymentRows = $query->latest('id')->limit(600)->get()->map(function ($payment) {
            $inv = $payment->invoice;
            $verifiedPaid = $inv ? (float) $inv->payments->where('status', 'verified')->sum('amount') : 0;
            $invRemaining = $inv ? max(0, (float) $inv->amount - $verifiedPaid) : 0;

            return [
                'id' => $payment->id,
                'payment_date_formatted' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d/m/Y') : '-',
                'invoice_number' => $inv?->invoice_number ?? '-',
                'customer_name' => $payment->customer?->name ?? 'Pelanggan',
                'customer_code' => $payment->customer?->customer_code ?? '-',
                'customer_phone' => $payment->customer?->phone ?? '-',
                'collector_name' => $payment->collector?->name ?? 'Kasir / Admin',
                'payment_method' => $payment->payment_method ?? 'cash',
                'amount' => (float) $payment->amount,
                'invoice_remaining' => $invRemaining,
                'status' => $payment->status,
            ];
        })->values()->all();

        $company = [
            'name' => \App\Models\Setting::get('company_name', config('app.name', 'VIZBILL ISP NETWORK')),
            'address' => \App\Models\Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => \App\Models\Setting::get('company_phone', ''),
            'email' => \App\Models\Setting::get('company_email', ''),
            'logo' => \App\Models\Setting::get('company_logo', null),
        ];
        $logoBase64 = $this->resolveCompanyLogoBase64($company['logo'] ?? null);

        $reportTitle = match ($scope) {
            'verified' => 'LAPORAN SETORAN PENAGIH TERVALIDASI (MASUK KAS)',
            'all' => 'LAPORAN LENGKAP SETORAN & PELUNASAN PELANGGAN',
            default => 'BERITA ACARA SERAH TERIMA SETORAN PENAGIH (MENUNGGU VALIDASI)',
        };

        $viewData = [
            'isPdf' => $isPdf,
            'scope' => $scope,
            'reportTitle' => $reportTitle,
            'printDate' => now()->format('d/m/Y H:i'),
            'operatorName' => $user?->name ?? 'Admin / Petugas',
            'collectorFilterName' => $collectorFilterName,
            'isAdmin' => $isAdmin,
            'company' => $company,
            'logoBase64' => $logoBase64,
            'stats' => $stats,
            'paymentRows' => $paymentRows,
        ];

        if ($isPdf) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.customer-settlements-report', $viewData)
                ->setPaper('a4', 'landscape');
            return $pdf->download('Laporan_Setoran_Pelanggan_' . ucfirst($scope) . '_' . now()->format('Ymd_His') . '.pdf');
        }

        return view('print.customer-settlements-report', $viewData);
    }

    private function resolveCompanyLogoBase64(?string $logoPath): ?string
    {
        if (empty($logoPath)) {
            return null;
        }
        $clean = ltrim(preg_replace('#^/?storage/#', '', $logoPath), '/');
        $candidates = [
            storage_path('app/public/' . $clean),
            public_path('storage/' . $clean),
            public_path($logoPath),
        ];
        foreach ($candidates as $file) {
            if (is_file($file) && is_readable($file)) {
                $mime = @mime_content_type($file) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
            }
        }
        return null;
    }
}
