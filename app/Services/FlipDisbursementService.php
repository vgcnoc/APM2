<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlipDisbursementService
{
    /**
     * Dapatkan URL dasar Flip berdasarkan Environment (Sandbox / Production).
     */
    public function getBaseUrl(?string $env = null): string
    {
        $environment = $env ?: $this->getEnvironment();
        return $environment === 'production' 
            ? 'https://bigflip.id/api' 
            : 'https://bigflip.id/big_sandbox_api';
    }

    /**
     * Cek apakah fitur Flip Auto Transfer diaktifkan.
     */
    public function isEnabled(): bool
    {
        return Setting::get('flip_disbursement_enabled', '0') === '1';
    }

    /**
     * Dapatkan Secret API Key Flip.
     */
    public function getSecretKey(): ?string
    {
        return Setting::get('flip_secret_key');
    }

    /**
     * Dapatkan Webhook Validation Token Flip.
     */
    public function getValidationToken(): ?string
    {
        return Setting::get('flip_validation_token');
    }

    /**
     * Dapatkan Environment (sandbox / production).
     */
    public function getEnvironment(): string
    {
        return Setting::get('flip_environment', 'sandbox');
    }

    /**
     * Dapatkan konfigurasi Flip untuk dikirim ke frontend.
     */
    public function getConfig(): array
    {
        $key = $this->getSecretKey();
        $isSet = !empty($key);
        $maskedKey = $isSet ? substr($key, 0, 6) . '...' . substr($key, -4) : '';
        $env = $this->getEnvironment();

        return [
            'enabled' => $this->isEnabled(),
            'has_secret_key' => $isSet,
            'secret_key_masked' => $maskedKey,
            'environment' => $env,
            'validation_token' => $this->getValidationToken() ?? '',
            'auto_disburse' => Setting::get('flip_auto_disburse_on_approve', '0') === '1',
        ];
    }

    /**
     * Test koneksi ke Flip & cek saldo kas (Balance).
     */
    public function testConnection(?string $secretKey = null, ?string $environment = null): array
    {
        $key = $secretKey ?: $this->getSecretKey();
        if (empty($key)) {
            return [
                'status' => 'error',
                'message' => 'Secret API Key Flip belum diisi.',
            ];
        }

        $env = $environment ?: $this->getEnvironment();
        $baseUrl = $this->getBaseUrl($env);
        $envLabel = $env === 'production' ? 'Production (Live)' : 'Development (Sandbox)';

        try {
            $response = Http::withBasicAuth(trim($key), '')
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout(15)
                ->get("{$baseUrl}/v2/general/balance");

            if ($response->successful()) {
                $data = $response->json();
                $balance = $data['balance'] ?? 0;

                return [
                    'status' => 'success',
                    'balance' => (float) $balance,
                    'environment' => $envLabel,
                    'message' => "Koneksi ke Flip ({$envLabel}) BERHASIL! Saldo Kas Tersedia: Rp " . number_format($balance, 0, ',', '.'),
                ];
            }

            $errData = $response->json();
            $msg = $errData['message'] ?? $errData['error'] ?? ('Status HTTP: ' . $response->status());
            if (is_array($msg)) {
                $msg = implode(', ', $msg);
            }

            return [
                'status' => 'error',
                'message' => 'Autentikasi Flip Gagal: ' . $msg,
            ];
        } catch (\Exception $e) {
            Log::error('[FlipDisbursement] Test connection exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalisasi nama bank menjadi kode bank standar Flip (huruf kecil).
     */
    public static function normalizeBankCode(?string $bank): string
    {
        if (empty($bank)) return 'bca';
        $b = strtolower(trim($bank));

        // Hapus kata 'bank' / 'pt' jika ada
        $b = preg_replace('/^(bank|pt)\s+/i', '', $b);

        $map = [
            'bca' => 'bca',
            'bca syariah' => 'bca_syr',
            'mandiri' => 'mandiri',
            'bni' => 'bni',
            'bni syariah' => 'bni_syr',
            'bri' => 'bri',
            'bri syariah' => 'bsi',
            'bsi' => 'bsi',
            'bank syariah indonesia' => 'bsi',
            'cimb' => 'cimb',
            'cimb niaga' => 'cimb',
            'permata' => 'permata',
            'danamon' => 'danamon',
            'btpn' => 'btpn',
            'jenius' => 'btpn',
            'jago' => 'jago',
            'bank jago' => 'jago',
            'seabank' => 'seabank',
            'bnc' => 'neo',
            'bank neo' => 'neo',
            'neo' => 'neo',
            'maybank' => 'maybank',
            'ocbc' => 'ocbc',
            'ocbc nisp' => 'ocbc',
            'btn' => 'btn',
            'panin' => 'panin',
            'mega' => 'mega',
            'sinarmas' => 'sinarmas',
            'dana' => 'dana',
            'ovo' => 'ovo',
            'gopay' => 'gopay',
            'shopeepay' => 'shopeepay',
            'linkaja' => 'linkaja',
        ];

        return $map[$b] ?? $b;
    }

    /**
     * Eksekusi transfer otomatis (Disbursement) ke rekening bank karyawan via Flip for Business API.
     */
    public function disbursePayroll(Payroll $payroll, User $user, float $amount, string $description = ''): array
    {
        $key = $this->getSecretKey();
        if (empty($key)) {
            return [
                'status' => 'error',
                'message' => 'Flip for Business Secret API Key belum dikonfigurasi di Pengaturan Sistem.',
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
        $cleanAmount = (int) round($amount);

        if ($cleanAmount <= 0) {
            return [
                'status' => 'error',
                'message' => 'Nominal gaji bersih harus lebih dari 0 untuk ditransfer.',
            ];
        }

        // Generate Idempotency Key unik
        $externalId = "FLIP-PAY-{$payroll->id}-" . time();
        $safeRemark = !empty($description) ? substr($description, 0, 18) : "Gaji {$payroll->month}/{$payroll->year}";

        $payload = [
            'account_number' => $cleanAccount,
            'bank_code' => $bankCode,
            'amount' => $cleanAmount,
            'remark' => $safeRemark,
        ];

        $baseUrl = $this->getBaseUrl();

        try {
            Log::info("[FlipDisbursement] Mengirim transfer Payroll #{$payroll->id} ke {$bankCode} {$cleanAccount} Sebesar: Rp {$cleanAmount} (URL: {$baseUrl})");

            $response = Http::asForm()
                ->withBasicAuth(trim($key), '')
                ->withHeaders([
                    'idempotency-key' => $externalId,
                    'Accept' => 'application/json',
                ])
                ->timeout(30)
                ->post("{$baseUrl}/v2/disbursement", $payload);

            $data = $response->json();

            if ($response->successful()) {
                $disbId = (string) ($data['id'] ?? '');
                $rawStatus = strtoupper($data['status'] ?? 'PENDING');
                $mappedStatus = ($rawStatus === 'DONE') ? 'COMPLETED' : $rawStatus;

                $payroll->update([
                    'disbursement_channel' => 'flip',
                    'disbursement_id' => $disbId,
                    'disbursement_reference' => $externalId,
                    'disbursement_status' => $mappedStatus,
                    'disbursement_bank' => $bankCode,
                    'disbursement_account_number' => $cleanAccount,
                    'disbursement_error' => null,
                    'disbursement_payload' => json_encode($data),
                ]);

                Log::info("[FlipDisbursement] Sukses transfer Payroll #{$payroll->id}: Disb ID: {$disbId}, Status: {$mappedStatus}");

                return [
                    'status' => 'success',
                    'disbursement_id' => $disbId,
                    'disbursement_status' => $mappedStatus,
                    'message' => "Auto transfer via Flip berhasil dikirim (Status: {$mappedStatus}). Flip ID: {$disbId}",
                    'data' => $data,
                ];
            }

            // Gagal dari Flip
            $errorMessage = $data['message'] ?? $data['error'] ?? 'Gagal melakukan disbursement ke Flip.';
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }

            $friendlyMsg = $this->translateError($errorMessage);

            $payroll->update([
                'disbursement_channel' => 'flip',
                'disbursement_reference' => $externalId,
                'disbursement_status' => 'FAILED',
                'disbursement_error' => $friendlyMsg,
                'disbursement_payload' => json_encode($data),
            ]);

            Log::warning("[FlipDisbursement] Gagal transfer Payroll #{$payroll->id}: {$errorMessage}");

            return [
                'status' => 'error',
                'message' => "Flip Gagal: {$friendlyMsg}",
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error("[FlipDisbursement] Exception transfer Payroll #{$payroll->id}: " . $e->getMessage());

            $payroll->update([
                'disbursement_channel' => 'flip',
                'disbursement_status' => 'FAILED',
                'disbursement_error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghubungi Flip: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Terjemahkan pesan error Flip ke bahasa yang mudah dipahami admin.
     */
    protected function translateError(string $message): string
    {
        $low = strtolower($message);
        if (str_contains($low, 'balance') || str_contains($low, 'insufficient')) {
            return 'Saldo kas Flip tidak mencukupi untuk melakukan transfer ini. Harap top-up saldo deposit Flip Anda terlebih dahulu.';
        }
        if (str_contains($low, 'account') || str_contains($low, 'not found') || str_contains($low, 'invalid')) {
            return 'Nomor rekening bank tujuan tidak valid atau tidak ditemukan.';
        }
        if (str_contains($low, 'bank')) {
            return 'Kode bank tidak valid atau sedang gangguan.';
        }
        return $message;
    }
}
