<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Invoice::with(['customer' => function($q) {
            $q->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'package_id', 'created_at', 'status');
        }, 'customer.areaModel', 'customer.package', 'payments' => function($q) {
            $q->latest('payment_date');
        }]);

        $tab = $request->query('tab', 'semua');
        if ($tab === 'jatuh_tempo') {
            $query->where('due_date', '<', now())->where('status', '!=', 'paid');
        } elseif ($tab === 'piutang') {
            $query->where('status', 'partial');
        } elseif ($tab === 'lunas') {
            $query->where('status', 'paid');
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhereHas('areaModel', function($q3) use ($search) {
                             $q3->where('name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('area_id')) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('issued_date', [$request->start_date, $request->end_date]);
        }

        // Get counts for tabs
        $stats = [
            'semua' => Invoice::count(),
            'jatuh_tempo' => Invoice::where('due_date', '<', now())->where('status', '!=', 'paid')->count(),
            'piutang' => Invoice::where('status', 'partial')->count(),
            'lunas' => Invoice::where('status', 'paid')->count(),
        ];

        $invoices = $query->latest('id')
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(function ($invoice) {
                $lastPayment = $invoice->payments->first();
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer' => $invoice->customer ? [
                        'id' => $invoice->customer->id,
                        'name' => $invoice->customer->name,
                        'address' => $invoice->customer->address,
                        'area' => $invoice->customer->areaModel?->name,
                        'package' => $invoice->customer->package?->name,
                        'register_date' => $invoice->customer->created_at ? $invoice->customer->created_at->format('d M Y') : null,
                        'status' => $invoice->customer->status,
                    ] : null,
                    'period_label' => $invoice->period_label,
                    'amount' => $invoice->amount,
                    'total_paid' => $invoice->total_paid,
                    'remaining' => $invoice->remaining,
                    'status' => $invoice->status,
                    'last_payment_date' => $lastPayment ? \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') : null,
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'issued_date' => $invoice->issued_date ? $invoice->issued_date->format('Y-m-d') : null,
                    'promise_date' => null, // Placeholder
                ];
            });

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'start_date', 'end_date', 'area_id', 'tab']),
        ]);
    }
}
