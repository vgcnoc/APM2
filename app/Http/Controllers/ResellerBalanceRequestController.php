<?php

namespace App\Http\Controllers;

use App\Models\ResellerBalanceRequest;
use App\Models\ResellerTransaction;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use Carbon\Carbon;

class ResellerBalanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'transactions');

        // 1. Transactions Query (Mutasi Saldo)
        $txQuery = ResellerTransaction::with(['reseller.customer', 'reseller.user'])
            ->latest('id');

        if ($request->filled('reseller_id')) {
            $txQuery->where('reseller_id', $request->reseller_id);
        }

        if ($request->filled('type') && in_array($request->type, ['credit', 'debit'])) {
            $txQuery->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $txQuery->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhereHas('reseller.customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reseller.user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('start_date')) {
            $txQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $txQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $txQuery->paginate(15, ['*'], 'tx_page')->withQueryString();

        // 2. Requests Query (Permintaan Saldo / Topup)
        $reqQuery = ResellerBalanceRequest::with(['reseller.customer', 'reseller.user', 'approver'])
            ->latest('id');

        if ($request->filled('reseller_id')) {
            $reqQuery->where('reseller_id', $request->reseller_id);
        }

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected', 'paid'])) {
            $reqQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $reqQuery->where(function($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('reseller.customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reseller.user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $reqQuery->paginate(15, ['*'], 'req_page')->withQueryString();

        // 3. Resellers List (for filter & modal)
        $resellers = Reseller::with(['customer', 'user'])->get()->map(function($res) {
            $name = $res->customer?->name ?? $res->user?->name ?? ('Reseller #' . $res->id);
            $code = $res->customer?->customer_code ?? $res->user?->email ?? ('ID:' . $res->id);
            $phone = $res->customer?->phone ?? $res->user?->phone ?? '-';
            return [
                'id' => $res->id,
                'name' => $name,
                'code' => $code,
                'phone' => $phone,
                'balance' => (float)$res->balance,
                'is_active' => (bool)$res->is_active,
                'has_customer' => !empty($res->customer_id),
            ];
        });

        // 4. KPI Stats
        $stats = [
            'total_saldo_beredar' => (float)Reseller::sum('balance'),
            'total_credit' => (float)ResellerTransaction::where('type', 'credit')->sum('amount'),
            'total_debit' => (float)ResellerTransaction::where('type', 'debit')->sum('amount'),
            'pending_requests_count' => ResellerBalanceRequest::where('status', 'pending')->count(),
            'pending_requests_amount' => (float)ResellerBalanceRequest::where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('ResellerRequests/Index', [
            'transactions' => $transactions,
            'requests' => $requests,
            'resellers' => $resellers,
            'stats' => $stats,
            'filters' => $request->only(['tab', 'search', 'reseller_id', 'type', 'status', 'start_date', 'end_date'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reseller_id' => 'required|exists:resellers,id',
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'required|in:transfer,kasbon,cash',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $reseller = Reseller::with(['customer', 'user'])->findOrFail($validated['reseller_id']);
            $customer = $reseller->customer;

            if ($validated['payment_method'] === 'kasbon' && !$customer) {
                return back()->with('error', 'Metode Kasbon hanya bisa digunakan untuk reseller yang terhubung dengan data Pelanggan (agar invoice kasbon dapat diterbitkan dan ditagih).');
            }

            $balanceRequest = ResellerBalanceRequest::create([
                'reseller_id' => $reseller->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'],
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            $reseller->balance += $validated['amount'];
            $reseller->save();

            ResellerTransaction::create([
                'reseller_id' => $reseller->id,
                'type' => 'credit',
                'amount' => $validated['amount'],
                'description' => 'Penambahan Saldo (' . ucfirst($validated['payment_method']) . ') via Admin' . ($validated['notes'] ? ' - ' . $validated['notes'] : ''),
                'reference_id' => 'TOPUP-' . $balanceRequest->id,
            ]);

            // Jika ada customer, buatkan invoice pencatatan keuangan
            if ($customer) {
                $isKasbon = $validated['payment_method'] === 'kasbon';
                
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'amount' => $validated['amount'],
                    'status' => $isKasbon ? 'unpaid' : 'paid',
                    'issued_date' => Carbon::now(),
                    'due_date' => $isKasbon ? Carbon::tomorrow() : Carbon::now(),
                    'period_month' => (int)date('n'),
                    'period_year' => (int)date('Y'),
                    'is_reseller_balance' => true,
                    'notes' => ($isKasbon ? 'Kasbon' : 'Pembelian') . ' Saldo Reseller via Admin. Ref: REQ-' . $balanceRequest->id,
                ]);

                // Jika cash atau transfer, langsung buatkan record pembayaran (Lunas)
                if (!$isKasbon) {
                    $invoice->payments()->create([
                        'customer_id' => $customer->id,
                        'payment_date' => Carbon::now(),
                        'amount' => $validated['amount'],
                        'payment_method' => $validated['payment_method'] === 'transfer' ? 'transfer' : 'cash',
                        'notes' => 'Pembayaran Langsung via Admin (' . $validated['payment_method'] . ')',
                        'status' => 'verified',
                        'collected_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Saldo reseller berhasil ditambahkan sebesar Rp ' . number_format($validated['amount'], 0, ',', '.') . ($validated['payment_method'] === 'kasbon' ? ' dan invoice kasbon telah dibuat.' : '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambah saldo: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, ResellerBalanceRequest $balanceRequest)
    {
        if ($balanceRequest->status !== 'pending') {
            return back()->with('error', 'Hanya permintaan berstatus pending yang bisa disetujui.');
        }

        DB::beginTransaction();
        try {
            $balanceRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            $reseller = $balanceRequest->reseller;
            $reseller->balance += $balanceRequest->amount;
            $reseller->save();

            ResellerTransaction::create([
                'reseller_id' => $reseller->id,
                'type' => 'credit',
                'amount' => $balanceRequest->amount,
                'description' => 'Persetujuan Permintaan Saldo (' . ucfirst($balanceRequest->payment_method) . ')' . ($balanceRequest->notes ? ' - ' . $balanceRequest->notes : ''),
                'reference_id' => 'REQ-' . $balanceRequest->id,
            ]);

            $customer = $reseller->customer;
            if ($customer) {
                $isKasbon = $balanceRequest->payment_method === 'kasbon';
                
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'amount' => $balanceRequest->amount,
                    'status' => $isKasbon ? 'unpaid' : 'paid',
                    'issued_date' => Carbon::now(),
                    'due_date' => $isKasbon ? Carbon::tomorrow() : Carbon::now(),
                    'period_month' => (int)date('n'),
                    'period_year' => (int)date('Y'),
                    'is_reseller_balance' => true,
                    'notes' => ($isKasbon ? 'Kasbon' : 'Pembelian') . ' Saldo Reseller (Approval). Ref: REQ-' . $balanceRequest->id,
                ]);

                if (!$isKasbon) {
                    $invoice->payments()->create([
                        'customer_id' => $customer->id,
                        'payment_date' => Carbon::now(),
                        'amount' => $balanceRequest->amount,
                        'payment_method' => $balanceRequest->payment_method === 'transfer' ? 'transfer' : 'cash',
                        'notes' => 'Disetujui Admin (' . $balanceRequest->payment_method . ')',
                        'status' => 'verified',
                        'collected_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Permintaan saldo disetujui. Saldo reseller bertambah Rp ' . number_format($balanceRequest->amount, 0, ',', '.') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, ResellerBalanceRequest $balanceRequest)
    {
        if ($balanceRequest->status !== 'pending') {
            return back()->with('error', 'Hanya permintaan berstatus pending yang bisa ditolak.');
        }

        $balanceRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Permintaan saldo telah ditolak.');
    }

    public function destroyTransaction(Request $request, ResellerTransaction $transaction)
    {
        DB::beginTransaction();
        try {
            $reseller = $transaction->reseller;
            if ($transaction->type === 'credit') {
                if ($reseller->balance < $transaction->amount) {
                    return back()->with('error', 'Tidak dapat membatalkan transaksi kredit: Saldo reseller saat ini (Rp ' . number_format($reseller->balance, 0, ',', '.') . ') lebih kecil dari nominal yang ingin dibatalkan (Rp ' . number_format($transaction->amount, 0, ',', '.') . ').');
                }
                $reseller->balance -= $transaction->amount;
            } else {
                $reseller->balance += $transaction->amount;
            }
            $reseller->save();

            // Jika ada balance request terkait
            if (str_starts_with($transaction->reference_id ?? '', 'TOPUP-') || str_starts_with($transaction->reference_id ?? '', 'REQ-')) {
                $reqId = str_replace(['TOPUP-', 'REQ-'], '', $transaction->reference_id);
                $balReq = ResellerBalanceRequest::find($reqId);
                if ($balReq) {
                    $balReq->update(['status' => 'rejected', 'notes' => trim(($balReq->notes ?? '') . ' [Dibatalkan Admin]')]);
                }
            }

            $transaction->delete();
            DB::commit();

            return back()->with('success', 'Transaksi mutasi berhasil dibatalkan dan saldo reseller telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }
}
