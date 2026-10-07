<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerBillingController extends Controller
{
    /**
     * Menu Penagihan Lapangan (Untuk Penagih/Kolektor Pelanggan Reguler)
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'unpaid');
        
        $query = Invoice::with(['customer', 'payments'])
            ->where('is_reseller_balance', false); // Khusus pelanggan reguler
            
        if ($activeTab === 'paid') {
            $query->where('status', 'paid');
        } elseif ($activeTab === 'overdue') {
            $query->whereIn('status', ['unpaid', 'partial'])
                  ->where('due_date', '<', Carbon::today());
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

        return Inertia::render('CustomerBilling/Index', [
            'invoices' => $invoices,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Penagih menerima uang di lapangan (Submit Pembayaran Pending)
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
            return back()->with('success', 'Penerimaan pembayaran dari pelanggan berhasil dicatat. Menunggu validasi admin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menu Validasi Setoran Pelanggan (Untuk Admin)
     */
    public function settlements(Request $request)
    {
        $activeTab = $request->get('tab', 'pending');
        
        $query = Payment::with(['invoice.payments', 'customer', 'collector'])
            ->whereHas('invoice', function($q) {
                $q->where('is_reseller_balance', false);
            });

        if ($activeTab === 'verified') {
            $query->where('status', 'verified');
        } else {
            $query->where('status', 'pending');
        }
            
        $payments = $query->latest()->paginate(10)
            ->through(function ($payment) {
                if ($payment->invoice) {
                    $verifiedPaid = $payment->invoice->payments->where('status', 'verified')->sum('amount');
                    $pendingPaid = $payment->invoice->payments->where('status', 'pending')->sum('amount');
                    
                    $payment->invoice->setAttribute('remaining', $payment->invoice->amount - $verifiedPaid);
                    $payment->invoice->setAttribute('total_paid', $verifiedPaid);
                    $payment->invoice->setAttribute('pending_amount', $pendingPaid);
                }
                return $payment;
            });

        return Inertia::render('CustomerBilling/Settlements', [
            'payments' => $payments,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Admin menyetujui setoran penagih
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
            
            // Sync status invoice
            $totalPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
            
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => 'partial']);
            }

            DB::commit();
            return back()->with('success', 'Setoran pelanggan berhasil divalidasi dan tersinkronisasi ke Invoice.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses validasi: ' . $e->getMessage());
        }
    }
}
