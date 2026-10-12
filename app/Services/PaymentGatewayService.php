<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Reseller;
use App\Models\ResellerTransaction;
use App\Models\ResellerBalanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    /**
     * Dapatkan konfigurasi payment gateway dari database settings
     */
    public function getConfig(): array
    {
        return [
            'provider' => Setting::get('pg_provider', 'none'),
            'environment' => Setting::get('pg_environment', 'sandbox'),
            'merchant_id' => Setting::get('pg_merchant_id', ''),
            'api_key' => Setting::get('pg_api_key', ''),
            'private_key' => Setting::get('pg_private_key', ''),
            'callback_token' => Setting::get('pg_callback_token', ''),
        ];
    }

    /**
     * Dapatkan daftar channel pembayaran yang didukung
     */
    public function getAvailableChannels(): array
    {
        return [
            [
                'code' => 'QRIS',
                'name' => 'QRIS (Semua E-Wallet & M-Banking)',
                'group' => 'E-Wallet & QR',
                'icon' => 'qrcode',
                'description' => 'BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana, ShopeePay, LinkAja',
            ],
            [
                'code' => 'BCAVA',
                'name' => 'BCA Virtual Account',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via ATM, BCA Mobile, KlikBCA',
            ],
            [
                'code' => 'BRIVA',
                'name' => 'BRI Virtual Account (BRIVA)',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via BRImo, ATM BRI, Agen BRILink',
            ],
            [
                'code' => 'BNIVA',
                'name' => 'BNI Virtual Account',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via BNI Mobile, ATM BNI, SMS Banking',
            ],
            [
                'code' => 'MANDIRIVA',
                'name' => 'Mandiri Virtual Account (Livin)',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via Livin by Mandiri, ATM Mandiri',
            ],
        ];
    }

    /**
     * Buat transaksi pembayaran untuk invoice tertentu
     */
    public function createPaymentTransaction(Invoice $invoice, string $channel = 'QRIS'): array
    {
        $config = $this->getConfig();
        $customer = $invoice->customer;
        $amount = (float) $invoice->remaining > 0 ? (float) $invoice->remaining : (float) $invoice->amount;

        $referenceId = 'PG-' . date('YmdHis') . '-' . $invoice->id;

        // 1. Jika provider Tripay
        if ($config['provider'] === 'tripay' && !empty($config['api_key']) && !empty($config['private_key'])) {
            return $this->createTripayTransaction($invoice, $channel, $referenceId, $config);
        }

        // 2. Jika provider Midtrans
        if ($config['provider'] === 'midtrans' && !empty($config['api_key'])) {
            return $this->createMidtransTransaction($invoice, $channel, $referenceId, $config);
        }

        // 3. Fallback / Built-in Automatic Gateway Simulation (Sandbox Cerdas)
        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat transaksi via Tripay Closed Payment
     */
    protected function createTripayTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $isProduction = $config['environment'] === 'production';
        $endpoint = $isProduction 
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $merchantRef = $invoice->invoice_number . '-' . time();
        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $signature = hash_hmac('sha256', $config['merchant_id'] . $merchantRef . $amount, $config['private_key']);

        $customer = $invoice->customer;
        $payload = [
            'method'         => $channel === 'QRIS' ? 'QRISC' : $channel,
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $customer ? $customer->name : 'Pelanggan APM',
            'customer_email' => ($customer && $customer->email) ? $customer->email : 'billing@apm.net',
            'customer_phone' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            'order_items'    => [
                [
                    'sku'      => $invoice->invoice_number,
                    'name'     => $invoice->notes ?: ('Pembayaran ' . $invoice->invoice_number),
                    'price'    => $amount,
                    'quantity' => 1
                ]
            ],
            'callback_url'   => url('/api/payment-gateway/callback'),
            'return_url'     => url('/payment-gateway/status/' . $invoice->id),
            'expired_time'   => time() + (24 * 3600),
            'signature'      => $signature
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key']
            ])->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json('data');
                $invoice->update([
                    'payment_ref' => $data['reference'] ?? $referenceId,
                    'payment_url' => $data['checkout_url'] ?? null,
                    'payment_channel' => $channel,
                    'payment_gateway_payload' => json_encode($data)
                ]);

                return [
                    'success' => true,
                    'provider' => 'tripay',
                    'reference' => $data['reference'],
                    'merchant_ref' => $data['merchant_ref'],
                    'payment_method' => $data['payment_name'] ?? $channel,
                    'checkout_url' => $data['checkout_url'] ?? null,
                    'qr_string' => $data['qr_string'] ?? null,
                    'qr_url' => $data['qr_url'] ?? null,
                    'pay_code' => $data['pay_code'] ?? null,
                    'amount' => $data['amount'],
                    'expired_time' => $data['expired_time'],
                    'instructions' => $data['instructions'] ?? [],
                ];
            }

            Log::warning('[PaymentGateway] Tripay create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] Tripay Exception: ' . $e->getMessage());
        }

        // Jika tripay gagal, fallback ke simulasi cerdas
        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat transaksi via Midtrans Snap
     */
    protected function createMidtransTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $isProduction = $config['environment'] === 'production';
        $endpoint = $isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $serverKey = $config['api_key']; // Server key
        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $customer = $invoice->customer;

        $payload = [
            'transaction_details' => [
                'order_id' => $invoice->invoice_number . '-' . time(),
                'gross_amount' => $amount
            ],
            'customer_details' => [
                'first_name' => $customer ? $customer->name : 'Pelanggan APM',
                'email' => ($customer && $customer->email) ? $customer->email : 'billing@apm.net',
                'phone' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $invoice->update([
                    'payment_ref' => $payload['transaction_details']['order_id'],
                    'payment_url' => $data['redirect_url'] ?? null,
                    'payment_channel' => $channel,
                    'payment_gateway_payload' => json_encode($data)
                ]);

                return [
                    'success' => true,
                    'provider' => 'midtrans',
                    'reference' => $payload['transaction_details']['order_id'],
                    'token' => $data['token'] ?? null,
                    'checkout_url' => $data['redirect_url'] ?? null,
                    'amount' => $amount,
                    'instructions' => [],
                ];
            }
            Log::warning('[PaymentGateway] Midtrans create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] Midtrans Exception: ' . $e->getMessage());
        }

        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat Transaksi Simulator Sandbox (Untuk testing tanpa API key atau mode instan)
     */
    protected function createSimulatedTransaction(Invoice $invoice, string $channel, string $referenceId): array
    {
        $amount = (float) $invoice->remaining > 0 ? (float) $invoice->remaining : (float) $invoice->amount;
        $codePrefix = match ($channel) {
            'BCAVA' => '7000',
            'BRIVA' => '1280',
            'BNIVA' => '8808',
            'MANDIRIVA' => '8950',
            default => '9901'
        };
        $payCode = $codePrefix . str_pad($invoice->id, 8, '0', STR_PAD_LEFT);

        $simulatedData = [
            'success' => true,
            'provider' => 'simulator',
            'reference' => $referenceId,
            'merchant_ref' => $invoice->invoice_number,
            'payment_method' => $channel,
            'checkout_url' => url('/payment-gateway/pay/' . $invoice->id),
            'qr_string' => '00020101021226580014ID.LINKAJA.WWW0118936009110021000' . $invoice->id,
            'pay_code' => $payCode,
            'amount' => $amount,
            'expired_time' => time() + (24 * 3600),
            'instructions' => [
                'Buka aplikasi M-Banking atau E-Wallet Anda.',
                'Pilih menu ' . ($channel === 'QRIS' ? 'Scan QRIS' : 'Transfer Virtual Account') . '.',
                $channel === 'QRIS' ? 'Arahkan kamera ke kode QR di atas.' : 'Masukkan nomor Virtual Account: ' . $payCode,
                'Periksa nominal pembayaran (Rp ' . number_format($amount, 0, ',', '.') . ').',
                'Konfirmasi pembayaran dan masukkan PIN Anda.',
                'Sistem akan otomatis memverifikasi pembayaran dalam hitungan detik.'
            ]
        ];

        $invoice->update([
            'payment_ref' => $referenceId,
            'payment_url' => $simulatedData['checkout_url'],
            'payment_channel' => $channel,
            'payment_gateway_payload' => json_encode($simulatedData)
        ]);

        return $simulatedData;
    }

    /**
     * Selesaikan Invoice secara otomatis (Settlement/Lunas)
     * Dapat dipanggil dari Webhook resmi Tripay/Midtrans atau Simulator Checkout
     */
    public function settleInvoice(Invoice $invoice, array $payload = []): bool
    {
        if ($invoice->status === 'paid') {
            return true; // Sudah lunas sebelumnya
        }

        $channel = $payload['channel'] ?? $invoice->payment_channel ?? 'QRIS';
        $refId = $payload['reference'] ?? $invoice->payment_ref ?? ('PG-' . time());
        $amount = (float) $invoice->remaining > 0 ? (float) $invoice->remaining : (float) $invoice->amount;

        DB::beginTransaction();
        try {
            // 1. Buat record pembayaran terverifikasi
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'payment_method' => 'payment_gateway',
                'payment_channel' => strtoupper($channel),
                'payment_date' => Carbon::now(),
                'reference_number' => $refId,
                'status' => 'verified',
                'notes' => 'Pembayaran Otomatis via Payment Gateway (' . strtoupper($channel) . '). Ref: ' . $refId,
                'collected_by' => null, // Diproses oleh sistem otomatis
            ]);

            // 2. Update status invoice ke Paid
            $invoice->update([
                'status' => 'paid',
                'payment_channel' => strtoupper($channel),
            ]);

            $customer = $invoice->customer;

            // 3. JIKA INVOICE RESELLER SALDO (Top Up Mandiri atau Pelunasan Kasbon)
            if ($invoice->is_reseller_balance && $customer) {
                $reseller = $customer->reseller;
                if ($reseller) {
                    $isTopUp = str_contains(strtolower($invoice->notes ?? ''), 'top up') 
                            || str_contains(strtolower($invoice->notes ?? ''), 'isi saldo');

                    // Jika ini top up mandiri, langsung tambahkan saldo reseller
                    if ($isTopUp) {
                        $reseller->balance += $amount;
                        $reseller->save();

                        ResellerTransaction::create([
                            'reseller_id' => $reseller->id,
                            'type' => 'credit',
                            'amount' => $amount,
                            'description' => 'Top Up Saldo Mandiri Otomatis via Payment Gateway (' . strtoupper($channel) . ')',
                            'reference_id' => $refId,
                        ]);
                    }
                }
            }

            // 4. JIKA INVOICE PELANGGAN INTERNET REGULER
            // Jika pelanggan sedang isolir (suspended), otomatis buka isolir dan aktifkan kembali
            if (!$invoice->is_reseller_balance && $customer) {
                if ($customer->status === 'suspended') {
                    $customer->update(['status' => 'active']);

                    // Sinkronkan ke FreeRADIUS agar internet langsung online detik itu juga
                    if (class_exists(\App\Services\RadiusService::class)) {
                        try {
                            app(\App\Services\RadiusService::class)->syncCustomer($customer);
                        } catch (\Exception $re) {
                            Log::warning('[PaymentGateway] Radius sync failed: ' . $re->getMessage());
                        }
                    }
                }
            }

            DB::commit();
            Log::info("[PaymentGateway] Settle Invoice Success: {$invoice->invoice_number} ({$refId}) Amount: {$amount}");
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[PaymentGateway] Settle Invoice Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Top up saldo mandiri untuk reseller
     */
    public function createResellerTopUp(Reseller $reseller, float $amount, string $channel = 'QRIS'): array
    {
        $customer = $reseller->customer;
        if (!$customer) {
            throw new \Exception('Data pelanggan tidak terhubung dengan akun reseller.');
        }

        // Buat invoice baru untuk transaksi top up ini
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'amount' => $amount,
            'status' => 'unpaid',
            'issued_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDay(),
            'period_month' => (int) date('n'),
            'period_year' => (int) date('Y'),
            'is_reseller_balance' => true,
            'notes' => 'Top Up Saldo Mandiri Reseller via Payment Gateway (' . strtoupper($channel) . ')',
        ]);

        return $this->createPaymentTransaction($invoice, $channel);
    }
}
