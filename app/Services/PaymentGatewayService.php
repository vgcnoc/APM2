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
                'description' => 'Transfer via myBCA, BCA Mobile, KlikBCA, ATM BCA',
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
                'description' => 'Transfer via BNI Mobile Banking, ATM BNI, SMS Banking',
            ],
            [
                'code' => 'MANDIRIVA',
                'name' => 'Mandiri Virtual Account (Livin)',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via Livin by Mandiri, ATM Mandiri',
            ],
            [
                'code' => 'PERMATAVA',
                'name' => 'Permata Virtual Account',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via PermataMobile X, ATM Permata, Prima/Bersama',
            ],
            [
                'code' => 'CIMBVA',
                'name' => 'CIMB Niaga Virtual Account',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via OCTO Mobile, OCTO Clicks, ATM CIMB',
            ],
            [
                'code' => 'BSIVA',
                'name' => 'BSI Virtual Account',
                'group' => 'Virtual Account',
                'icon' => 'bank',
                'description' => 'Transfer via BSI Mobile, ATM BSI, Bank Syariah Indonesia',
            ],
            [
                'code' => 'ALFAMART',
                'name' => 'Gerai Alfamart / Alfamidi',
                'group' => 'Convenience Store',
                'icon' => 'store',
                'description' => 'Bayar tunai di kasir Alfamart, Alfamidi, atau Dan+Dan terdekat',
            ],
            [
                'code' => 'INDOMARET',
                'name' => 'Gerai Indomaret / Ceriamart',
                'group' => 'Convenience Store',
                'icon' => 'store',
                'description' => 'Bayar tunai di kasir Indomaret atau Ceriamart terdekat',
            ],
        ];
    }

    /**
     * Buat transaksi pembayaran untuk invoice tertentu
     */
    public function createPaymentTransaction(Invoice $invoice, string $channel = 'QRIS'): array
    {
        $config = $this->getConfig();
        $referenceId = 'PG-' . date('YmdHis') . '-' . $invoice->id;

        // Pilih provider yang aktif
        switch ($config['provider']) {
            case 'tripay':
                if (!empty($config['api_key']) && !empty($config['private_key'])) {
                    return $this->createTripayTransaction($invoice, $channel, $referenceId, $config);
                }
                break;

            case 'midtrans':
                if (!empty($config['api_key'])) {
                    return $this->createMidtransTransaction($invoice, $channel, $referenceId, $config);
                }
                break;

            case 'xendit':
                if (!empty($config['api_key'])) {
                    return $this->createXenditTransaction($invoice, $channel, $referenceId, $config);
                }
                break;

            case 'duitku':
                if (!empty($config['api_key']) && !empty($config['merchant_id'])) {
                    return $this->createDuitkuTransaction($invoice, $channel, $referenceId, $config);
                }
                break;

            case 'ipaymu':
                if (!empty($config['api_key']) && !empty($config['merchant_id'])) {
                    return $this->createIpaymuTransaction($invoice, $channel, $referenceId, $config);
                }
                break;

            case 'paydisini':
                if (!empty($config['api_key'])) {
                    return $this->createPaydisiniTransaction($invoice, $channel, $referenceId, $config);
                }
                break;

            case 'mayar':
                if (!empty($config['api_key'])) {
                    return $this->createMayarTransaction($invoice, $channel, $referenceId, $config);
                }
                break;
        }

        // Fallback / Built-in Automatic Gateway Simulation (Sandbox Cerdas)
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
            'return_url'     => url('/payment-gateway/pay/' . $invoice->id),
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

        $serverKey = $config['api_key'];
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
     * Buat transaksi via Xendit Invoices API
     */
    protected function createXenditTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $endpoint = 'https://api.xendit.co/v2/invoices';
        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $customer = $invoice->customer;
        $externalId = $invoice->invoice_number . '-' . time();

        $payload = [
            'external_id' => $externalId,
            'amount' => $amount,
            'payer_email' => ($customer && $customer->email) ? $customer->email : 'billing@apm.net',
            'description' => $invoice->notes ?: ('Pembayaran ' . $invoice->invoice_number),
            'invoice_duration' => 86400,
            'customer' => [
                'given_names' => $customer ? $customer->name : 'Pelanggan APM',
                'mobile_number' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            ],
            'success_redirect_url' => url('/payment-gateway/pay/' . $invoice->id),
            'failure_redirect_url' => url('/payment-gateway/pay/' . $invoice->id),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($config['api_key'] . ':'),
                'Content-Type' => 'application/json'
            ])->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $invoice->update([
                    'payment_ref' => $data['id'] ?? $externalId,
                    'payment_url' => $data['invoice_url'] ?? null,
                    'payment_channel' => $channel,
                    'payment_gateway_payload' => json_encode($data)
                ]);

                return [
                    'success' => true,
                    'provider' => 'xendit',
                    'reference' => $data['id'] ?? $externalId,
                    'merchant_ref' => $externalId,
                    'checkout_url' => $data['invoice_url'] ?? null,
                    'amount' => $amount,
                    'instructions' => [
                        'Klik tombol Buka Portal Pembayaran Xendit.',
                        'Pilih metode pembayaran (QRIS, VA Bank, E-Wallet, atau Gerai Retail).',
                        'Selesaikan pembayaran sesuai instruksi di halaman Xendit.'
                    ],
                ];
            }
            Log::warning('[PaymentGateway] Xendit create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] Xendit Exception: ' . $e->getMessage());
        }

        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat transaksi via Duitku API
     */
    protected function createDuitkuTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $isProduction = $config['environment'] === 'production';
        $endpoint = $isProduction 
            ? 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry'
            : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';

        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $customer = $invoice->customer;
        $merchantOrderId = $invoice->invoice_number . '-' . time();
        $signature = md5($config['merchant_id'] . $merchantOrderId . $amount . $config['api_key']);

        $channelMap = [
            'QRIS' => 'SP',
            'BCAVA' => 'BC',
            'BRIVA' => 'BR',
            'BNIVA' => 'I1',
            'MANDIRIVA' => 'M2',
            'PERMATAVA' => 'BT',
            'CIMBVA' => 'B1',
            'ALFAMART' => 'FT',
            'INDOMARET' => 'IR',
        ];
        $duitkuMethod = $channelMap[$channel] ?? 'SP';

        $payload = [
            'merchantCode' => $config['merchant_id'],
            'paymentAmount' => $amount,
            'paymentMethod' => $duitkuMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $invoice->notes ?: ('Pembayaran ' . $invoice->invoice_number),
            'email' => ($customer && $customer->email) ? $customer->email : 'billing@apm.net',
            'phoneNumber' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            'callbackUrl' => url('/api/payment-gateway/callback'),
            'returnUrl' => url('/payment-gateway/pay/' . $invoice->id),
            'signature' => $signature,
            'expiryPeriod' => 1440,
        ];

        try {
            $response = Http::timeout(15)->post($endpoint, $payload);
            if ($response->successful()) {
                $data = $response->json();
                if (($data['statusCode'] ?? '') === '00') {
                    $invoice->update([
                        'payment_ref' => $data['reference'] ?? $merchantOrderId,
                        'payment_url' => $data['paymentUrl'] ?? null,
                        'payment_channel' => $channel,
                        'payment_gateway_payload' => json_encode($data)
                    ]);

                    return [
                        'success' => true,
                        'provider' => 'duitku',
                        'reference' => $data['reference'] ?? $merchantOrderId,
                        'merchant_ref' => $merchantOrderId,
                        'pay_code' => $data['vaNumber'] ?? null,
                        'qr_string' => $data['qrString'] ?? null,
                        'checkout_url' => $data['paymentUrl'] ?? null,
                        'amount' => $amount,
                        'instructions' => [],
                    ];
                }
            }
            Log::warning('[PaymentGateway] Duitku create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] Duitku Exception: ' . $e->getMessage());
        }

        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat transaksi via iPaymu Direct API
     */
    protected function createIpaymuTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $isProduction = $config['environment'] === 'production';
        $endpoint = $isProduction 
            ? 'https://my.ipaymu.com/api/v2/payment/direct'
            : 'https://sandbox.ipaymu.com/api/v2/payment/direct';

        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $customer = $invoice->customer;

        $method = 'qris';
        $ipayChannel = 'mpm';
        if (str_ends_with($channel, 'VA')) {
            $method = 'va';
            $ipayChannel = strtolower(str_replace('VA', '', $channel));
        } elseif ($channel === 'ALFAMART') {
            $method = 'cstore';
            $ipayChannel = 'alfamart';
        } elseif ($channel === 'INDOMARET') {
            $method = 'cstore';
            $ipayChannel = 'indomaret';
        }

        $payload = [
            'name' => $customer ? $customer->name : 'Pelanggan APM',
            'phone' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            'email' => ($customer && $customer->email) ? $customer->email : 'billing@apm.net',
            'amount' => $amount,
            'notifyUrl' => url('/api/payment-gateway/callback'),
            'uredirect' => url('/payment-gateway/pay/' . $invoice->id),
            'comments' => $invoice->notes ?: ('Pembayaran ' . $invoice->invoice_number),
            'paymentMethod' => $method,
            'paymentChannel' => $ipayChannel,
        ];

        try {
            $body = json_encode($payload);
            $stringToSign = "POST:" . $config['merchant_id'] . ":" . strtolower(hash('sha256', $body)) . ":" . $config['api_key'];
            $signature = hash_hmac('sha256', $stringToSign, $config['api_key']);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'signature' => $signature,
                'va' => $config['merchant_id'],
                'timestamp' => date('YmdHis'),
            ])->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $res = $response->json();
                $data = $res['Data'] ?? [];
                if (($res['Status'] ?? 0) === 200 && !empty($data)) {
                    $invoice->update([
                        'payment_ref' => (string) ($data['TransactionId'] ?? $data['SessionID'] ?? $referenceId),
                        'payment_url' => $data['Url'] ?? null,
                        'payment_channel' => $channel,
                        'payment_gateway_payload' => json_encode($data)
                    ]);

                    return [
                        'success' => true,
                        'provider' => 'ipaymu',
                        'reference' => (string) ($data['TransactionId'] ?? $data['SessionID'] ?? $referenceId),
                        'pay_code' => $data['PaymentNo'] ?? null,
                        'qr_string' => $data['QrString'] ?? null,
                        'checkout_url' => $data['Url'] ?? null,
                        'amount' => $amount,
                        'instructions' => [],
                    ];
                }
            }
            Log::warning('[PaymentGateway] iPaymu create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] iPaymu Exception: ' . $e->getMessage());
        }

        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat transaksi via Paydisini API
     */
    protected function createPaydisiniTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $endpoint = 'https://api.paydisini.co.id/v1/';
        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $customer = $invoice->customer;
        $uniqueCode = $invoice->invoice_number . '-' . time();

        $serviceMap = [
            'QRIS' => '11',
            'BCAVA' => '1',
            'BRIVA' => '2',
            'BNIVA' => '3',
            'MANDIRIVA' => '4',
            'ALFAMART' => '7',
            'INDOMARET' => '8',
        ];
        $service = $serviceMap[$channel] ?? '11';
        $validTime = 1800; // 30 menit
        $signature = md5($config['api_key'] . $uniqueCode . $service . $amount . $validTime . 'NewTransaction');

        $payload = [
            'key' => $config['api_key'],
            'request' => 'new',
            'unique_code' => $uniqueCode,
            'service' => $service,
            'amount' => $amount,
            'note' => $invoice->notes ?: ('Pembayaran ' . $invoice->invoice_number),
            'valid_time' => $validTime,
            'ewallet_phone' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            'signature' => $signature,
            'return_url' => url('/payment-gateway/pay/' . $invoice->id),
        ];

        try {
            $response = Http::asForm()->timeout(15)->post($endpoint, $payload);
            if ($response->successful()) {
                $res = $response->json();
                if (($res['success'] ?? false) === true) {
                    $data = $res['data'] ?? [];
                    $invoice->update([
                        'payment_ref' => (string) ($data['pay_id'] ?? $uniqueCode),
                        'payment_url' => $data['checkout_url'] ?? null,
                        'payment_channel' => $channel,
                        'payment_gateway_payload' => json_encode($data)
                    ]);

                    return [
                        'success' => true,
                        'provider' => 'paydisini',
                        'reference' => (string) ($data['pay_id'] ?? $uniqueCode),
                        'merchant_ref' => $uniqueCode,
                        'pay_code' => $data['virtual_account'] ?? null,
                        'qr_string' => $data['qr_content'] ?? null,
                        'checkout_url' => $data['checkout_url'] ?? null,
                        'amount' => $amount,
                        'instructions' => [],
                    ];
                }
            }
            Log::warning('[PaymentGateway] Paydisini create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] Paydisini Exception: ' . $e->getMessage());
        }

        return $this->createSimulatedTransaction($invoice, $channel, $referenceId);
    }

    /**
     * Buat transaksi via Mayar API
     */
    protected function createMayarTransaction(Invoice $invoice, string $channel, string $referenceId, array $config): array
    {
        $endpoint = 'https://api.mayar.id/hl/v1/payment/create';
        $amount = (int) round($invoice->remaining > 0 ? $invoice->remaining : $invoice->amount);
        $customer = $invoice->customer;

        $payload = [
            'name' => $customer ? $customer->name : 'Pelanggan APM',
            'email' => ($customer && $customer->email) ? $customer->email : 'billing@apm.net',
            'mobile' => ($customer && $customer->phone) ? $customer->phone : '081234567890',
            'amount' => $amount,
            'description' => $invoice->notes ?: ('Pembayaran ' . $invoice->invoice_number),
            'redirectUrl' => url('/payment-gateway/pay/' . $invoice->id),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json'
            ])->timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                $res = $response->json();
                $data = $res['data'] ?? [];
                if (!empty($data['link'])) {
                    $invoice->update([
                        'payment_ref' => (string) ($data['id'] ?? $referenceId),
                        'payment_url' => $data['link'] ?? null,
                        'payment_channel' => $channel,
                        'payment_gateway_payload' => json_encode($data)
                    ]);

                    return [
                        'success' => true,
                        'provider' => 'mayar',
                        'reference' => (string) ($data['id'] ?? $referenceId),
                        'checkout_url' => $data['link'] ?? null,
                        'amount' => $amount,
                        'instructions' => [
                            'Klik tombol Buka Portal Pembayaran Mayar.',
                            'Pilih metode pembayaran yang tersedia di Mayar.',
                            'Selesaikan pembayaran sebelum batas waktu berakhir.'
                        ],
                    ];
                }
            }
            Log::warning('[PaymentGateway] Mayar create failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('[PaymentGateway] Mayar Exception: ' . $e->getMessage());
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
            'PERMATAVA' => '8528',
            'CIMBVA' => '2020',
            'BSIVA' => '9000',
            'ALFAMART' => '1100',
            'INDOMARET' => '2200',
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
                'Pilih menu ' . ($channel === 'QRIS' ? 'Scan QRIS' : (in_array($channel, ['ALFAMART', 'INDOMARET']) ? 'Pembayaran Merchant di Kasir' : 'Transfer Virtual Account')) . '.',
                $channel === 'QRIS' ? 'Arahkan kamera ke kode QR di atas.' : 'Masukkan kode pembayaran: ' . $payCode,
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
     * Dapat dipanggil dari Webhook resmi Tripay/Midtrans/Xendit/Duitku/iPaymu/Paydisini/Mayar atau Simulator
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
