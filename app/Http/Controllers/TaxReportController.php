<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)->latest('id')])
            ->where('status', 'active')
            ->orderBy('name');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(20)->withQueryString()->through(function ($customer) {
            return $this->resolveCustomerTaxData($customer);
        });
        
        // Aggregate totals for active customers
        $activeCustomers = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)->latest('id')])
            ->where('status', 'active')
            ->get();

        $total_base_price = 0;
        $total_ppn = 0;
        $total_bhp = 0;
        $total_uso = 0;
        
        foreach ($activeCustomers as $cust) {
            $data = $this->resolveCustomerTaxData($cust);
            $total_base_price += $data['base_price'];
            $total_ppn += $data['ppn_amount'];
            $total_bhp += $data['bhp_amount'];
            $total_uso += $data['uso_amount'];
        }
        
        $totals = [
            'base_price' => $total_base_price,
            'ppn' => $total_ppn,
            'bhp' => $total_bhp,
            'uso' => $total_uso,
            'grand_total' => $total_base_price + $total_ppn + $total_bhp + $total_uso,
        ];

        return Inertia::render('Customers/TaxReport', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
            'totals' => $totals
        ]);
    }

    public function print(Request $request)
    {
        $query = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)->latest('id')])
            ->where('status', 'active')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        $totals = ['base_price' => 0, 'ppn' => 0, 'bhp' => 0, 'uso' => 0, 'grand_total' => 0];

        $rows = $query->get()->map(function ($customer) use (&$totals) {
            $data = $this->resolveCustomerTaxData($customer);

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
        ]);
    }

    private function resolveCustomerTaxData(Customer $customer): array
    {
        $ppn_percent = $customer->tax_ppn !== null ? (float) $customer->tax_ppn : 0;
        $bhp_percent = $customer->tax_bhp !== null ? (float) $customer->tax_bhp : 0;
        $uso_percent = $customer->tax_uso !== null ? (float) $customer->tax_uso : 0;

        $invoice = $customer->invoices->where('period_month', now()->month)->where('period_year', now()->year)->first()
            ?? $customer->invoices->first();

        $isProrata = $invoice ? ($invoice->is_prorata || $invoice->billing_type === 'prorata') : false;

        if ($customer->service_status === 'gratis') {
            $basePrice = 0.0;
        } elseif ($invoice && $invoice->amount > 0) {
            // Jika ada invoice terbit (termasuk tagihan prorata aktivasi), DPP dihitung dari nominal riil invoice
            if ($ppn_percent > 0) {
                $basePrice = round((float) $invoice->amount / (1 + ($ppn_percent / 100)));
            } else {
                $basePrice = round((float) $invoice->amount);
            }
        } else {
            $basePrice = (float) ($customer->base_amount > 0 ? $customer->base_amount : ($customer->package ? $customer->package->price : 0));
        }

        $ppn_amount = round($basePrice * ($ppn_percent / 100));
        $bhp_amount = round($basePrice * ($bhp_percent / 100));
        $uso_amount = round($basePrice * ($uso_percent / 100));

        $total_tax = $ppn_amount + $bhp_amount + $uso_amount;
        $total_price = $basePrice + $total_tax;

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
