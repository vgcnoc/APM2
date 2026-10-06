<?php

namespace App\Http\Controllers;

use App\Models\ResellerBalanceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use Carbon\Carbon;

class ResellerBalanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = ResellerBalanceRequest::with('reseller.customer')
            ->latest()
            ->paginate(10);

        $resellers = \App\Models\Reseller::with('customer')->get();

        return Inertia::render('ResellerRequests/Index', [
            'requests' => $requests,
            'resellers' => $resellers
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
            $balanceRequest = ResellerBalanceRequest::create([
                'reseller_id' => $validated['reseller_id'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'],
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            $reseller = \App\Models\Reseller::find($validated['reseller_id']);
            $reseller->balance += $validated['amount'];
            $reseller->save();

            if ($validated['payment_method'] === 'kasbon') {
                $customer = $reseller->customer;
                if ($customer) {
                    Invoice::create([
                        'invoice_number' => 'INV-' . date('Ym') . '-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT),
                        'customer_id' => $customer->id,
                        'amount' => $validated['amount'],
                        'status' => 'unpaid',
                        'issued_date' => Carbon::now(),
                        'due_date' => Carbon::tomorrow(),
                        'period_label' => 'Kasbon Saldo (' . date('d M Y') . ')',
                        'notes' => 'Kasbon Penambahan Saldo Reseller via Admin. Ref: REQ-' . $balanceRequest->id,
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Saldo reseller berhasil ditambahkan' . ($validated['payment_method'] === 'kasbon' ? ' dan invoice kasbon telah dibuat.' : '.'));
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
            // Update request
            $balanceRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            // Add balance to reseller
            $reseller = $balanceRequest->reseller;
            $reseller->balance += $balanceRequest->amount;
            $reseller->save();

            // If kasbon, create an invoice for tomorrow
            if ($balanceRequest->payment_method === 'kasbon') {
                $customer = $reseller->customer;
                if ($customer) {
                    Invoice::create([
                        'invoice_number' => 'INV-' . date('Ym') . '-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT),
                        'customer_id' => $customer->id,
                        'amount' => $balanceRequest->amount,
                        'status' => 'unpaid',
                        'issued_date' => Carbon::now(),
                        'due_date' => Carbon::tomorrow(),
                        'period_label' => 'Kasbon Saldo (' . date('d M Y') . ')',
                        'notes' => 'Kasbon Penambahan Saldo Reseller. Ref: REQ-' . $balanceRequest->id,
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Permintaan saldo disetujui. Saldo reseller telah bertambah.');
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
}
