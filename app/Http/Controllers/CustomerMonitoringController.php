<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\RadiusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerMonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today()->toDateString();
        $tab = $request->query('tab', 'menunggak');
        $search = $request->query('search');
        $areaId = $request->query('area_id');
        $severity = $request->query('severity', 'all');
        $promiseFilter = $request->query('promise_filter', 'all');
        $stopReason = $request->query('stop_reason', 'all');

        $isRestricted = auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('monitoring_pelanggan_view_all') && !auth()->user()->can('customers_all_view_all');
        $isAreaRestricted = $isRestricted;
        $userAreaIds = auth()->check() ? auth()->user()->getAccessibleAreaIds() : [];

        // Scoping closures (all, area, own)
        $scopeCustomerArea = function ($query) use ($isRestricted, $userAreaIds) {
            if ($isRestricted) {
                if (auth()->user()->can('monitoring_pelanggan_view_area') || auth()->user()->can('customers_all_view_area')) {
                    $query->whereIn('area_id', $userAreaIds);
                } else {
                    $query->where('sales_id', auth()->id());
                }
            }
        };

        $scopeInvoiceArea = function ($query) use ($isRestricted, $userAreaIds) {
            if ($isRestricted) {
                if (auth()->user()->can('monitoring_pelanggan_view_area') || auth()->user()->can('customers_all_view_area')) {
                    $query->whereHas('customer', function($cq) use ($userAreaIds) {
                        $cq->whereIn('area_id', $userAreaIds);
                    });
                } else {
                    $query->whereHas('customer', function($cq) {
                        $cq->where('sales_id', auth()->id());
                    });
                }
            }
        };

        // Filter Area & Search helper untuk query Customer
        $applyCustomerFilter = function ($q) use ($areaId, $search) {
            if ($areaId) {
                $areaName = Area::find($areaId)?->name;
                $q->where(fn($sub) => $sub->where('area_id', $areaId)->when($areaName, fn($s) => $s->orWhere('area', $areaName)));
            }
            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }
        };

        // Filter Area & Search helper untuk query Invoice
        $applyInvoiceCustomerFilter = function ($q) use ($areaId, $search) {
            if ($areaId) {
                $areaName = Area::find($areaId)?->name;
                $q->whereHas('customer', fn($c) => $c->where(fn($sub) => $sub->where('area_id', $areaId)->when($areaName, fn($s) => $s->orWhere('area', $areaName))));
            }
            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%")
                              ->orWhere('address', 'like', "%{$search}%");
                        });
                });
            }
        };

        // ── 1. STATS RINGKASAN GLOBAL (KONTEKSTUAL AREA & SEARCH) ──
        // Pelanggan menunggak (memiliki invoice belum lunas yang telah lewat jatuh tempo dan belum terminated)
        $menunggakCustomerQuery = Customer::where('status', '!=', 'terminated')
            ->whereHas('invoices', function ($q) use ($today) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('is_reseller_balance', false)
                  ->where('due_date', '<', $today);
            });
        $scopeCustomerArea($menunggakCustomerQuery);
        $applyCustomerFilter($menunggakCustomerQuery);
        $menunggakCustomersCount = $menunggakCustomerQuery->count();

        $nominalMenunggakQuery = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->where('is_reseller_balance', false)
            ->where('due_date', '<', $today)
            ->whereHas('customer', fn($c) => $c->where('status', '!=', 'terminated'));
        $scopeInvoiceArea($nominalMenunggakQuery);
        $applyInvoiceCustomerFilter($nominalMenunggakQuery);
        $totalNominalMenunggak = (float) $nominalMenunggakQuery->sum('amount');

        // Janji bayar (hanya pelanggan yang belum terminated)
        $janjiBayarQuery = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->where('is_reseller_balance', false)
            ->whereNotNull('promise_date')
            ->whereHas('customer', fn($c) => $c->where('status', '!=', 'terminated'));
        $scopeInvoiceArea($janjiBayarQuery);
        $applyInvoiceCustomerFilter($janjiBayarQuery);

        $totalJanjiBayarCount = (clone $janjiBayarQuery)->count();
        $totalNominalJanjiBayar = (float) (clone $janjiBayarQuery)->sum('amount');
        $janjiTerlewatCount = (clone $janjiBayarQuery)->where('promise_date', '<', $today)->count();
        $janjiHariIniCount = (clone $janjiBayarQuery)->where('promise_date', '=', $today)->count();
        $janjiMendatangCount = (clone $janjiBayarQuery)->where('promise_date', '>', $today)->count();

        // Stop Sementara (Suspended)
        $stopSementaraQuery = Customer::where('status', 'suspended');
        $scopeCustomerArea($stopSementaraQuery);
        $applyCustomerFilter($stopSementaraQuery);
        $stopSementaraCount = $stopSementaraQuery->count();

        $stopKarenaTunggakanQuery = Customer::where('status', 'suspended')
            ->whereHas('invoices', function ($q) use ($today) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('is_reseller_balance', false)
                  ->where('due_date', '<', $today);
            });
        $scopeCustomerArea($stopKarenaTunggakanQuery);
        $applyCustomerFilter($stopKarenaTunggakanQuery);
        $stopKarenaTunggakanCount = $stopKarenaTunggakanQuery->count();

        $stats = [
            'total_menunggak_count' => $menunggakCustomersCount,
            'total_nominal_menunggak' => $totalNominalMenunggak,
            'total_janji_bayar_count' => $totalJanjiBayarCount,
            'total_nominal_janji_bayar' => $totalNominalJanjiBayar,
            'janji_terlewat_count' => $janjiTerlewatCount,
            'janji_hari_ini_count' => $janjiHariIniCount,
            'janji_mendatang_count' => $janjiMendatangCount,
            'stop_sementara_count' => $stopSementaraCount,
            'stop_karena_tunggakan_count' => $stopKarenaTunggakanCount,
        ];

        // ── 2. DATA SESUAI TAB AKTIF ──────────────────────────────
        $items = null;

        if ($tab === 'janji_bayar') {
            // TAB: JANJI BAYAR
            $q = Invoice::with([
                'customer' => function ($c) {
                    $c->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'package_id', 'status');
                },
                'customer.areaModel:id,name',
                'customer.package:id,name,price',
            ])
            ->where('is_reseller_balance', false)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereNotNull('promise_date')
            ->whereHas('customer', fn($c) => $c->where('status', '!=', 'terminated'));

            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%")
                              ->orWhere('address', 'like', "%{$search}%");
                        });
                });
            }

            if ($areaId) {
                $areaName = Area::find($areaId)?->name;
                $q->whereHas('customer', fn($c) => $c->where(fn($sub) => $sub->where('area_id', $areaId)->when($areaName, fn($s) => $s->orWhere('area', $areaName))));
            }

            if ($promiseFilter === 'today') {
                $q->where('promise_date', '=', $today);
            } elseif ($promiseFilter === 'overdue') {
                $q->where('promise_date', '<', $today);
            } elseif ($promiseFilter === 'upcoming') {
                $q->where('promise_date', '>', $today);
            }

            $scopeInvoiceArea($q);
            $items = $q->orderBy('promise_date', 'asc')->paginate(15)->withQueryString();

        } elseif ($tab === 'stop_sementara') {
            // TAB: STOP SEMENTARA (SUSPENDED)
            $q = Customer::with([
                'areaModel:id,name',
                'package:id,name,price',
            ])
            ->where('status', 'suspended')
            ->withCount(['invoices as unpaid_count' => function ($sub) {
                $sub->whereIn('status', ['unpaid', 'partial'])->where('is_reseller_balance', false);
            }])
            ->withSum(['invoices as total_unpaid_amount' => function ($sub) {
                $sub->whereIn('status', ['unpaid', 'partial'])->where('is_reseller_balance', false);
            }], 'amount');

            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            if ($areaId) {
                $areaName = Area::find($areaId)?->name;
                $q->where(fn($sub) => $sub->where('area_id', $areaId)->when($areaName, fn($s) => $s->orWhere('area', $areaName)));
            }

            if ($stopReason === 'tunggakan') {
                $q->whereHas('invoices', function ($sub) use ($today) {
                    $sub->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false)
                        ->where('due_date', '<', $today);
                });
            } elseif ($stopReason === 'manual') {
                $q->whereDoesntHave('invoices', function ($sub) use ($today) {
                    $sub->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false)
                        ->where('due_date', '<', $today);
                });
            }

            $scopeCustomerArea($q);
            $items = $q->orderByDesc('updated_at')->paginate(15)->withQueryString();

        } else {
            // TAB: SERING MENUNGGAK (DEFAULT)
            $q = Customer::where('status', '!=', 'terminated')
            ->with([
                'areaModel:id,name',
                'package:id,name,price',
                'invoices' => function ($inv) {
                    $inv->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false)
                        ->orderBy('due_date', 'asc');
                }
            ])
            ->whereHas('invoices', function ($inv) use ($today) {
                $inv->whereIn('status', ['unpaid', 'partial'])
                    ->where('is_reseller_balance', false)
                    ->where('due_date', '<', $today);
            })
            ->withCount(['invoices as unpaid_count' => function ($inv) {
                $inv->whereIn('status', ['unpaid', 'partial'])
                    ->where('is_reseller_balance', false);
            }])
            ->withSum(['invoices as total_unpaid_amount' => function ($inv) {
                $inv->whereIn('status', ['unpaid', 'partial'])
                    ->where('is_reseller_balance', false);
            }], 'amount');

            if ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            if ($areaId) {
                $areaName = Area::find($areaId)?->name;
                $q->where(fn($sub) => $sub->where('area_id', $areaId)->when($areaName, fn($s) => $s->orWhere('area', $areaName)));
            }

            if ($severity === '1_month') {
                $q->having('unpaid_count', '=', 1);
            } elseif ($severity === '2_months') {
                $q->having('unpaid_count', '=', 2);
            } elseif ($severity === '3_plus') {
                $q->having('unpaid_count', '>=', 3);
            }

            $scopeCustomerArea($q);

            // Urutkan dari jumlah tunggakan terbanyak, lalu total rupiah terbesar
            $items = $q->orderByDesc('unpaid_count')
                       ->orderByDesc('total_unpaid_amount')
                       ->paginate(15)
                       ->withQueryString();
        }

        // Master Area untuk Filter (scoped if restricted)
        $areas = $isAreaRestricted
            ? Area::whereIn('id', $userAreaIds)->orderBy('name')->get(['id', 'name'])
            : Area::orderBy('name')->get(['id', 'name']);

        // Data Perusahaan untuk Template WhatsApp
        $companyName = Setting::get('company_name', config('app.name', 'ISP Provider'));
        $companyPhone = Setting::get('company_phone', '');
        $paymentBanks = json_decode(Setting::get('payment_banks', '[]'), true) ?: [];

        return Inertia::render('Monitoring/CustomerMonitoring', [
            'tab' => $tab,
            'items' => $items,
            'stats' => $stats,
            'areas' => $areas,
            'company' => [
                'name' => $companyName,
                'phone' => $companyPhone,
                'banks' => $paymentBanks,
            ],
            'filters' => [
                'search' => $search,
                'area_id' => $areaId,
                'severity' => $severity,
                'promise_filter' => $promiseFilter,
                'stop_reason' => $stopReason,
            ]
        ]);
    }

    /**
     * Buka Isolir / Aktifkan Kembali Layanan Pelanggan
     */
    public function unsuspend(Customer $customer, RadiusService $radius)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all')) {
            if (!in_array($customer->area_id, auth()->user()->getAccessibleAreaIds())) {
                return back()->with('error', 'Anda tidak memiliki hak akses untuk mengubah data pelanggan di area ini.');
            }
        }

        $customer->update([
            'status' => 'active',
            'suspend_start' => null,
            'suspend_end' => null,
        ]);

        // CustomerObserver akan auto-sync RADIUS dan disconnect agar user re-login aktif
        return back()->with('success', "Layanan untuk {$customer->name} ({$customer->customer_code}) berhasil diaktifkan kembali.");
    }

    /**
     * Stop Sementara / Isolir Pelanggan
     */
    public function suspend(Request $request, Customer $customer, RadiusService $radius)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all')) {
            if (!in_array($customer->area_id, auth()->user()->getAccessibleAreaIds())) {
                return back()->with('error', 'Anda tidak memiliki hak akses untuk mengubah data pelanggan di area ini.');
            }
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $startDate = $request->start_date ?: Carbon::today()->toDateString();

        $customer->update([
            'status' => 'suspended',
            'suspend_start' => $startDate,
            'suspend_end' => $request->end_date ?: null,
        ]);

        return back()->with('success', "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil di-Stop Sementara.");
    }

    /**
     * Tetapkan / Perbarui Janji Bayar untuk Invoice
     */
    public function setPromise(Request $request, Invoice $invoice)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('invoices_view_all')) {
            $invoice->loadMissing('customer');
            if ($invoice->customer && !in_array($invoice->customer->area_id, auth()->user()->getAccessibleAreaIds())) {
                return back()->with('error', 'Anda tidak memiliki hak akses untuk memproses invoice di area ini.');
            }
        }

        $request->validate([
            'promise_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice->update([
            'promise_date' => $request->promise_date,
            'notes' => $request->notes ? ($invoice->notes ? $invoice->notes . ' | ' . $request->notes : $request->notes) : $invoice->notes,
        ]);

        // Jika pelanggan sedang di-isolir dan admin mengatur janji bayar,
        // buka isolirnya sementara agar bisa beraktivitas hingga tanggal janji bayar
        $customer = $invoice->customer;
        if ($customer && $customer->status === 'suspended' && $request->boolean('auto_unsuspend', true)) {
            $customer->update([
                'status' => 'active',
            ]);
        }

        return back()->with('success', "Janji bayar untuk invoice {$invoice->invoice_number} berhasil disimpan.");
    }

    /**
     * Cetak Surat Janji Bayar dari Monitoring Pelanggan
     */
    public function printPromise(Invoice $invoice)
    {
        return redirect()->route('invoices.print-promise', $invoice);
    }
}
