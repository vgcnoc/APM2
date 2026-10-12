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

        // ── 1. STATS RINGKASAN GLOBAL ─────────────────────────────
        // Pelanggan menunggak (memiliki invoice belum lunas yang telah lewat jatuh tempo dan belum terminated)
        $menunggakCustomersCount = Customer::where('status', '!=', 'terminated')
            ->whereHas('invoices', function ($q) use ($today) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('is_reseller_balance', false)
                  ->where('due_date', '<', $today);
            })->count();

        $totalNominalMenunggak = (float) Invoice::whereIn('status', ['unpaid', 'partial'])
            ->where('is_reseller_balance', false)
            ->where('due_date', '<', $today)
            ->whereHas('customer', fn($c) => $c->where('status', '!=', 'terminated'))
            ->sum('amount');

        // Janji bayar (hanya pelanggan yang belum terminated)
        $janjiBayarQuery = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->where('is_reseller_balance', false)
            ->whereNotNull('promise_date')
            ->whereHas('customer', fn($c) => $c->where('status', '!=', 'terminated'));

        $totalJanjiBayarCount = (clone $janjiBayarQuery)->count();
        $totalNominalJanjiBayar = (float) (clone $janjiBayarQuery)->sum('amount');
        $janjiTerlewatCount = (clone $janjiBayarQuery)->where('promise_date', '<', $today)->count();
        $janjiHariIniCount = (clone $janjiBayarQuery)->where('promise_date', '=', $today)->count();
        $janjiMendatangCount = (clone $janjiBayarQuery)->where('promise_date', '>', $today)->count();

        // Stop Sementara (Suspended)
        $stopSementaraCount = Customer::where('status', 'suspended')->count();
        $stopKarenaTunggakanCount = Customer::where('status', 'suspended')
            ->whereHas('invoices', function ($q) use ($today) {
                $q->whereIn('status', ['unpaid', 'partial'])
                  ->where('is_reseller_balance', false)
                  ->where('due_date', '<', $today);
            })->count();

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
                $q->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
            }

            if ($promiseFilter === 'today') {
                $q->where('promise_date', '=', $today);
            } elseif ($promiseFilter === 'overdue') {
                $q->where('promise_date', '<', $today);
            } elseif ($promiseFilter === 'upcoming') {
                $q->where('promise_date', '>', $today);
            }

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
                $q->where('area_id', $areaId);
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
                $q->where('area_id', $areaId);
            }

            if ($severity === '1_month') {
                $q->having('unpaid_count', '=', 1);
            } elseif ($severity === '2_months') {
                $q->having('unpaid_count', '=', 2);
            } elseif ($severity === '3_plus') {
                $q->having('unpaid_count', '>=', 3);
            }

            // Urutkan dari jumlah tunggakan terbanyak, lalu total rupiah terbesar
            $items = $q->orderByDesc('unpaid_count')
                       ->orderByDesc('total_unpaid_amount')
                       ->paginate(15)
                       ->withQueryString();
        }

        // Master Area untuk Filter
        $areas = Area::orderBy('name')->get(['id', 'name']);

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
}
