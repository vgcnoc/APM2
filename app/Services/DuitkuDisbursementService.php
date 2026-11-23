<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuitkuDisbursementService
{
    /**
     * Dapatkan URL dasar Duitku Disbursement berdasarkan Environment (Sandbox / Production).
     */
    public function getBaseUrl(?string $env = null): string
    {
        $environment = $env ?: $this->getEnvironment();
        return $environment === 'production'
            ? 'https://passport.duitku.com/webapi/api/disbursement'
            : 'https://sandbox.duitku.com/webapi/api/disbursement';
    }

    /**
     * Cek apakah fitur Duitku Auto Transfer diaktifkan.
     */
    public function isEnabled(): bool
    {
        return Setting::get('duitku_disbursement_enabled', '0') === '1';
    }

    /**
     * Dapatkan Merchant Code Duitku.
     */
    public function getMerchantCode(): ?string
    {
        return Setting::get('duitku_merchant_code', Setting::get('duitku_merchant_id'));
    }

    /**
     * Dapatkan API Key / Secret Key Duitku.
     */
    public function getApiKey(): ?string
    {
        return Setting::get('duitku_disbursement_api_key', Setting::get('duitku_api_key'));
    }

    /**
     * Dapatkan Environment (sandbox / production).
     */
    public function getEnvironment(): string
    {
        return Setting::get('duitku_disbursement_environment', Setting::get('duitku_environment', 'sandbox'));
    }

    /**
     * Dapatkan konfigurasi Duitku untuk dikirim ke frontend.
     */
    public function getConfig(): array
    {
        $merchantCode = $this->getMerchantCode();
        $apiKey = $this->getApiKey();
        $isKeySet = !empty($apiKey);
        $maskedKey = $isKeySet ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : '';
        $env = $this->getEnvironment();

        return [
            'enabled' => $this->isEnabled(),
            'merchant_code' => $merchantCode ?? '',
            'has_merchant_code' => !empty($merchantCode),
            'has_api_key' => $isKeySet,
            'api_key_masked' => $maskedKey,
            'environment' => $env,
            'auto_disburse' => Setting::get('duitku_auto_disburse_on_approve', '0') === '1',
        ];
    }

    /**
     * Test koneksi ke Duitku & cek saldo kas (Balance).
     */
    public function testConnection(?string $merchantCode = null, ?string $apiKey = null, ?string $environment = null): array
    {
        $mCode = $merchantCode ?: $this->getMerchantCode();
        $key = $apiKey ?: $this->getApiKey();
        $env = $environment ?: $this->getEnvironment();

        if (empty($mCode)) {
            return [
                'status' => 'error',
                'message' => 'Merchant Code Duitku belum diisi.',
            ];
        }

        if (empty($key)) {
            return [
                'status' => 'error',
                'message' => 'API Key Duitku belum diisi.',
            ];
        }

        $baseUrl = $this->getBaseUrl($env);
        $envLabel = $env === 'production' ? 'Production (Live)' : 'Development (Sandbox)';
        $timestamp = (string) round(microtime(true) * 1000);
        
        // Duitku signature formula: sha256(merchantCode + timestamp + apiKey)
        $signature = hash('sha256', $mCode . $timestamp . $key);

        try {
            $response = Http::asJson()
                ->timeout(15)
                ->post("{$baseUrl}/checkbalance", [
                    'merchantCode' => $mCode,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $respCode = (string) ($data['responseCode'] ?? $data['response_code'] ?? $data['status'] ?? '');
                
                // 00 = Sukses di Duitku
                if ($respCode === '00' || isset($data['balance']) || isset($data['balanceAvailable'])) {
                    $balance = (float) ($data['balanceAvailable'] ?? $data['balance'] ?? 0);
                    return [
                        'status' => 'success',
                        'balance' => $balance,
                        'environment' => $envLabel,
                        'message' => "Koneksi ke Duitku Disbursement ({$envLabel}) BERHASIL! Saldo Kas Tersedia: Rp " . number_format($balance, 0, ',', '.'),
                    ];
                }

                $msg = $data['responseDesc'] ?? $data['responseMessage'] ?? $data['message'] ?? 'Respon tidak valid';
                return [
                    'status' => 'error',
                    'message' => "Duitku Menolak ({$respCode}): {$msg}",
                ];
            }

            // Fallback jika endpoint balance berbeda di sandbox atau butuh otentikasi header
            $errData = $response->json() ?: [];
            $msg = $errData['responseDesc'] ?? $errData['message'] ?? ('HTTP status ' . $response->status());

            return [
                'status' => 'error',
                'message' => 'Autentikasi Duitku Gagal: ' . $msg,
            ];
        } catch (\Exception $e) {
            Log::error('[DuitkuDisbursement] Test connection exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Koneksi ke Duitku gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalisasi nama bank menjadi kode kliring / kode bank standar Duitku (3 digit kode bank Indonesia atau huruf besar).
     */
    public static function normalizeBankCode(?string $bank): string
    {
        if (empty($bank)) return '014'; // Default BCA
        $b = strtolower(trim($bank));

        // Hapus kata 'bank' / 'pt' jika ada
        $b = preg_replace('/^(bank|pt)\s+/i', '', $b);

        $map = [
            'bca' => '014',
            'bca syariah' => '536',
            'mandiri' => '008',
            'bni' => '009',
            'bni syariah' => '451',
            'bri' => '002',
            'bri syariah' => '451',
            'bsi' => '451',
            'bank syariah indonesia' => '451',
            'cimb' => '022',
            'cimb niaga' => '022',
            'permata' => '013',
            'danamon' => '011',
            'btpn' => '213',
            'jenius' => '213',
            'jago' => '542',
            'bank jago' => '542',
            'seabank' => '535',
            'bnc' => '490',
            'bank neo' => '490',
            'neo' => '490',
            'maybank' => '016',
            'ocbc' => '028',
            'ocbc nisp' => '028',
            'btn' => '200',
            'panin' => '019',
            'mega' => '426',
            'sinarmas' => '153',
            'dana' => 'DANA',
            'ovo' => 'OVO',
            'gopay' => 'GOPAY',
            'shopeepay' => 'SHOPEEPAY',
            'linkaja' => 'LINKAJA',
        ];

        return $map[$b] ?? strtoupper($b);
    }

    /**
     * Eksekusi transfer otomatis (Disbursement) ke rekening bank karyawan via Duitku API.
     */
    public function disbursePayroll(Payroll $payroll, User $user, float $amount, string $description = ''): array
    {
        $mCode = $this->getMerchantCode();
        $apiKey = $this->getApiKey();

        if (empty($mCode) || empty($apiKey)) {
            return [
                'status' => 'error',
                'message' => 'Kredensial Duitku Disbursement (Merchant Code / API Key) belum dikonfigurasi di Pengaturan Sistem.',
            ];
        }

        if (empty($user->bank_account_number)) {
            return [
                'status' => 'error',
                'message' => "Nomor rekening untuk {$user->name} belum diatur. Atur rekening terlebih dahulu.",
            ];
        }

        $cleanAccount = preg_replace('/[^0-9]/', '', $user->bank_account_number);
        if (empty($cleanAccount)) {
            return [
                'status' => 'error',
                'message' => "Format nomor rekening '{$user->bank_account_number}' tidak valid.",
            ];
        }

        $cleanAmount = (int) round($amount);
        if ($cleanAmount <= 0) {
            return [
                'status' => 'error',
                'message' => 'Nominal gaji bersih harus lebih dari 0 untuk ditransfer.',
            ];
        }

        $bankCode = self::normalizeBankCode($user->bank_name);
        $accountHolder = !empty($user->bank_account_holder) ? $user->bank_account_holder : $user->name;
        $externalId = "DUITKU-PAY-{$payroll->id}-" . time();
        $safeRemark = !empty($description) ? substr($description, 0, 50) : "Gaji {$payroll->month}/{$payroll->year}";
        $timestamp = (string) round(microtime(true) * 1000);

        // Duitku signature for transfer: sha256(merchantCode + custRefId + bankCode + bankAccount + amount + timestamp + apiKey)
        $signature = hash('sha256', $mCode . $externalId . $bankCode . $cleanAccount . $cleanAmount . $timestamp . $apiKey);

        $payload = [
            'merchantCode' => $mCode,
            'custRefId' => $externalId,
            'bankCode' => $bankCode,
            'bankAccount' => $cleanAccount,
            'accountName' => $accountHolder,
            'amount' => $cleanAmount,
            'purpose' => $safeRemark,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $baseUrl = $this->getBaseUrl();

        try {
            Log::info("[DuitkuDisbursement] Mengirim transfer Payroll #{$payroll->id} ke {$bankCode} {$cleanAccount} Sebesar: Rp {$cleanAmount} (URL: {$baseUrl})");

            $response = Http::asJson()
                ->timeout(35)
                ->post("{$baseUrl}/transfer", $payload);

            $data = $response->json() ?: [];
            $respCode = (string) ($data['responseCode'] ?? $data['response_code'] ?? '');

            if ($response->successful() && ($respCode === '00' || $respCode === '01' || empty($respCode))) {
                $disbId = (string) ($data['disburseId'] ?? $data['id'] ?? $externalId);
                $rawStatus = strtoupper($data['status'] ?? ($respCode === '00' ? 'COMPLETED' : 'PENDING'));
                $mappedStatus = in_array($rawStatus, ['SUCCESS', 'DONE', 'COMPLETED']) ? 'COMPLETED' : $rawStatus;

                $payroll->update([
                    'disbursement_channel' => 'duitku',
                    'disbursement_id' => $disbId,
                    'disbursement_reference' => $externalId,
                    'disbursement_status' => $mappedStatus,
                    'disbursement_bank' => $bankCode,
                    'disbursement_account_number' => $cleanAccount,
                    'disbursement_error' => null,
                    'disbursement_payload' => json_encode($data),
                ]);

                Log::info("[DuitkuDisbursement] Sukses transfer Payroll #{$payroll->id}: Disb ID: {$disbId}, Status: {$mappedStatus}");

                return [
                    'status' => 'success',
                    'disbursement_id' => $disbId,
                    'disbursement_status' => $mappedStatus,
                    'message' => "Auto transfer via Duitku berhasil dikirim (Status: {$mappedStatus}). Ref: {$disbId}",
                    'data' => $data,
                ];
            }

            // Gagal dari Duitku
            $errorDesc = $data['responseDesc'] ?? $data['responseMessage'] ?? $data['message'] ?? 'Gagal melakukan transfer ke Duitku.';
            $friendlyMsg = $this->translateError($errorDesc);

            $payroll->update([
                'disbursement_channel' => 'duitku',
                'disbursement_reference' => $externalId,
                'disbursement_status' => 'FAILED',
                'disbursement_error' => $friendlyMsg,
                'disbursement_payload' => json_encode($data),
            ]);

            Log::warning("[DuitkuDisbursement] Gagal transfer Payroll #{$payroll->id}: {$errorDesc} (Code: {$respCode})");

            return [
                'status' => 'error',
                'message' => "Duitku Gagal: {$friendlyMsg}",
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error("[DuitkuDisbursement] Exception transfer Payroll #{$payroll->id}: " . $e->getMessage());

            $payroll->update([
                'disbursement_channel' => 'duitku',
                'disbursement_reference' => $externalId,
                'disbursement_status' => 'FAILED',
                'disbursement_error' => 'Koneksi Duitku terputus / timeout: ' . $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Gagal terhubung ke server Duitku: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Terjemahkan pesan error Duitku ke bahasa Indonesia yang ramah pengguna.
     */
    protected function translateError(string $raw): string
    {
        $low = strtolower($raw);
        if (str_contains($low, 'insufficient') || str_contains($low, 'saldo')) {
            return 'Saldo Kas Duitku tidak mencukupi untuk melakukan transfer ini.';
        }
        if (str_contains($low, 'account not found') || str_contains($low, 'invalid account') || str_contains($low, 'nomor rekening')) {
            return 'Nomor rekening atau kode bank tidak ditemukan / tidak valid di bank tujuan.';
        }
        if (str_contains($low, 'signature')) {
            return 'Tanda tangan API (Signature) Duitku tidak valid. Periksa kembali Merchant Code & API Key.';
        }
        if (str_contains($low, 'duplicate') || str_contains($low, 'already exists')) {
            return 'Transaksi dengan ID ini sudah pernah diproses sebelumnya.';
        }
        if (str_contains($low, 'limit')) {
            return 'Melebihi batas limit harian transfer Duitku.';
        }
        return $raw;
    }
}

