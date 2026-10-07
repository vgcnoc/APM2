<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResellerBillingController extends Controller
{
    /**
     * Menu Penagihan (Untuk Penagih/Kolektor)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'unpaid');
        
        $query = Invoice::with(['customer', 'payments'])
            ->whereHas('customer', function($q) {
                $q->where('is_reseller', true);
            })
            ->where('is_reseller_balance', true);
            
        if ($activeTab === 'paid') {
            $query->where('status', 'paid');
        } else {
            $query->whereIn('status', ['unpaid', 'partial']);
        }
            
        $invoices = $query->latest()->paginate(10)
            ->through(function ($invoice) {
                $verifiedPaid = $invoice->payments->where('status', 'verified')->sum('amount');
                $pendingPaid = $invoice->payments->where('status', 'pending')->sum('amount');
                
                return array_merge($invoice->toArray(), [
                    'remaining' => $invoice->amount - $verifiedPaid,
                    'total_paid' => $verifiedPaid,
                    'pending_amount' => $pendingPaid,
                    'effective_remaining' => $invoice->amount - $verifiedPaid - $pendingPaid
                ]);
            });

        return Inertia::render('ResellerBilling/Index', [
            'invoices' => $invoices,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Penagih menerima uang (Submit Pembayaran Pending)
     */
    public function collect(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'is_direct_payment' => 'nullable|boolean'
        ]);

        if ($validated['amount'] > $invoice->remaining) {
            return back()->with('error', 'Nominal melebihi sisa tagihan.');
        }

        $isDirect = !empty($validated['is_direct_payment']);

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'],
                'status' => $isDirect ? 'verified' : 'pending',
                'collected_by' => auth()->id()
            ]);

            if ($isDirect) {
                // If it's a direct payment at the office, settle immediately
                $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
                if ($totalPaid >= $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($totalPaid > 0) {
                    $invoice->update(['status' => 'partial']);
                }
                
                DB::commit();
                return back()->with('success', 'Pembayaran langsung dikantor berhasil diproses dan invoice diupdate.');
            }

            DB::commit();
            return back()->with('success', 'Penerimaan pembayaran berhasil dicatat. Menunggu pelunasan dari perusahaan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menu Pelunasan (Untuk Perusahaan/Admin)
     */
    public function settlements(Request $request)
    {
        // Only show pending payments for Resellers
        $payments = Payment::with(['invoice', 'customer', 'collector'])
            ->where('status', 'pending')
            ->whereHas('customer', function($q) {
                $q->where('is_reseller', true);
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('ResellerBilling/Settlements', [
            'payments' => $payments
        ]);
    }

    /**
     * Perusahaan menyetujui / melunasi pembayaran
     */
    public function approve(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Pembayaran ini sudah tidak berstatus pending.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'verified'
            ]);

            $invoice = $payment->invoice;
            
            // Check if invoice needs to change status
            $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
            
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => 'partial']);
            }

            DB::commit();
            return back()->with('success', 'Pelunasan berhasil disetujui. Saldo tagihan telah berkurang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pelunasan: ' . $e->getMessage());
        }
    }
}
