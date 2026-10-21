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

    public function update(Request $request, ResellerBalanceRequest $balanceRequest)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'nullable|in:transfer,kasbon,cash',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $newAmount = (float)$validated['amount'];
            $oldAmount = (float)$balanceRequest->amount;
            $diff = $newAmount - $oldAmount;
            $reseller = $balanceRequest->reseller;

            if ($balanceRequest->status === 'approved') {
                if ($diff < 0 && ($reseller->balance + $diff) < 0) {
                    $kurang = abs($reseller->balance + $diff);
                    return back()->with('error', 'Tidak dapat mengurangi nominal: Saldo reseller saat ini (Rp ' . number_format($reseller->balance, 0, ',', '.') . ') tidak mencukupi untuk pengurangan (Rp ' . number_format(abs($diff), 0, ',', '.') . '). Kurang Rp ' . number_format($kurang, 0, ',', '.') . '.');
                }

                $reseller->balance += $diff;
                $reseller->save();

                $koreksiNote = " [Koreksi dari Rp " . number_format($oldAmount, 0, ',', '.') . " ke Rp " . number_format($newAmount, 0, ',', '.') . ($validated['reason'] ? ": {$validated['reason']}" : "") . "]";

                // Update transaksi mutasi terkait
                $tx = ResellerTransaction::where('reseller_id', $reseller->id)
                    ->where(function($q) use ($balanceRequest) {
                        $q->where('reference_id', 'TOPUP-' . $balanceRequest->id)
                          ->orWhere('reference_id', 'REQ-' . $balanceRequest->id);
                    })->first();

                if ($tx) {
                    $tx->amount = $newAmount;
                    $cleanDesc = preg_replace('/ \[Koreksi.*?\]/', '', $tx->description);
                    $tx->description = $cleanDesc . $koreksiNote;
                    $tx->save();
                } else {
                    ResellerTransaction::create([
                        'reseller_id' => $reseller->id,
                        'type' => 'credit',
                        'amount' => $newAmount,
                        'description' => 'Penambahan Saldo (' . ucfirst($balanceRequest->payment_method) . ') via Admin' . $koreksiNote,
                        'reference_id' => 'TOPUP-' . $balanceRequest->id,
                    ]);
                }

                // Update invoice terkait (jika ada)
                Invoice::where('is_reseller_balance', true)
                    ->where(function($q) use ($balanceRequest) {
                        $q->where('notes', 'like', "%REQ-{$balanceRequest->id}%");
                    })
                    ->each(function($inv) use ($newAmount, $validated, $balanceRequest) {
                        $inv->amount = $newAmount;
                        if (!empty($validated['payment_method'])) {
                            $isKasbon = $validated['payment_method'] === 'kasbon';
                            $inv->status = $isKasbon ? 'unpaid' : 'paid';
                        }
                        $inv->save();

                        if ($balanceRequest->payment_method !== 'kasbon') {
                            $inv->payments()->update(['amount' => $newAmount]);
                        }
                    });
            }

            $updateData = ['amount' => $newAmount];
            if (!empty($validated['payment_method'])) {
                $updateData['payment_method'] = $validated['payment_method'];
            }
            if (isset($validated['notes'])) {
                $updateData['notes'] = $validated['notes'];
            }
            if (!empty($validated['reason'])) {
                $updateData['notes'] = trim(($updateData['notes'] ?? $balanceRequest->notes ?? '') . " [Koreksi: {$validated['reason']}]");
            }
            $balanceRequest->update($updateData);

            DB::commit();
            return back()->with('success', 'Nominal saldo berhasil dikoreksi menjadi Rp ' . number_format($newAmount, 0, ',', '.') . '. Saldo reseller telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengoreksi nominal: ' . $e->getMessage());
        }
    }

    public function destroy(ResellerBalanceRequest $balanceRequest)
    {
        DB::beginTransaction();
        try {
            $reseller = $balanceRequest->reseller;

            if ($balanceRequest->status === 'approved') {
                if ($reseller->balance < $balanceRequest->amount) {
                    return back()->with('error', 'Tidak dapat membatalkan: Saldo reseller saat ini (Rp ' . number_format($reseller->balance, 0, ',', '.') . ') lebih kecil dari nominal yang ingin dibatalkan (Rp ' . number_format($balanceRequest->amount, 0, ',', '.') . ').');
                }
                $reseller->balance -= $balanceRequest->amount;
                $reseller->save();
            }

            // Hapus transaksi mutasi terkait
            ResellerTransaction::where('reseller_id', $reseller->id)
                ->where(function($q) use ($balanceRequest) {
                    $q->where('reference_id', 'TOPUP-' . $balanceRequest->id)
                      ->orWhere('reference_id', 'REQ-' . $balanceRequest->id);
                })->delete();

            // Batalkan atau hapus invoice kasbon terkait
            Invoice::where('is_reseller_balance', true)
                ->where(function($q) use ($balanceRequest) {
                    $q->where('notes', 'like', "%REQ-{$balanceRequest->id}%");
                })
                ->each(function($inv) {
                    if ($inv->status === 'unpaid') {
                        $inv->delete();
                    } else {
                        $inv->update(['status' => 'cancelled', 'notes' => trim(($inv->notes ?? '') . ' [Permintaan Dibatalkan]')]);
                    }
                });

            $balanceRequest->update([
                'status' => 'rejected',
                'notes' => trim(($balanceRequest->notes ?? '') . ' [Dibatalkan Admin]')
            ]);

            DB::commit();
            return back()->with('success', 'Pengisian saldo berhasil dibatalkan dan saldo reseller telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan pengisian saldo: ' . $e->getMessage());
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

    public function updateTransaction(Request $request, ResellerTransaction $transaction)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'reason' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $newAmount = (float)$validated['amount'];
            $oldAmount = (float)$transaction->amount;
            $diff = $newAmount - $oldAmount;
            $reseller = $transaction->reseller;

            if ($transaction->type === 'credit') {
                if ($diff < 0 && ($reseller->balance + $diff) < 0) {
                    $kurang = abs($reseller->balance + $diff);
                    return back()->with('error', 'Tidak dapat mengurangi nominal: Saldo reseller saat ini (Rp ' . number_format($reseller->balance, 0, ',', '.') . ') tidak mencukupi untuk pengurangan (Rp ' . number_format(abs($diff), 0, ',', '.') . '). Kurang Rp ' . number_format($kurang, 0, ',', '.') . '.');
                }
                $reseller->balance += $diff;
            } else {
                if ($diff > 0 && ($reseller->balance - $diff) < 0) {
                    $kurang = abs($reseller->balance - $diff);
                    return back()->with('error', 'Tidak dapat menambah debit: Saldo reseller saat ini (Rp ' . number_format($reseller->balance, 0, ',', '.') . ') tidak mencukupi. Kurang Rp ' . number_format($kurang, 0, ',', '.') . '.');
                }
                $reseller->balance -= $diff;
            }
            $reseller->save();

            $koreksiNote = " [Koreksi dari Rp " . number_format($oldAmount, 0, ',', '.') . " ke Rp " . number_format($newAmount, 0, ',', '.') . ($validated['reason'] ? ": {$validated['reason']}" : "") . "]";
            
            $cleanDesc = preg_replace('/ \[Koreksi.*?\]/', '', $transaction->description);
            $transaction->amount = $newAmount;
            $transaction->description = $cleanDesc . $koreksiNote;
            $transaction->save();

            // Jika ada balance request terkait, sinkronkan juga
            if (str_starts_with($transaction->reference_id ?? '', 'TOPUP-') || str_starts_with($transaction->reference_id ?? '', 'REQ-')) {
                $reqId = str_replace(['TOPUP-', 'REQ-'], '', $transaction->reference_id);
                $balReq = ResellerBalanceRequest::find($reqId);
                if ($balReq) {
                    $balReq->update([
                        'amount' => $newAmount,
                        'notes' => trim(($balReq->notes ?? '') . $koreksiNote)
                    ]);
                }

                // Sinkronkan invoice terkait
                Invoice::where('is_reseller_balance', true)
                    ->where(function($q) use ($reqId) {
                        $q->where('notes', 'like', "%REQ-{$reqId}%");
                    })
                    ->each(function($inv) use ($newAmount, $balReq) {
                        $inv->update(['amount' => $newAmount]);
                        if ($balReq && $balReq->payment_method !== 'kasbon') {
                            $inv->payments()->update(['amount' => $newAmount]);
                        }
                    });
            }

            DB::commit();
            return back()->with('success', 'Transaksi mutasi berhasil dikoreksi menjadi Rp ' . number_format($newAmount, 0, ',', '.') . '. Saldo reseller telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengoreksi transaksi: ' . $e->getMessage());
        }
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

                // Batalkan atau hapus invoice kasbon terkait
                Invoice::where('is_reseller_balance', true)
                    ->where(function($q) use ($reqId) {
                        $q->where('notes', 'like', "%REQ-{$reqId}%");
                    })
                    ->each(function($inv) {
                        if ($inv->status === 'unpaid') {
                            $inv->delete();
                        } else {
                            $inv->update(['status' => 'cancelled', 'notes' => trim(($inv->notes ?? '') . ' [Transaksi Dibatalkan]')]);
                        }
                    });
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
