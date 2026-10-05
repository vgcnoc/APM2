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
            $q->select('id', 'name', 'customer_code', 'phone');
        }, 'payments']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('period_month') && $request->filled('period_year')) {
            $query->where('period_month', $request->period_month)
                  ->where('period_year', $request->period_year);
        }

        $invoices = $query->latest('id')
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer' => $invoice->customer ? [
                        'id' => $invoice->customer->id,
                        'name' => $invoice->customer->name,
                        'customer_code' => $invoice->customer->customer_code,
                    ] : null,
                    'period_label' => $invoice->period_label,
                    'amount' => $invoice->amount,
                    'total_paid' => $invoice->total_paid,
                    'remaining' => $invoice->remaining,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'issued_date' => $invoice->issued_date ? $invoice->issued_date->format('Y-m-d') : null,
                ];
            });

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'period_month', 'period_year']),
        ]);
    }
}
