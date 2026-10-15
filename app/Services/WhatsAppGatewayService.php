<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    /**
     * Dapatkan URL base WA Gateway Node.js
     */
    public function getGatewayUrl(): string
    {
        return rtrim(Setting::get('wa_gateway_url', 'http://127.0.0.1:3001'), '/');
    }

    /**
     * Cek status koneksi WA dan ambil QR code
     */
    public function getStatus(): array
    {
        $url = $this->getGatewayUrl();

        try {
            $response = Http::timeout(5)->get("{$url}/api/status");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'error',
                'connected' => false,
                'message' => 'WA Gateway merespons dengan kode HTTP: ' . $response->status(),
                'engine' => 'Baileys Multi-Device Engine',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'connected' => false,
                'message' => 'Layanan WA Gateway (Port 3001) belum aktif di server. Jalankan `node wa-gateway/server.js`.',
                'error_detail' => $e->getMessage(),
                'engine' => 'Baileys Multi-Device Engine',
            ];
        }
    }

    /**
     * Kirim pesan WhatsApp (Teks atau Gambar)
     */
    public function sendMessage(string $phone, string $message, ?string $mediaUrl = null): array
    {
        $url = $this->getGatewayUrl();

        try {
            $payload = [
                'phone' => $phone,
                'message' => $message,
            ];

            if (!empty($mediaUrl)) {
                $payload['media_url'] = $mediaUrl;
            }

            $response = Http::timeout(25)->post("{$url}/api/send-message", $payload);
            $data = $response->json();

            if ($response->successful() && !empty($data['success'])) {
                return [
                    'success' => true,
                    'messageId' => $data['messageId'] ?? null,
                    'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Gagal mengirim pesan melalui WhatsApp Gateway.',
            ];
        } catch (\Exception $e) {
            Log::error("[WhatsAppGateway] Send message exception to {$phone}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Putuskan sesi / logout WA
     */
    public function logout(): array
    {
        $url = $this->getGatewayUrl();
        try {
            $response = Http::timeout(10)->post("{$url}/api/logout");
            return $response->json();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal logout dari gateway: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Restart layanan socket
     */
    public function restart(): array
    {
        $url = $this->getGatewayUrl();
        try {
            $response = Http::timeout(10)->post("{$url}/api/restart");
            return $response->json();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal restart gateway: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Smart Salam Waktu Indonesia (Pagi / Siang / Sore / Malam)
     */
    public static function getTimeGreeting(): string
    {
        $hour = (int) now()->timezone('Asia/Jakarta')->format('H');
        if ($hour >= 4 && $hour < 11) {
            return 'Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            return 'Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            return 'Sore';
        } else {
            return 'Malam';
        }
    }

    /**
     * Smart Spintax Parser
     * contoh: "{Halo|Hai|Selamat {salam}} {nama}" -> acak setiap penerima
     */
    public static function parseSpintax(string $text): string
    {
        return preg_replace_callback('/\{([^{}]*)\}/', function ($matches) {
            $parts = explode('|', $matches[1]);
            return $parts[array_rand($parts)];
        }, $text);
    }

    /**
     * Render Template Pintar dengan Variabel Personalisasi
     */
    public static function renderMessage(string $template, array $data): string
    {
        $companyName = Setting::get('company_name', Setting::get('app_name', 'ISP Internet'));
        $salam = self::getTimeGreeting();

        $variables = [
            '{nama}' => $data['name'] ?? 'Pelanggan',
            '{id_pelanggan}' => $data['customer_id'] ?? $data['id'] ?? '-',
            '{telepon}' => $data['phone'] ?? '-',
            '{paket}' => $data['package_name'] ?? 'Internet',
            '{tagihan}' => !empty($data['unpaid_amount']) ? 'Rp ' . number_format($data['unpaid_amount'], 0, ',', '.') : 'Rp 0',
            '{nomor_invoice}' => $data['invoice_number'] ?? '-',
            '{jatuh_tempo}' => $data['due_date'] ?? '-',
            '{alamat}' => $data['address'] ?? '-',
            '{area}' => $data['area_name'] ?? 'Pusat',
            '{salam}' => $salam,
            '{perusahaan}' => $companyName,
        ];

        $rendered = str_replace(array_keys($variables), array_values($variables), $template);

        // Eksekusi spintax anti-ban setelah variabel diisi
        return self::parseSpintax($rendered);
    }
}
