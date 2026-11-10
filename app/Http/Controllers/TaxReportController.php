<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerBillingPrice;
use App\Services\DataPrivacyMaskingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class TaxReportController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        if ($month < 1 || $month > 12) $month = now()->month;
        if ($year < 2020 || $year > 2050) $year = now()->year;

        $query = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->latest('id')])
            ->where('status', 'active')
            ->orderBy('name');

        // Apply Data Privacy Masking filter if active for this account
        DataPrivacyMaskingService::applyCustomerFilter($query, 'customers.id', null, 'tax_report');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        $globalTaxPpn = (float) Setting::get('tax_ppn', '0');
        $globalBhp = (float) Setting::get('tax_bhp', '1.25');
        $globalUso = (float) Setting::get('tax_uso', '0.5');

        $customers = $query->paginate(20)->withQueryString()->through(function ($customer) use ($globalTaxPpn, $globalBhp, $globalUso, $month, $year) {
            return $this->resolveCustomerTaxData($customer, $globalTaxPpn, $globalBhp, $globalUso, $month, $year);
        });
        
        // Aggregate totals for scoped active customers in this period
        $activeCustomersQuery = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->latest('id')])
            ->where('status', 'active');
        DataPrivacyMaskingService::applyCustomerFilter($activeCustomersQuery, 'customers.id', null, 'tax_report');
        $activeCustomers = $activeCustomersQuery->get();

        $total_base_price = 0;
        $total_ppn = 0;
        $total_bhp = 0;
        $total_uso = 0;
        $grand_total = 0;
        
        foreach ($activeCustomers as $cust) {
            $data = $this->resolveCustomerTaxData($cust, $globalTaxPpn, $globalBhp, $globalUso, $month, $year);
            $total_base_price += $data['base_price'];
            $total_ppn += $data['ppn_amount'];
            $total_bhp += $data['bhp_amount'];
            $total_uso += $data['uso_amount'];
            $grand_total += $data['total_price'];
        }
        
        $totals = [
            'base_price' => $total_base_price,
            'ppn' => $total_ppn,
            'bhp' => $total_bhp,
            'uso' => $total_uso,
            'grand_total' => $grand_total,
        ];

        $user = auth()->user();
        $isSuperAdmin = DataPrivacyMaskingService::isSuperAdmin($user);

        return Inertia::render('Customers/TaxReport', [
            'customers' => $customers,
            'filters' => array_merge($request->only(['search']), [
                'month' => $month,
                'year' => $year,
            ]),
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'totals' => $totals,
            'masking_info' => DataPrivacyMaskingService::getMaskingInfoForUser(),
            'masking_settings' => DataPrivacyMaskingService::getSettings(),
            'can_manage_masking' => $isSuperAdmin || ($user && $user->can('menu_settings_billing')),
            'available_roles' => Role::orderBy('name')->pluck('name')->toArray(),
            'available_users' => User::select('id', 'name', 'email', 'role')->orderBy('name')->get(),
        ]);
    }

    public function print(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        if ($month < 1 || $month > 12) $month = now()->month;
        if ($year < 2020 || $year > 2050) $year = now()->year;

        $query = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->latest('id')])
            ->where('status', 'active')
            ->orderBy('name');

        // Apply Data Privacy Masking filter to print view as well
        DataPrivacyMaskingService::applyCustomerFilter($query, 'customers.id', null, 'tax_report');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        $globalTaxPpn = (float) Setting::get('tax_ppn', '0');
        $globalBhp = (float) Setting::get('tax_bhp', '1.25');
        $globalUso = (float) Setting::get('tax_uso', '0.5');

        $totals = ['base_price' => 0, 'ppn' => 0, 'bhp' => 0, 'uso' => 0, 'grand_total' => 0];

        $rows = $query->get()->map(function ($customer) use (&$totals, $globalTaxPpn, $globalBhp, $globalUso, $month, $year) {
            $data = $this->resolveCustomerTaxData($customer, $globalTaxPpn, $globalBhp, $globalUso, $month, $year);

            $totals['base_price'] += $data['base_price'];
            $totals['ppn'] += $data['ppn_amount'];
            $totals['bhp'] += $data['bhp_amount'];
            $totals['uso'] += $data['uso_amount'];
            $totals['grand_total'] += $data['total_price'];

            return (object) $data;
        });

        return view('print.tax-report', [
            'rows' => $rows,
            'totals' => $totals,
            'search' => $request->search,
            'month' => $month,
            'year' => $year,
        ]);
    }

    private function resolveCustomerTaxData(
        Customer $customer,
        ?float $globalTaxPpn = null,
        ?float $globalBhp = null,
        ?float $globalUso = null,
        ?int $month = null,
        ?int $year = null
    ): array {
        if ($globalTaxPpn === null) {
            $globalTaxPpn = (float) Setting::get('tax_ppn', '0');
        }
        if ($globalBhp === null) {
            $globalBhp = (float) Setting::get('tax_bhp', '1.25');
        }
        if ($globalUso === null) {
            $globalUso = (float) Setting::get('tax_uso', '0.5');
        }

        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // PPN: jika customer->tax_ppn NULL, warisi setting global tax_ppn.
        // Jika customer->tax_ppn diisi angka (misal 0), gunakan angka tersebut (misal bebas pajak).
        $ppn_percent = $customer->tax_ppn !== null ? (float) $customer->tax_ppn : $globalTaxPpn;

        // Auto-calculate BHP & USO if customer has tax enabled (or customer custom override > 0)
        if ($customer->tax_bhp !== null && (float) $customer->tax_bhp > 0) {
            $bhp_percent = (float) $customer->tax_bhp;
        } elseif ($customer->service_status !== 'gratis' && $ppn_percent > 0) {
            $bhp_percent = $globalBhp;
        } else {
            $bhp_percent = 0.0;
        }

        if ($customer->tax_uso !== null && (float) $customer->tax_uso > 0) {
            $uso_percent = (float) $customer->tax_uso;
        } elseif ($customer->service_status !== 'gratis' && $ppn_percent > 0) {
            $uso_percent = $globalUso;
        } else {
            $uso_percent = 0.0;
        }

        // Cari invoice khusus untuk periode (bulan & tahun) yang sedang dilihat
        $invoice = $customer->invoices->where('period_month', $month)->where('period_year', $year)->first();

        $isProrata = $invoice ? ($invoice->is_prorata || $invoice->billing_type === 'prorata') : false;

        if ($customer->service_status === 'gratis') {
            $basePrice = 0;
            $ppn_amount = 0;
            $total_price = 0;
        } elseif ($invoice && $invoice->amount > 0) {
            // Jika ada invoice terbit di periode ini, DPP dihitung dari nominal invoice
            if ($ppn_percent > 0) {
                $basePrice = (int) round((float) $invoice->amount / (1 + ($ppn_percent / 100)));
                $ppn_amount = (int) ($invoice->amount - $basePrice);
            } else {
                $basePrice = (int) round((float) $invoice->amount);
                $ppn_amount = 0;
            }
            $total_price = (int) $invoice->amount;
        } else {
            // Jika belum terbit invoice untuk periode ini, ambil nilai paket bulanan normal aktif
            $basePrice = (int) round(CustomerBillingPrice::monthly($customer));
            if ($ppn_percent > 0) {
                $ppn_amount = (int) round($basePrice * ($ppn_percent / 100));
            } else {
                $ppn_amount = 0;
            }
            $total_price = (int) ($basePrice + $ppn_amount);
        }

        $bhp_amount = (int) round($basePrice * ($bhp_percent / 100));
        $uso_amount = (int) round($basePrice * ($uso_percent / 100));

        $total_tax = (int) ($ppn_amount + $bhp_amount + $uso_amount);

        return [
            'id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'name' => $customer->name,
            'package_name' => $customer->package ? $customer->package->name : '-',
            'is_prorata' => $isProrata,
            'base_price' => $basePrice,
            'ppn_percent' => $ppn_percent,
            'ppn_amount' => $ppn_amount,
            'bhp_percent' => $bhp_percent,
            'bhp_amount' => $bhp_amount,
            'uso_percent' => $uso_percent,
            'uso_amount' => $uso_amount,
            'total_tax' => $total_tax,
            'total_price' => $total_price,
        ];
    }
}
