<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditDisbursementService
{
    protected string $baseUrl = 'https://api.xendit.co';

    /**
     * Cek apakah fitur Xendit Auto Transfer diaktifkan.
     */
    public function isEnabled(): bool
    {
        return Setting::get('xendit_disbursement_enabled', '0') === '1';
    }

    /**
     * Dapatkan Secret API Key Xendit.
     */
    public function getApiKey(): ?string
    {
        return Setting::get('xendit_secret_key');
    }

    /**
     * Dapatkan Webhook Callback Token Xendit.
     */
    public function getWebhookToken(): ?string
    {
        return Setting::get('xendit_webhook_token');
    }

    /**
     * Dapatkan konfigurasi Xendit untuk dikirim ke frontend.
     */
    public function getConfig(): array
    {
        $key = $this->getApiKey();
        $isSet = !empty($key);
        $maskedKey = $isSet ? substr($key, 0, 8) . '...' . substr($key, -4) : '';
        $isLive = str_starts_with($key ?? '', 'xnd_production_');

        return [
            'enabled' => $this->isEnabled(),
            'has_api_key' => $isSet,
            'api_key_masked' => $maskedKey,
            'environment' => $isLive ? 'production' : 'sandbox',
            'webhook_token' => $this->getWebhookToken() ?? '',
            'auto_disburse' => Setting::get('xendit_auto_disburse_on_approve', '0') === '1',
        ];
    }

    /**
     * Test koneksi ke Xendit & cek saldo kas (Balance CASH).
     */
    public function testConnection(?string $apiKey = null): array
    {
        $key = $apiKey ?: $this->getApiKey();
        if (empty($key)) {
            return [
                'status' => 'error',
                'message' => 'Secret API Key Xendit belum diisi.',
            ];
        }

        try {
            $response = Http::withBasicAuth(trim($key), '')
                ->timeout(15)
                ->get("{$this->baseUrl}/balance", [
                    'account_type' => 'CASH'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $balance = $data['balance'] ?? 0;
                $isLive = str_starts_with($key, 'xnd_production_');
                $envLabel = $isLive ? 'Production (Live)' : 'Development (Sandbox)';

                return [
                    'status' => 'success',
                    'balance' => (float) $balance,
                    'environment' => $envLabel,
                    'message' => "Koneksi ke Xendit ({$envLabel}) BERHASIL! Saldo Kas Tersedia: Rp " . number_format($balance, 0, ',', '.'),
                ];
            }

            $errData = $response->json();
            $msg = $errData['message'] ?? $errData['error_code'] ?? 'Status HTTP: ' . $response->status();
            return [
                'status' => 'error',
                'message' => 'Autentikasi Xendit Gagal: ' . $msg,
            ];
        } catch (\Exception $e) {
            Log::error('[XenditDisbursement] Test connection exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalisasi nama bank menjadi kode bank standar Xendit.
     */
    public static function normalizeBankCode(?string $bank): string
    {
        if (empty($bank)) return 'BCA';
        $b = strtoupper(trim($bank));

        // Hapus kata 'BANK' / 'PT' jika ada
        $b = preg_replace('/^(BANK|PT)\s+/i', '', $b);

        $map = [
            'BCA' => 'BCA',
            'BCA SYARIAH' => 'BCA_SYR',
            'MANDIRI' => 'MANDIRI',
            'BNI' => 'BNI',
            'BNI SYARIAH' => 'BNI_SYR',
            'BRI' => 'BRI',
            'BRI SYARIAH' => 'BSI',
            'BSI' => 'BSI',
            'BANK SYARIAH INDONESIA' => 'BSI',
            'CIMB' => 'CIMB',
            'CIMB NIAGA' => 'CIMB',
            'PERMATA' => 'PERMATA',
            'DANAMON' => 'DANAMON',
            'BTPN' => 'BTPN',
            'JENIUS' => 'BTPN',
            'JAGO' => 'JAGO',
            'BANK JAGO' => 'JAGO',
            'SEABANK' => 'SEABANK',
            'BNC' => 'BNC',
            'BANK NEO' => 'BNC',
            'NEO' => 'BNC',
            'MAYBANK' => 'MAYBANK',
            'OCBC' => 'OCBC',
            'OCBC NISP' => 'OCBC',
            'BTN' => 'BTN',
            'PANIN' => 'PANIN',
            'MEGA' => 'MEGA',
            'SINARMAS' => 'SINARMAS',
            'DANA' => 'DANA',
            'OVO' => 'OVO',
            'GOPAY' => 'GOPAY',
            'SHOPEEPAY' => 'SHOPEEPAY',
            'LINKAJA' => 'LINKAJA',
        ];

        return $map[$b] ?? $b;
    }

    /**
     * Eksekusi transfer otomatis (Disbursement) ke rekening bank karyawan via Xendit API.
     */
    public function disbursePayroll(Payroll $payroll, User $user, float $amount, string $description = ''): array
    {
        $key = $this->getApiKey();
        if (empty($key)) {
            return [
                'status' => 'error',
                'message' => 'Xendit Secret API Key belum dikonfigurasi di Pengaturan Sistem.',
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

        $bankCode = self::normalizeBankCode($user->bank_name);
        $accountHolder = trim($user->bank_account_holder ?: $user->name);
        $cleanAmount = (int) round($amount);

        if ($cleanAmount <= 0) {
            return [
                'status' => 'error',
                'message' => 'Nominal gaji bersih harus lebih dari 0 untuk ditransfer.',
            ];
        }

        // Generate ID eksternal unik
        $externalId = "PAYROLL-{$payroll->id}-" . time();
        $desc = !empty($description) ? substr($description, 0, 100) : "Gaji {$user->name} Periode {$payroll->month}/{$payroll->year}";

        $payload = [
            'external_id' => $externalId,
            'amount' => $cleanAmount,
            'bank_code' => $bankCode,
            'account_holder_name' => $accountHolder,
            'account_number' => $cleanAccount,
            'description' => $desc,
        ];

        try {
            Log::info("[XenditDisbursement] Mengirim transfer untuk Payroll #{$payroll->id} ke {$bankCode} {$cleanAccount} an {$accountHolder} Sebesar: Rp {$cleanAmount}");

            $response = Http::withBasicAuth(trim($key), '')
                ->withHeaders([
                    'X-Idempotency-Key' => $externalId,
                ])
                ->timeout(30)
                ->post("{$this->baseUrl}/disbursements", $payload);

            $data = $response->json();

            if ($response->successful()) {
                $disbId = $data['id'] ?? null;
                $status = strtoupper($data['status'] ?? 'PENDING');

                $payroll->update([
                    'disbursement_channel' => 'xendit',
                    'disbursement_id' => $disbId,
                    'disbursement_reference' => $externalId,
                    'disbursement_status' => $status,
                    'disbursement_bank' => $bankCode,
                    'disbursement_account_number' => $cleanAccount,
                    'disbursement_error' => null,
                    'disbursement_payload' => json_encode($data),
                ]);

                Log::info("[XenditDisbursement] Sukses transfer Payroll #{$payroll->id}: Disb ID: {$disbId}, Status: {$status}");

                return [
                    'status' => 'success',
                    'disbursement_id' => $disbId,
                    'disbursement_status' => $status,
                    'message' => "Auto transfer via Xendit berhasil dikirim (Status: {$status}). Ref ID: {$externalId}",
                    'data' => $data,
                ];
            }

            // Gagal dari Xendit
            $errorCode = $data['error_code'] ?? 'UNKNOWN_ERROR';
            $errorMessage = $data['message'] ?? 'Gagal melakukan disbursement ke Xendit.';

            $friendlyMsg = $this->translateErrorCode($errorCode, $errorMessage);

            $payroll->update([
                'disbursement_channel' => 'xendit',
                'disbursement_reference' => $externalId,
                'disbursement_status' => 'FAILED',
                'disbursement_error' => $friendlyMsg,
                'disbursement_payload' => json_encode($data),
            ]);

            Log::warning("[XenditDisbursement] Gagal transfer Payroll #{$payroll->id}: {$errorCode} - {$errorMessage}");

            return [
                'status' => 'error',
                'error_code' => $errorCode,
                'message' => "Xendit Gagal: {$friendlyMsg}",
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error("[XenditDisbursement] Exception transfer Payroll #{$payroll->id}: " . $e->getMessage());

            $payroll->update([
                'disbursement_channel' => 'xendit',
                'disbursement_status' => 'FAILED',
                'disbursement_error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghubungi Xendit: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Terjemahkan kode error Xendit ke bahasa yang mudah dipahami admin.
     */
    protected function translateErrorCode(string $code, string $default): string
    {
        return match ($code) {
            'INSUFFICIENT_BALANCE' => 'Saldo Kas Xendit tidak mencukupi untuk melakukan transfer ini. Harap top-up saldo Xendit terlebih dahulu.',
            'INVALID_DESTINATION_ACCOUNT', 'ACCOUNT_NUMBER_INVALID' => 'Nomor rekening bank tujuan tidak valid atau tidak terdaftar.',
            'BANK_CODE_NOT_SUPPORTED' => 'Kode bank tidak didukung oleh Xendit.',
            'DUPLICATE_EXTERNAL_ID' => 'Transaksi ganda terdeteksi (Idempotency error).',
            'DISBURSEMENT_LIMIT_EXCEEDED' => 'Melebihi limit transaksi harian disbursement Xendit.',
            default => $default,
        };
    }
}
