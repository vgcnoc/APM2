<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['package'])
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
            $basePrice = $customer->package ? $customer->package->price : 0;
            
            $ppn_percent = $customer->tax_ppn ?: 0;
            $bhp_percent = $customer->tax_bhp ?: 0;
            $uso_percent = $customer->tax_uso ?: 0;
            
            $ppn_amount = $basePrice * ($ppn_percent / 100);
            $bhp_amount = $basePrice * ($bhp_percent / 100);
            $uso_amount = $basePrice * ($uso_percent / 100);
            
            $total_tax = $ppn_amount + $bhp_amount + $uso_amount;
            $total_price = $basePrice + $total_tax;
            
            return [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'package_name' => $customer->package ? $customer->package->name : '-',
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
        });
        
        // Aggregate totals for active customers
        $activeCustomers = Customer::with(['package'])->where('status', 'active')->get();
        $total_base_price = 0;
        $total_ppn = 0;
        $total_bhp = 0;
        $total_uso = 0;
        
        foreach ($activeCustomers as $cust) {
            $bPrice = $cust->package ? $cust->package->price : 0;
            $total_base_price += $bPrice;
            $total_ppn += $bPrice * (($cust->tax_ppn ?: 0) / 100);
            $total_bhp += $bPrice * (($cust->tax_bhp ?: 0) / 100);
            $total_uso += $bPrice * (($cust->tax_uso ?: 0) / 100);
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
        $query = Customer::with(['package'])
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
            $basePrice = $customer->package ? $customer->package->price : 0;
            $ppn_percent = $customer->tax_ppn ?: 0;
            $bhp_percent = $customer->tax_bhp ?: 0;
            $uso_percent = $customer->tax_uso ?: 0;

            $ppn_amount = $basePrice * ($ppn_percent / 100);
            $bhp_amount = $basePrice * ($bhp_percent / 100);
            $uso_amount = $basePrice * ($uso_percent / 100);
            $total_price = $basePrice + $ppn_amount + $bhp_amount + $uso_amount;

            $totals['base_price'] += $basePrice;
            $totals['ppn'] += $ppn_amount;
            $totals['bhp'] += $bhp_amount;
            $totals['uso'] += $uso_amount;
            $totals['grand_total'] += $total_price;

            return (object) [
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'package_name' => $customer->package ? $customer->package->name : '-',
                'base_price' => $basePrice,
                'ppn_percent' => $ppn_percent,
                'ppn_amount' => $ppn_amount,
                'bhp_percent' => $bhp_percent,
                'bhp_amount' => $bhp_amount,
                'uso_percent' => $uso_percent,
                'uso_amount' => $uso_amount,
                'total_price' => $total_price,
            ];
        });

        return view('print.tax-report', [
            'rows' => $rows,
            'totals' => $totals,
            'search' => $request->search,
        ]);
    }
}
