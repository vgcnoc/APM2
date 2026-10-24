<?php

namespace App\Services;

use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Service untuk mengukur Trafik Jaringan Internet 100% REAL / ASLI.
 * 
 * Hirarki Pengukuran:
 * 1. LIVE MIKROTIK (RouterOS API): Query live bytes/traffic monitor langsung dari router gateway/WAN.
 * 2. LIVE RADIUS ACCOUNTING (Interim-Update Delta): Menghitung laju transfer bit/detik dari sesi aktif FreeRADIUS.
 * 3. STANDBY / IDLE: Jika tidak ada router tersambung dan 0 sesi aktif, menampilkan angka asli 0.0 Mbps (tanpa data palsu).
 */
class RouterTrafficService
{
    /**
     * Ambil statistik trafik internet yang 100% REAL dan akurat.
     *
     * @param int|string|null $nasId
     * @return array
     */
    public function getRealTraffic($nasId = null): array
    {
        $todayStr = today()->toDateString();
        $now = now()->timezone('Asia/Jakarta');

        // ── 1. Coba baca langsung dari Router MikroTik (RouterOS API) ─────────────
        $routerResult = $this->readFromMikrotik($nasId);

        $rxMbps = 0.0;
        $txMbps = 0.0;
        $sourceType = 'standby'; // 'routeros', 'radius', 'standby'
        $sourceLabel = 'Standby (0 Mbps)';
        $routerOnline = false;
        $activePppUsers = [];
        $activeHotspotUsers = [];

        if ($routerResult !== null && $routerResult['status'] === 'online') {
            $rxMbps = (float) $routerResult['rx_mbps'];
            $txMbps = (float) $routerResult['tx_mbps'];
            $sourceType = 'routeros';
            $sourceLabel = 'Live MikroTik API (' . ($routerResult['interface'] ?? 'WAN') . ')';
            $routerOnline = true;
            $activePppUsers = $routerResult['ppp_active'] ?? [];
            $activeHotspotUsers = $routerResult['hotspot_active'] ?? [];
        } else {
            // ── 2. Fallback Pengukuran Akurat dari Sesi FreeRADIUS (Delta Bytes) ───
            $radiusResult = $this->readFromRadiusAccounting($nasId);
            if ($radiusResult !== null && $radiusResult['active_sessions'] > 0) {
                $rxMbps = (float) $radiusResult['rx_mbps'];
                $txMbps = (float) $radiusResult['tx_mbps'];
                $sourceType = 'radius';
                $sourceLabel = "Live RADIUS Sesi ({$radiusResult['active_sessions']} aktif)";
            } else {
                // ── 3. Benar-benar tidak ada trafik / router offline (REAL 0 Mbps) ─
                $rxMbps = 0.0;
                $txMbps = 0.0;
                $sourceType = 'standby';
                $sourceLabel = 'Standby (Tidak ada trafik mengalir)';
            }
        }

        $totalMbps = round($rxMbps + $txMbps, 2);

        // ── 4. Akumulasi Kuota Transfer Data Hari Ini (Asli dari Tabel RadAcct) ──
        $bytesToday = $this->calculateRealBytesToday($nasId);
        $downloadGbToday = round($bytesToday['download_bytes'] / (1024 * 1024 * 1024), 2);
        $uploadGbToday = round($bytesToday['upload_bytes'] / (1024 * 1024 * 1024), 2);
        $totalGbToday = round(($bytesToday['download_bytes'] + $bytesToday['upload_bytes']) / (1024 * 1024 * 1024), 2);

        // ── 5. Puncak Trafik Hari Ini (Real Peak Observed) ──────────────────────
        $peakCacheKey = "real_traffic_peak_{$todayStr}";
        $currentPeak = (float) Cache::get($peakCacheKey, 0.0);
        if ($totalMbps > $currentPeak) {
            $currentPeak = $totalMbps;
            Cache::put($peakCacheKey, $currentPeak, now()->endOfDay());
        }

        // ── 6. Histori Titik Grafik 12 Interval (100% Data Aktual) ─────────────
        $historyCacheKey = "real_traffic_history_{$todayStr}";
        $history = Cache::get($historyCacheKey, []);

        $lastPoint = end($history);
        $shouldAppend = true;
        if ($lastPoint && isset($lastPoint['timestamp'])) {
            // Perbarui setiap minimal 30 detik
            if ($now->timestamp - $lastPoint['timestamp'] < 30) {
                $shouldAppend = false;
            }
        }

        if ($shouldAppend) {
            $history[] = [
                'time'      => $now->format('H:i'),
                'timestamp' => $now->timestamp,
                'rx'        => round($rxMbps, 1),
                'tx'        => round($txMbps, 1),
            ];

            // Pertahankan maksimal 12 titik terakhir
            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }
            Cache::put($historyCacheKey, $history, now()->endOfDay());
        }

        // Jika histori masih kurang dari 12 titik, lengkapi titik awal dengan nilai 0.0
        $formattedHistory = [];
        $pointsNeeded = 12 - count($history);
        for ($i = $pointsNeeded; $i > 0; $i--) {
            $t = $now->copy()->subMinutes($i * 5);
            $formattedHistory[] = [
                'time' => $t->format('H:i'),
                'rx'   => 0.0,
                'tx'   => 0.0,
            ];
        }
        foreach ($history as $h) {
            $formattedHistory[] = [
                'time' => $h['time'],
                'rx'   => (float) $h['rx'],
                'tx'   => (float) $h['tx'],
            ];
        }

        return [
            'download_mbps'     => round($rxMbps, 1),
            'upload_mbps'       => round($txMbps, 1),
            'total_mbps'        => round($totalMbps, 1),
            'peak_mbps_today'   => round($currentPeak, 1),
            'download_gb_today' => $downloadGbToday,
            'upload_gb_today'   => $uploadGbToday,
            'total_gb_today'    => $totalGbToday,
            'history'           => $formattedHistory,
            'source_type'       => $sourceType,
            'source_label'      => $sourceLabel,
            'router_online'     => $routerOnline,
            'active_ppp_users'  => $activePppUsers,
            'active_hotspot_users' => $activeHotspotUsers,
        ];
    }

    /**
     * Membaca statistik antarmuka live langsung dari MikroTik RouterOS API.
     */
    private function readFromMikrotik($nasId = null): ?array
    {
        try {
            $query = Nas::query();
            if (is_numeric($nasId)) {
                $query->where('id', (int) $nasId);
            }
            $nasList = $query->get();

            foreach ($nasList as $nas) {
                $host = $nas->vpn_ip ?: ($nas->nasname !== '0.0.0.0/0' ? $nas->nasname : null);
                $port = (int) (Router::where('nas_id', $nas->id)->value('api_port') ?: 8728);
                $user = $nas->api_user;
                $pass = (string) $nas->api_password;

                if (!$host || !$user) {
                    continue;
                }

                $cacheKey = "router_traffic_live_{$nas->id}";
                $cached = Cache::get($cacheKey);
                if ($cached && isset($cached['status']) && $cached['status'] === 'online' && (time() - ($cached['time'] ?? 0) < 5)) {
                    return $cached;
                }

                $client = new RouterOsApiClient();
                try {
                    // Timeout cepat 1.0 detik agar tidak memperlambat respon dashboard bila router mati
                    $client->connect($host, $port, $user, $pass, 1.0);

                    // 1. Ambil daftar interface untuk mencari interface WAN utama
                    $interfaces = $client->command('/interface/print')['rows'] ?? [];
                    $targetIf = null;
                    $totalRxBps = 0;
                    $totalTxBps = 0;

                    // Prioritaskan interface bernama wan, ether1, sfp, internet, atau pppoe-out
                    foreach ($interfaces as $if) {
                        $nameLower = strtolower($if['name'] ?? '');
                        if (str_contains($nameLower, 'wan') || str_contains($nameLower, 'internet') || str_contains($nameLower, 'pppoe-out')) {
                            $targetIf = $if['name'];
                            break;
                        }
                    }

                    if (!$targetIf && count($interfaces) > 0) {
                        $targetIf = $interfaces[0]['name'] ?? 'ether1';
                    }

                    // 2. Baca monitor-traffic pada target interface
                    if ($targetIf) {
                        try {
                            $trafficReply = $client->command('/interface/monitor-traffic', [
                                'interface' => $targetIf,
                                'once'      => '',
                            ]);
                            $tRow = $trafficReply['rows'][0] ?? [];
                            $totalRxBps = (float) ($tRow['rx-bits-per-second'] ?? 0);
                            $totalTxBps = (float) ($tRow['tx-bits-per-second'] ?? 0);
                        } catch (Throwable $e) {}
                    }

                    // 3. Ambil daftar user PPPoE aktif langsung dari Router
                    $activePpp = [];
                    try {
                        $pppRows = $client->command('/ppp/active/print')['rows'] ?? [];
                        foreach ($pppRows as $p) {
                            if (!empty($p['name'])) {
                                $activePpp[] = $p['name'];
                            }
                        }
                    } catch (Throwable $e) {}

                    // 4. Ambil daftar user Hotspot aktif langsung dari Router
                    $activeHotspot = [];
                    try {
                        $hsRows = $client->command('/ip/hotspot/active/print')['rows'] ?? [];
                        foreach ($hsRows as $h) {
                            if (!empty($h['user'])) {
                                $activeHotspot[] = $h['user'];
                            }
                        }
                    } catch (Throwable $e) {}

                    $client->close();

                    $res = [
                        'status'         => 'online',
                        'time'           => time(),
                        'interface'      => $targetIf,
                        'rx_mbps'        => round($totalRxBps / 1_000_000, 2),
                        'tx_mbps'        => round($totalTxBps / 1_000_000, 2),
                        'ppp_active'     => $activePpp,
                        'hotspot_active' => $activeHotspot,
                    ];

                    Cache::put($cacheKey, $res, 10);
                    return $res;
                } catch (Throwable $e) {
                    $client->close();
                    continue;
                }
            }
        } catch (Throwable $e) {}

        return null;
    }

    /**
     * Menghitung laju transfer dari data akunting FreeRADIUS (delta bytes riil per detik).
     */
    private function readFromRadiusAccounting($nasId = null): ?array
    {
        try {
            if (!class_exists(RadAcct::class)) {
                return null;
            }

            $onlineQuery = RadAcct::online();
            if (is_numeric($nasId)) {
                $nasObj = Nas::find((int) $nasId);
                if ($nasObj) {
                    $onlineQuery->where('nasipaddress', $nasObj->nasname);
                }
            }

            $activeCount = (clone $onlineQuery)->count();
            if ($activeCount === 0) {
                return ['active_sessions' => 0, 'rx_mbps' => 0.0, 'tx_mbps' => 0.0];
            }

            $currentBytes = (clone $onlineQuery)
                ->selectRaw('COALESCE(SUM(acctinputoctets), 0) as total_in, COALESCE(SUM(acctoutputoctets), 0) as total_out')
                ->first();

            $totalIn = (float) ($currentBytes->total_in ?? 0);
            $totalOut = (float) ($currentBytes->total_out ?? 0);
            $nowTime = microtime(true);

            $snapshotKey = "radius_traffic_meter_snapshot_" . ($nasId ?: 'all');
            $prevSnapshot = Cache::get($snapshotKey);

            $rxMbps = 0.0;
            $txMbps = 0.0;

            if ($prevSnapshot && isset($prevSnapshot['time']) && $nowTime > $prevSnapshot['time']) {
                $elapsedSec = max(0.5, $nowTime - $prevSnapshot['time']);
                $deltaIn = max(0, $totalIn - $prevSnapshot['total_in']);
                $deltaOut = max(0, $totalOut - $prevSnapshot['total_out']);

                // acctoutputoctets = download oleh pelanggan (Rx jaringan)
                // acctinputoctets = upload oleh pelanggan (Tx jaringan)
                $rxMbps = round(($deltaOut * 8) / ($elapsedSec * 1_000_000), 2);
                $txMbps = round(($deltaIn * 8) / ($elapsedSec * 1_000_000), 2);
            }

            Cache::put($snapshotKey, [
                'time'      => $nowTime,
                'total_in'  => $totalIn,
                'total_out' => $totalOut,
            ], 60);

            return [
                'active_sessions' => $activeCount,
                'rx_mbps'         => $rxMbps,
                'tx_mbps'         => $txMbps,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Menghitung total byte download & upload yang benar-benar tercatat HARI INI di database FreeRADIUS.
     */
    private function calculateRealBytesToday($nasId = null): array
    {
        $dlBytes = 0.0;
        $ulBytes = 0.0;

        try {
            if (class_exists(RadAcct::class)) {
                $q = RadAcct::query()->where(function ($sub) {
                    $sub->whereDate('acctstarttime', today())
                        ->orWhereDate('acctstoptime', today())
                        ->orWhereNull('acctstoptime');
                });

                if (is_numeric($nasId)) {
                    $nasObj = Nas::find((int) $nasId);
                    if ($nasObj) {
                        $q->where('nasipaddress', $nasObj->nasname);
                    }
                }

                $res = $q->selectRaw('COALESCE(SUM(acctinputoctets), 0) as ul, COALESCE(SUM(acctoutputoctets), 0) as dl')->first();
                if ($res) {
                    $ulBytes = (float) $res->ul;
                    $dlBytes = (float) $res->dl;
                }
            }
        } catch (Throwable $e) {}

        return [
            'download_bytes' => $dlBytes,
            'upload_bytes'   => $ulBytes,
        ];
    }
}
