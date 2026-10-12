<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Reseller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    protected PaymentGatewayService $pgService;

    public function __construct(PaymentGatewayService $pgService)
    {
        $this->pgService = $pgService;
    }

    /**
     * Memulai transaksi pembayaran untuk invoice
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'channel' => 'nullable|string|max:20',
        ]);

        $invoice = Invoice::with('customer.reseller')->findOrFail($validated['invoice_id']);
        $channel = $validated['channel'] ?? 'QRIS';

        if ($invoice->status === 'paid') {
            return back()->with('info', 'Tagihan ini sudah lunas.');
        }

        $result = $this->pgService->createPaymentTransaction($invoice, $channel);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('payment-gateway.pay', $invoice->id);
    }

    /**
     * Reseller Top Up Saldo Mandiri Otomatis
     */
    public function topUpReseller(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000',
            'channel' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();
        $reseller = Reseller::where('user_id', $user->id)->first();

        if (!$reseller) {
            return back()->with('error', 'Akun Anda tidak terdaftar sebagai reseller.');
        }

        $channel = $validated['channel'] ?? 'QRIS';
        $result = $this->pgService->createResellerTopUp($reseller, (float) $validated['amount'], $channel);

        // Cari invoice yang baru dibuat untuk diredirect ke halaman pembayaran
        $invoice = Invoice::where('payment_ref', $result['reference'])->first();
        if ($invoice) {
            return redirect()->route('payment-gateway.pay', $invoice->id);
        }

        return redirect()->route('client-area.dashboard')->with('success', 'Transaksi top up telah dibuat. Silakan lakukan pembayaran.');
    }

    /**
     * Halaman Pembayaran / Checkout
     */
    public function pay(Invoice $invoice)
    {
        $invoice->load(['customer.package', 'customer.reseller']);

        $channels = $this->pgService->getAvailableChannels();
        $payload = json_decode($invoice->payment_gateway_payload ?? '{}', true);

        // Jika transaksi belum pernah dibuat, buat transaksi default (QRIS)
        if (empty($payload)) {
            $payload = $this->pgService->createPaymentTransaction($invoice, 'QRIS');
        }

        return Inertia::render('PaymentGateway/Pay', [
            'invoice' => $invoice,
            'channels' => $channels,
            'paymentData' => $payload,
            'config' => $this->pgService->getConfig(),
        ]);
    }

    /**
     * Webhook Callback dari Payment Gateway (Tripay, Midtrans, dll)
     */
    public function callback(Request $request)
    {
        Log::info('[PaymentGateway Webhook] Received:', $request->all());

        $config = $this->pgService->getConfig();
        $provider = $config['provider'];

        // 1. Validasi Tripay Callback
        if ($provider === 'tripay') {
            $callbackSignature = $request->header('X-Callback-Signature');
            $json = $request->getContent();
            $signature = hash_hmac('sha256', $json, $config['private_key']);

            if ($callbackSignature !== $signature) {
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }

            $data = $request->all();
            $merchantRef = $data['merchant_ref'] ?? null;
            $status = strtoupper($data['status'] ?? '');

            if ($status === 'PAID') {
                $invoice = Invoice::where('payment_ref', $data['reference'] ?? '')
                    ->orWhere('invoice_number', explode('-', $merchantRef)[0])
                    ->first();

                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => $data['payment_method'] ?? 'QRIS',
                        'reference' => $data['reference'] ?? $merchantRef,
                    ]);
                    return response()->json(['success' => true]);
                }
            }

            return response()->json(['success' => true]);
        }

        // 2. Validasi Midtrans Callback
        if ($provider === 'midtrans') {
            $orderId = $request->order_id;
            $statusCode = $request->status_code;
            $grossAmount = $request->gross_amount;
            $serverKey = $config['api_key'];
            $inputSignature = $request->signature_key;

            $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            if ($inputSignature !== $signature) {
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }

            $transactionStatus = $request->transaction_status;
            if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
                $invoice = Invoice::where('payment_ref', $orderId)->first();
                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => $request->payment_type ?? 'QRIS',
                        'reference' => $request->transaction_id ?? $orderId,
                    ]);
                    return response()->json(['success' => true]);
                }
            }

            return response()->json(['success' => true]);
        }

        // 3. Fallback generic callback
        $ref = $request->reference ?? $request->order_id ?? $request->merchant_ref;
        if ($ref) {
            $invoice = Invoice::where('payment_ref', $ref)->first();
            if ($invoice) {
                $this->pgService->settleInvoice($invoice, [
                    'channel' => $request->channel ?? 'QRIS',
                    'reference' => $ref,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Konfirmasi Pembayaran Instan (Auto Settle untuk simulasi / testing sandbox)
     */
    public function simulateSuccess(Request $request, Invoice $invoice)
    {
        $channel = $request->input('channel', $invoice->payment_channel ?? 'QRIS');

        $success = $this->pgService->settleInvoice($invoice, [
            'channel' => $channel,
            'reference' => $invoice->payment_ref ?: ('SIM-' . time()),
        ]);

        if ($success) {
            $isReseller = $invoice->is_reseller_balance;
            $isTopUp = str_contains(strtolower($invoice->notes ?? ''), 'top up') 
                    || str_contains(strtolower($invoice->notes ?? ''), 'isi saldo');

            if ($isReseller && $isTopUp) {
                return redirect()->route('client-area.dashboard')->with('success', 'Pembayaran berhasil! Saldo dompet Anda otomatis bertambah Rp ' . number_format($invoice->amount, 0, ',', '.') . '.');
            } elseif ($isReseller) {
                return redirect()->route('client-area.dashboard')->with('success', 'Pembayaran kasbon berhasil! Tagihan kasbon Anda telah lunas.');
            } else {
                return redirect()->route('customer-area.billing')->with('success', 'Pembayaran berhasil! Tagihan internet Anda telah lunas dan layanan aktif.');
            }
        }

        return back()->with('error', 'Gagal memproses verifikasi pembayaran.');
    }
}
