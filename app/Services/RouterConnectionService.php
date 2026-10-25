<?php

namespace App\Services;

use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RouterConnectionService
{
    public function probe(Nas $nas, int $port = 8728): array
    {
        $key = hash('sha256', implode('|', [$nas->id, $nas->apiHost(), $port, $nas->api_user, $nas->api_password]));
        return Cache::remember('nas-health:'.$key, 15, function () use ($nas, $port) {
            $result = ['status' => 'offline', 'state' => 'unconfigured', 'api_authenticated' => false, 'accounting_last_seen' => null, 'accounting_fresh' => false, 'checked_at' => now()->toIso8601String()];
            try {
                $last = RadAcct::whereIn('nasipaddress', $nas->accountingAddresses())->max('acctupdatetime');
                $result['accounting_last_seen'] = $last;
                $result['accounting_fresh'] = $last && now()->subSeconds(max(180, (int) config('radius.interim_interval', 60) * 3))->lte(\Carbon\Carbon::parse($last));
            } catch (Throwable $e) { /* Accounting availability is independent from API access. */ }
            if (!$nas->apiHost() || !$nas->api_user || !$nas->api_password) {
                return $result + ['message' => 'IP atau akun API belum lengkap.'];
            }
            $client = app(RouterOsApiClient::class);
            try {
                $client->connect($nas->apiHost(), $port, $nas->api_user, (string) $nas->api_password, 1.5);
                $client->command('/system/resource/print', ['.proplist' => 'uptime']);
                return array_replace($result, ['status' => 'online', 'state' => 'api_ready', 'api_authenticated' => true, 'message' => 'API terautentikasi'.($result['accounting_fresh'] ? '; accounting mutakhir.' : '; accounting belum mutakhir.')]);
            } catch (Throwable $e) {
                return array_replace($result, [
                    'state' => str_contains($e->getMessage(), 'RouterOS:') ? 'api_access_denied' : 'unreachable',
                    'message' => str_contains($e->getMessage(), 'RouterOS:') ? 'Akun API ditolak atau tidak memiliki izin baca.' : 'API tidak terjangkau. Periksa VPN, rute, dan layanan API router.',
                ]);
            } finally { $client->close(); }
        });
    }
}
