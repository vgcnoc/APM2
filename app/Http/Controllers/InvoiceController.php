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
        $today = now()->startOfDay();

        $query = Invoice::with(['customer' => function($q) use ($today) {
            $q->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'package_id', 'created_at', 'status')
              ->withCount(['invoices as unpaid_invoices_count' => function ($query) {
                  $query->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false);
              }])
              ->withCount(['invoices as overdue_invoices_count' => function ($query) use ($today) {
                  $query->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false)
                        ->where('due_date', '<', $today);
              }])
              ->withSum(['invoices as total_unpaid_amount' => function ($query) {
                  $query->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false);
              }], 'amount')
              ->withSum(['invoices as total_overdue_amount' => function ($query) use ($today) {
                  $query->whereIn('status', ['unpaid', 'partial'])
                        ->where('is_reseller_balance', false)
                        ->where('due_date', '<', $today);
              }], 'amount');
        }, 'customer.areaModel', 'customer.package', 'payments' => function($q) {
            $q->latest('payment_date');
        }])->where('is_reseller_balance', false);

        $tab = $request->query('tab', 'semua');
        if ($tab === 'jatuh_tempo') {
            $query->where('due_date', '<', now())->where('status', '!=', 'paid');
        } elseif ($tab === 'piutang') {
            $query->where('status', 'partial');
        } elseif ($tab === 'lunas') {
            $query->where('status', 'paid');
        } elseif ($tab === 'prorata') {
            $query->where(function($q) {
                $q->where('is_prorata', true)
                  ->orWhere('billing_type', 'prorata');
            });
        } elseif ($tab === 'upgrade') {
            $query->where(function($q) {
                $q->whereIn('billing_type', ['upgrade', 'downgrade'])
                  ->orWhere('notes', 'like', '%Upgrade%')
                  ->orWhere('notes', 'like', '%Downgrade%');
            });
        } elseif ($tab === 'janji_bayar') {
            $query->whereNotNull('promise_date')->where('status', '!=', 'paid');
        } elseif ($tab === 'stop_sementara') {
            $query->whereHas('customer', fn($q) => $q->where('status', 'suspended'));
        } elseif ($tab === 'stop_permanen') {
            $query->whereHas('customer', fn($q) => $q->where('status', 'terminated'));
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
        // Get counts for tabs
        $baseQuery = Invoice::where('is_reseller_balance', false);
        
        $stats = [
            'semua' => (clone $baseQuery)->count(),
            'jatuh_tempo' => (clone $baseQuery)->where('due_date', '<', now())->where('status', '!=', 'paid')->count(),
            'piutang' => (clone $baseQuery)->where('status', 'partial')->count(),
            'lunas' => (clone $baseQuery)->where('status', 'paid')->count(),
            'prorata' => (clone $baseQuery)->where(function($q) {
                $q->where('is_prorata', true)
                  ->orWhere('billing_type', 'prorata');
            })->count(),
            'upgrade' => (clone $baseQuery)->where(function($q) {
                $q->whereIn('billing_type', ['upgrade', 'downgrade'])
                  ->orWhere('notes', 'like', '%Upgrade%')
                  ->orWhere('notes', 'like', '%Downgrade%');
            })->count(),
            'janji_bayar' => (clone $baseQuery)->whereNotNull('promise_date')->where('status', '!=', 'paid')->count(),
            'stop_sementara' => (clone $baseQuery)->whereHas('customer', fn($q) => $q->where('status', 'suspended'))->count(),
            'stop_permanen' => (clone $baseQuery)->whereHas('customer', fn($q) => $q->where('status', 'terminated'))->count(),

            // Amounts for cards
            'total_unpaid_amount' => (clone $baseQuery)->where('status', 'unpaid')->sum('amount'),
            'total_paid_amount' => \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('is_reseller_balance', false))->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'total_piutang_amount' => (clone $baseQuery)->where('status', 'partial')->sum('amount') - \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('status', 'partial')->where('is_reseller_balance', false))->sum('amount'),
            'total_jatuh_tempo_amount' => (clone $baseQuery)->where('due_date', '<', now())->where('status', 'unpaid')->sum('amount') + ((clone $baseQuery)->where('due_date', '<', now())->where('status', 'partial')->sum('amount') - \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('due_date', '<', now())->where('status', 'partial')->where('is_reseller_balance', false))->sum('amount')),
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
                        'package_id' => $invoice->customer->package_id,
                        'package' => $invoice->customer->package?->name,
                        'package_price' => (float) ($invoice->customer->package?->price ?? 0),
                        'package_speed' => $invoice->customer->package?->speed_mbps ?? 0,
                        'register_date' => $invoice->customer->created_at ? $invoice->customer->created_at->format('d M Y') : null,
                        'status' => $invoice->customer->status,
                        'unpaid_count' => $invoice->customer->unpaid_invoices_count ?? 0,
                        'total_unpaid' => $invoice->customer->total_unpaid_amount ?? 0,
                        'overdue_count' => $invoice->customer->overdue_invoices_count ?? 0,
                        'total_overdue' => $invoice->customer->total_overdue_amount ?? 0,
                    ] : null,
                    'period_label' => $invoice->period_label,
                    'billing_type' => $invoice->billing_type ?? $invoice->customer?->billing_type,
                    'is_prorata' => (bool) $invoice->is_prorata,
                    'amount' => $invoice->amount,
                    'total_paid' => $invoice->total_paid,
                    'remaining' => $invoice->remaining,
                    'status' => $invoice->status,
                    'notes' => $invoice->notes,
                    'last_payment_date' => $lastPayment ? \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') : null,
                    'last_payment_method' => $lastPayment ? $lastPayment->payment_method : null,
                    'last_payment_notes' => $lastPayment ? $lastPayment->notes : null,
                    'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                    'issued_date' => $invoice->issued_date ? $invoice->issued_date->format('Y-m-d') : null,
                    'promise_date' => $invoice->promise_date ? $invoice->promise_date->format('Y-m-d') : null,
                ];
            });

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'packages' => \App\Models\InternetPackage::active()->orderBy('price')->get(['id', 'name', 'price', 'speed_mbps']),
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

    public function stopSementara(Request $request, Invoice $invoice, \App\Services\RadiusService $radius)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $customer = $invoice->customer;
        if ($customer && $customer->status !== 'suspended') {
            $customer->update([
                'status' => 'suspended',
                'suspend_start' => $request->start_date,
                'suspend_end' => $request->end_date,
            ]);
            $radius->guard(fn($r) => $r->syncCustomer($customer));
            $accounts = $radius->customerAccounts($customer);
            foreach (array_keys($accounts) as $username) {
                $radius->guard(fn($r) => $r->disconnect($username));
            }
        }
        return redirect()->back()->with('success', 'Pelanggan berhasil di-Stop Sementara mulai ' . $request->start_date . ' sampai ' . $request->end_date . '.');
    }

    public function stopPermanen(Invoice $invoice, \App\Services\RadiusService $radius)
    {
        $customer = $invoice->customer;
        if ($customer && $customer->status !== 'terminated') {
            $customer->update(['status' => 'terminated']);
            $radius->guard(fn($r) => $r->syncCustomer($customer));
            $accounts = $radius->customerAccounts($customer);
            foreach (array_keys($accounts) as $username) {
                $radius->guard(fn($r) => $r->disconnect($username));
            }
        }
        return redirect()->back()->with('success', 'Pelanggan berhasil di-Stop Permanen.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('customer', 'payments');
        
        $company = [
            'name' => \App\Models\Setting::get('company_name', config('app.name', 'ISP Management')),
            'address' => \App\Models\Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => \App\Models\Setting::get('company_phone', ''),
            'email' => \App\Models\Setting::get('company_email', ''),
            'website' => \App\Models\Setting::get('company_website', ''),
            'logo' => \App\Models\Setting::get('company_logo', null),
        ];

        $banks = json_decode(\App\Models\Setting::get('payment_banks', '[]'), true) ?: [];

        return view('print.invoice', compact('invoice', 'company', 'banks'));
    }

    /**
     * Aksi Upgrade / Downgrade Paket Pelanggan dari Invoice
     */
    public function changePackage(Request $request, Invoice $invoice, \App\Services\RadiusService $radius)
    {
        $request->validate([
            'new_package_id' => 'required|exists:internet_packages,id',
            'billing_action' => 'required|in:prorata_diff,replace_invoice,next_month_only',
            'effective_date' => 'nullable|date',
            'sync_radius' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'pay_now' => 'nullable|boolean',
            'payment_method' => 'nullable|string',
        ]);

        $customer = $invoice->customer;
        if (!$customer) {
            return back()->withErrors(['error' => 'Pelanggan untuk tagihan ini tidak ditemukan.']);
        }

        $oldPackage = $customer->package;
        $newPackage = \App\Models\InternetPackage::findOrFail($request->new_package_id);

        if ($oldPackage && $oldPackage->id == $newPackage->id) {
            return back()->withErrors(['error' => 'Paket baru sama dengan paket pelanggan saat ini.']);
        }

        $oldPrice = (float) ($oldPackage?->price ?? 0);
        $newPrice = (float) $newPackage->price;
        $isUpgrade = $newPrice >= $oldPrice;
        $actionLabel = $isUpgrade ? 'Upgrade' : 'Downgrade';
        $actionType = $isUpgrade ? 'upgrade' : 'downgrade';

        $effectiveDate = $request->effective_date ? \Carbon\Carbon::parse($request->effective_date) : now();
        $daysInMonth = \Carbon\Carbon::create($invoice->period_year ?: $effectiveDate->year, $invoice->period_month ?: $effectiveDate->month, 1)->daysInMonth;
        $effectiveDay = min($daysInMonth, max(1, $effectiveDate->day));

        // 1. Penyesuaian Billing
        if ($request->billing_action === 'prorata_diff') {
            // Pemakaian ke belakang (Paket Lama): tanggal 1 s/d (effectiveDay - 1)
            $pastDays = max(0, $effectiveDay - 1);
            $pastAmount = $pastDays > 0 ? round(($oldPrice / $daysInMonth) * $pastDays) : 0;

            // Pemakaian ke depan (Paket Baru): tanggal effectiveDay s/d akhir bulan (daysInMonth)
            $futureDays = max(1, $daysInMonth - $effectiveDay + 1);
            $futureAmount = round(($newPrice / $daysInMonth) * $futureDays);

            // Terapkan PPN jika ada
            $taxPpn = (float) ($customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
            if ($taxPpn > 0) {
                $pastAmount = round($pastAmount + ($pastAmount * ($taxPpn / 100)));
                $futureAmount = round($futureAmount + ($futureAmount * ($taxPpn / 100)));
            }

            // Total tagihan prorata untuk satu periode bulan berjalan
            $totalProrataAmount = $pastAmount + $futureAmount;
            $oldPackageName = $oldPackage?->name ?? 'Paket Lama';
            $rincianStr = "Prorata {$actionLabel} ke {$newPackage->name} [Lama: {$pastDays} hari ({$oldPackageName} @ Rp " . number_format($pastAmount, 0, ',', '.') . ") + Baru: {$futureDays} hari ({$newPackage->name} @ Rp " . number_format($futureAmount, 0, ',', '.') . ") = Total Rp " . number_format($totalProrataAmount, 0, ',', '.') . "]";

            if (in_array($invoice->status, ['unpaid', 'partial'])) {
                $invoice->amount = $totalProrataAmount;
                $invoice->billing_type = $actionType;
                $invoice->is_prorata = true;
                $invoice->notes = $invoice->notes ? $invoice->notes . ' | ' . $rincianStr : $rincianStr;
                if ($request->filled('notes')) {
                    $invoice->notes .= ' (' . $request->notes . ')';
                }
                
                $totalPaid = (float) $invoice->payments()->sum('amount');
                $invoice->total_paid = $totalPaid;
                $invoice->remaining = max(0, $totalProrataAmount - $totalPaid);
                $invoice->save();

                // Jika dicentang opsi bayar lunas seketika
                if ($request->boolean('pay_now') && $invoice->remaining > 0) {
                    $payMethod = $request->payment_method ?: 'cash';
                    $invoice->payments()->create([
                        'customer_id' => $customer->id,
                        'payment_date' => now(),
                        'amount' => $invoice->remaining,
                        'payment_method' => $payMethod,
                        'notes' => 'Pembayaran lunas seketika saat ' . $actionLabel . ' paket',
                        'processed_by' => auth()->id(),
                    ]);
                    $invoice->update([
                        'status' => 'paid',
                        'total_paid' => $totalProrataAmount,
                        'remaining' => 0,
                    ]);
                }
            } else {
                // Jika invoice berjalan sudah lunas, hitung selisih dari nilai yang telah dibayar
                $paidAmount = (float) $invoice->amount;
                $diff = $totalProrataAmount - $paidAmount;

                if ($diff > 0) {
                    $newInv = Invoice::create([
                        'customer_id' => $customer->id,
                        'period_month' => $invoice->period_month ?: $effectiveDate->month,
                        'period_year' => $invoice->period_year ?: $effectiveDate->year,
                        'amount' => $diff,
                        'due_date' => now()->addDays(7),
                        'issued_date' => now(),
                        'status' => 'unpaid',
                        'is_prorata' => true,
                        'billing_type' => $actionType,
                        'is_reseller_balance' => false,
                        'notes' => "Selisih {$actionLabel} ke {$newPackage->name} periode " . $effectiveDate->translatedFormat('F Y') . " [Prorata Baru: {$futureDays} hari (Rp " . number_format($futureAmount, 0, ',', '.') . ") - Sisa Lama: Rp " . number_format(max(0, $paidAmount - $pastAmount), 0, ',', '.') . " = Rp " . number_format($diff, 0, ',', '.') . "]" . ($request->notes ? ' - ' . $request->notes : ''),
                    ]);

                    if ($request->boolean('pay_now')) {
                        $payMethod = $request->payment_method ?: 'cash';
                        $newInv->payments()->create([
                            'customer_id' => $customer->id,
                            'payment_date' => now(),
                            'amount' => $diff,
                            'payment_method' => $payMethod,
                            'notes' => 'Pembayaran lunas selisih saat ' . $actionLabel . ' paket',
                            'processed_by' => auth()->id(),
                        ]);
                        $newInv->update([
                            'status' => 'paid',
                            'total_paid' => $diff,
                            'remaining' => 0,
                        ]);
                    }
                } elseif ($diff < 0) {
                    $noteAppend = "Kelebihan bayar {$actionLabel} sebesar Rp " . number_format(abs($diff), 0, ',', '.');
                    $invoice->notes = $invoice->notes ? $invoice->notes . ' | ' . $noteAppend : $noteAppend;
                    $invoice->save();
                }
            }
        } elseif ($request->billing_action === 'replace_invoice') {
            if (in_array($invoice->status, ['unpaid', 'partial'])) {
                $invoice->amount = $newPrice;
                $invoice->billing_type = $actionType;
                $noteAppend = "{$actionLabel} ke {$newPackage->name} (Nominal tagihan diubah ke tarif penuh paket baru)";
                $invoice->notes = $invoice->notes ? $invoice->notes . ' | ' . $noteAppend : $noteAppend;
                if ($request->filled('notes')) {
                    $invoice->notes .= ' (' . $request->notes . ')';
                }
                $invoice->save();

                if ($request->boolean('pay_now')) {
                    $payMethod = $request->payment_method ?: 'cash';
                    $invoice->payments()->create([
                        'customer_id' => $customer->id,
                        'payment_date' => now(),
                        'amount' => $newPrice,
                        'payment_method' => $payMethod,
                        'notes' => 'Pembayaran lunas seketika saat ' . $actionLabel . ' paket',
                        'processed_by' => auth()->id(),
                    ]);
                    $invoice->update([
                        'status' => 'paid',
                        'total_paid' => $newPrice,
                        'remaining' => 0,
                    ]);
                }
            }
        } elseif ($request->billing_action === 'next_month_only') {
            $invoice->billing_type = $actionType;
            $noteAppend = "{$actionLabel} ke {$newPackage->name} (Efektif tagihan periode berikutnya)";
            $invoice->notes = $invoice->notes ? $invoice->notes . ' | ' . $noteAppend : $noteAppend;
            if ($request->filled('notes')) {
                $invoice->notes .= ' (' . $request->notes . ')';
            }
            $invoice->save();
        }

        // 2. Update Paket Pelanggan
        $customer->update([
            'package_id' => $newPackage->id,
        ]);

        // 3. Sinkronisasi Profile RADIUS & Disconnect Session
        if ($request->boolean('sync_radius', true)) {
            $radius->guard(function($r) use ($customer) {
                $r->syncCustomer($customer);
                $accounts = $r->customerAccounts($customer);
                foreach (array_keys($accounts) as $username) {
                    $r->disconnect($username);
                }
            });
        }

        return redirect()->back()->with('success', "Pelanggan {$customer->name} berhasil di-{$actionLabel} ke paket {$newPackage->name}." . ($request->boolean('sync_radius', true) ? ' Profile kecepatan RADIUS telah disinkronkan.' : ''));
    }
}
