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
     * Webhook Callback dari Payment Gateway (Tripay, Midtrans, Xendit, Duitku, iPaymu, Paydisini, Mayar, dll)
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
                Log::warning('[PaymentGateway Webhook] Tripay invalid signature');
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
                Log::warning('[PaymentGateway Webhook] Midtrans invalid signature');
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }

            $transactionStatus = $request->transaction_status;
            if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
                $invoice = Invoice::where('payment_ref', $orderId)
                    ->orWhere('invoice_number', explode('-', $orderId)[0])
                    ->first();
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

        // 3. Validasi Xendit Callback
        if ($provider === 'xendit') {
            $xenditToken = $request->header('x-callback-token');
            $expectedToken = $config['callback_token'] ?: $config['private_key'];

            if (!empty($expectedToken) && $xenditToken !== $expectedToken) {
                Log::warning('[PaymentGateway Webhook] Xendit invalid token');
                return response()->json(['success' => false, 'message' => 'Invalid callback token'], 403);
            }

            $status = strtoupper($request->status ?? '');
            if ($status === 'PAID') {
                $externalId = $request->external_id;
                $invoice = Invoice::where('payment_ref', $request->id)
                    ->orWhere('payment_ref', $externalId)
                    ->orWhere('invoice_number', explode('-', $externalId)[0])
                    ->first();

                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => $request->payment_method ?? 'XENDIT',
                        'reference' => $request->id ?? $externalId,
                    ]);
                    return response()->json(['success' => true]);
                }
            }

            return response()->json(['success' => true]);
        }

        // 4. Validasi Duitku Callback
        if ($provider === 'duitku') {
            $merchantOrderId = $request->merchantOrderId;
            $amount = $request->amount;
            $resultCode = $request->resultCode;
            $signature = $request->signature;

            $expectedSignature = md5($config['merchant_id'] . $amount . $merchantOrderId . $config['api_key']);
            if ($signature !== $expectedSignature) {
                Log::warning('[PaymentGateway Webhook] Duitku invalid signature');
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }

            if ($resultCode === '00') {
                $invoice = Invoice::where('payment_ref', $request->reference ?? '')
                    ->orWhere('payment_ref', $merchantOrderId)
                    ->orWhere('invoice_number', explode('-', $merchantOrderId)[0])
                    ->first();

                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => $request->paymentCode ?? 'DUITKU',
                        'reference' => $request->reference ?? $merchantOrderId,
                    ]);
                    return response()->json(['success' => true]);
                }
            }

            return response()->json(['success' => true]);
        }

        // 5. Validasi iPaymu Callback
        if ($provider === 'ipaymu') {
            $status = strtolower($request->status ?? '');
            if ($status === 'berhasil') {
                $trxId = $request->trx_id ?? $request->sid;
                $invoice = Invoice::where('payment_ref', $trxId)
                    ->orWhere('payment_ref', $request->reference)
                    ->first();

                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => $request->channel ?? 'IPAYMU',
                        'reference' => $trxId,
                    ]);
                    return response()->json(['success' => true]);
                }
            }

            return response()->json(['success' => true]);
        }

        // 6. Validasi Paydisini Callback
        if ($provider === 'paydisini') {
            $uniqueCode = $request->unique_code;
            $status = $request->status;
            $signature = $request->signature;

            $expectedSignature = md5($config['api_key'] . $uniqueCode . 'Callback');
            if ($signature !== $expectedSignature) {
                Log::warning('[PaymentGateway Webhook] Paydisini invalid signature');
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }

            if ($status === 'Success') {
                $invoice = Invoice::where('payment_ref', $request->pay_id ?? '')
                    ->orWhere('payment_ref', $uniqueCode)
                    ->orWhere('invoice_number', explode('-', $uniqueCode)[0])
                    ->first();

                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => $request->service_name ?? 'PAYDISINI',
                        'reference' => $request->pay_id ?? $uniqueCode,
                    ]);
                    return response()->json(['success' => true, 'message' => 'Payment settled']);
                }
            }

            return response()->json(['success' => true]);
        }

        // 7. Validasi Mayar Callback
        if ($provider === 'mayar') {
            $event = $request->event ?? '';
            if (in_array($event, ['payment.received', 'payment.success'])) {
                $data = $request->data ?? [];
                $id = $data['id'] ?? null;
                $invoice = Invoice::where('payment_ref', $id)->first();

                if ($invoice) {
                    $this->pgService->settleInvoice($invoice, [
                        'channel' => 'MAYAR',
                        'reference' => $id,
                    ]);
                    return response()->json(['success' => true]);
                }
            }

            return response()->json(['success' => true]);
        }

        // 8. Fallback Generic Callback
        $ref = $request->reference ?? $request->order_id ?? $request->merchant_ref ?? $request->external_id ?? $request->unique_code;
        if ($ref) {
            $invoice = Invoice::where('payment_ref', $ref)
                ->orWhere('invoice_number', explode('-', $ref)[0])
                ->first();
            if ($invoice) {
                $this->pgService->settleInvoice($invoice, [
                    'channel' => $request->channel ?? $request->payment_method ?? 'PAYMENT_GATEWAY',
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
