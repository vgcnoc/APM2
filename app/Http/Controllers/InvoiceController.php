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
            $q->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'package_id', 'created_at', 'status')
              ->withCount(['invoices as unpaid_invoices_count' => function ($query) {
                  $query->whereIn('status', ['unpaid', 'partial']);
              }])
              ->withSum(['invoices as total_unpaid_amount' => function ($query) {
                  $query->whereIn('status', ['unpaid', 'partial']);
              }], 'amount');
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
        } elseif ($tab === 'prorata') {
            $query->where('is_prorata', true);
        } elseif ($tab === 'upgrade') {
            $query->where('id', '<', 0); // Placeholder
        } elseif ($tab === 'janji_bayar') {
            $query->whereNotNull('promise_date')->where('status', '!=', 'paid');
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
            'prorata' => Invoice::where('is_prorata', true)->count(),
            'upgrade' => 0, // Placeholder
            'janji_bayar' => Invoice::whereNotNull('promise_date')->where('status', '!=', 'paid')->count(),

            // Amounts for cards
            'total_unpaid_amount' => Invoice::where('status', 'unpaid')->sum('amount'),
            'total_paid_amount' => \App\Models\Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'total_piutang_amount' => Invoice::where('status', 'partial')->sum('amount') - \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('status', 'partial'))->sum('amount'),
            'total_jatuh_tempo_amount' => Invoice::where('due_date', '<', now())->where('status', 'unpaid')->sum('amount') + (Invoice::where('due_date', '<', now())->where('status', 'partial')->sum('amount') - \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('due_date', '<', now())->where('status', 'partial'))->sum('amount')),
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
                        'unpaid_count' => $invoice->customer->unpaid_invoices_count ?? 0,
                        'total_unpaid' => $invoice->customer->total_unpaid_amount ?? 0,
                    ] : null,
                    'period_label' => $invoice->period_label,
                    'amount' => $invoice->amount,
                    'total_paid' => $invoice->total_paid,
                    'remaining' => $invoice->remaining,
                    'status' => $invoice->status,
                    'last_payment_date' => $lastPayment ? \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') : null,
                    'last_payment_method' => $lastPayment ? $lastPayment->payment_method : null,
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'issued_date' => $invoice->issued_date ? $invoice->issued_date->format('Y-m-d') : null,
                    'promise_date' => $invoice->promise_date ? $invoice->promise_date->format('Y-m-d') : null,
                ];
            });

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'start_date', 'end_date', 'area_id', 'tab']),
            'payment_banks' => json_decode(\App\Models\Setting::get('payment_banks', '[]'), true),
        ]);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $paymentAmount = $request->amount;
        $customerId = $invoice->customer_id;
        
        // Cari semua tagihan unpaid/partial milik pelanggan ini, urutkan dari yang tertua
        $unpaidInvoices = Invoice::where('customer_id', $customerId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date', 'asc')
            ->get();
            
        // Pindahkan tagihan yang sedang diklik ke urutan pertama, agar diprioritaskan
        $unpaidInvoices = $unpaidInvoices->sortByDesc(function ($inv) use ($invoice) {
            return $inv->id == $invoice->id ? 1 : 0;
        });

        $remainingPayment = $paymentAmount;

        foreach ($unpaidInvoices as $inv) {
            if ($remainingPayment <= 0) break;

            $invRemaining = $inv->amount - $inv->payments()->sum('amount');
            if ($invRemaining <= 0) continue;

            $payForThis = min($invRemaining, $remainingPayment);

            $inv->payments()->create([
                'customer_id' => $customerId,
                'payment_date' => now(),
                'amount' => $payForThis,
                'payment_method' => $request->method,
                'notes' => $request->notes,
                'processed_by' => auth()->id(),
            ]);

            $totalPaid = $inv->payments()->sum('amount');
            if ($totalPaid >= $inv->amount) {
                $inv->update(['status' => 'paid', 'total_paid' => $totalPaid, 'remaining' => 0]);
            } else {
                $inv->update(['status' => 'partial', 'total_paid' => $totalPaid, 'remaining' => $inv->amount - $totalPaid]);
            }

            $remainingPayment -= $payForThis;
        }

        // Jika masih ada sisa pembayaran yang tidak teralokasi, 
        // kita bisa menaruhnya di tagihan yang sedang aktif/dipilih, menjadikannya overpaid.
        if ($remainingPayment > 0) {
            $invoice->payments()->create([
                'customer_id' => $customerId,
                'payment_date' => now(),
                'amount' => $remainingPayment,
                'payment_method' => $request->method,
                'notes' => ($request->notes ? $request->notes . ' | ' : '') . 'Kelebihan Pembayaran (Overpaid)',
                'processed_by' => auth()->id(),
            ]);
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->update(['status' => 'paid', 'total_paid' => $totalPaid, 'remaining' => 0]);
        }

        // Aktifkan kembali internet jika pelanggan sedang di-isolir
        $customer = $invoice->customer;
        if ($customer && $customer->status === 'suspended') {
            // Cek apakah masih ada tunggakan yang jatuh tempo (tanpa janji bayar aktif)
            // Jika smart allocation sudah melunasi tagihan jatuh tempo, kita aktifkan lagi.
            // Biar aman, otomatis aktifkan saja setelah ada pembayaran. Jika besoknya masih nunggak, cron job akan isolir lagi.
            $customer->update(['status' => 'active']);
        }

        return back()->with('success', 'Pembayaran berhasil diproses dengan sistem alokasi cerdas.');
    }

    public function rollback(Invoice $invoice)
    {
        // Hapus semua record pembayaran terkait invoice ini
        $invoice->payments()->delete();

        // Kembalikan status invoice menjadi belum lunas
        $invoice->update([
            'status' => 'unpaid',
            'total_paid' => 0,
            'remaining' => $invoice->amount
        ]);

        return back()->with('success', 'Pembayaran berhasil dibatalkan. Tagihan kembali belum lunas.');
    }

    public function promise(Request $request, Invoice $invoice)
    {
        $request->validate([
            'promise_date' => 'required|date|after_or_equal:today',
        ]);

        $invoice->update([
            'promise_date' => $request->promise_date
        ]);

        // Aktifkan kembali internet jika pelanggan sedang di-isolir
        $customer = $invoice->customer;
        if ($customer && $customer->status === 'suspended') {
            $customer->update(['status' => 'active']);
        }

        return back()->with('success', 'Janji bayar berhasil disimpan. Layanan internet pelanggan telah diaktifkan kembali.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'issued_date' => 'required|date',
        ]);

        $invoice->update($request->only('amount', 'due_date', 'issued_date'));
        return back()->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return back()->with('success', 'Tagihan berhasil dihapus.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('customer', 'payments');
        return view('print.invoice', compact('invoice'));
    }
}
