<?php

namespace App\Services;

use App\Models\Radius\Nas;
use App\Models\Router;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Deteksi otomatis kapasitas maksimal sesi online berdasarkan kekuatan CPU.
 *
 * Urutan sumber data:
 *  1. CPU router MikroTik (dibaca live via RouterOS API: /system/resource).
 *  2. Hasil deteksi terakhir router yang sedang tidak terjangkau (disimpan permanen di cache).
 *  3. CPU server aplikasi/RADIUS (cadangan bila router belum pernah terdeteksi).
 */
class RouterCapacityService
{
    /**
     * Estimasi sesi PPPoE/Hotspot aman per 1 core @ 1 GHz untuk tiap arsitektur CPU.
     * Kalibrasi kasar dari kelas perangkat: hEX (mmips) ±250, RB4011 (arm) ±1.400,
     * CCR1009 (tile) ±2.100, CCR2004 (arm64) ±2.700, CCR1036 (tile) ±8.600.
     */
    private const SESSIONS_PER_CORE_GHZ = [
        'tile'    => 200,
        'arm64'   => 400,
        'arm'     => 250,
        'x86_64'  => 600,
        'x86'     => 500,
        'mmips'   => 150,
        'mipsbe'  => 120,
        'smips'   => 80,
        'powerpc' => 150,
        'ppc'     => 150,
    ];

    private const DEFAULT_SESSIONS_PER_CORE_GHZ = 200;
    private const LIVE_CACHE_SECONDS = 60;

    /**
     * Hitung kapasitas total untuk daftar NAS (null = semua NAS).
     *
     * @return array{max_capacity:int, source:string, cpu_load:?int, devices:array<int,array>}
     */
    public function detect(?array $nasIds = null): array
    {
        $devices = [];

        try {
            $query = Nas::query();
            if ($nasIds !== null) {
                $query->whereIn('id', $nasIds ?: [0]);
            }
            $nasList = $query->get();
        } catch (Throwable $e) {
            $nasList = collect();
        }

        foreach ($nasList as $nas) {
            $info = $this->probeNas($nas);
            if ($info !== null) {
                $devices[] = $info;
            }
        }

        $usable = array_values(array_filter($devices, fn ($d) => ($d['capacity'] ?? 0) > 0));

        if (count($usable) > 0) {
            $loads = array_values(array_filter(array_map(
                fn ($d) => $d['status'] === 'online' ? $d['cpu_load'] : null,
                $usable
            ), fn ($v) => $v !== null));

            return [
                'max_capacity' => array_sum(array_column($usable, 'capacity')),
                'source'       => 'router',
                'cpu_load'     => count($loads) ? (int) round(array_sum($loads) / count($loads)) : null,
                'devices'      => $devices,
            ];
        }

        $server = $this->probeServer();

        return [
            'max_capacity' => $server['capacity'],
            'source'       => 'server',
            'cpu_load'     => $server['cpu_load'],
            'devices'      => array_merge($devices, [$server]),
        ];
    }

    /**
     * Ambil ID NAS sesuai cakupan area (null = semua NAS).
     */
    public function nasIdsForScope($areaId, bool $canViewAllAreas, array $accessibleAreaIds): ?array
    {
        if (is_numeric($areaId)) {
            return Router::where('area_id', (int) $areaId)->whereNotNull('nas_id')->pluck('nas_id')->all();
        }
        if (!$canViewAllAreas && count($accessibleAreaIds) > 0) {
            return Router::whereIn('area_id', $accessibleAreaIds)->whereNotNull('nas_id')->pluck('nas_id')->all();
        }
        return null;
    }

    private function probeNas(Nas $nas): ?array
    {
        $liveKey = "router_cpu_live_{$nas->id}";
        $lastKey = "router_cpu_last_{$nas->id}";

        $live = Cache::remember($liveKey, self::LIVE_CACHE_SECONDS, function () use ($nas, $lastKey) {
            $result = $this->readRouterResource($nas);
            if ($result['status'] === 'online') {
                Cache::forever($lastKey, $result);
            }
            return $result;
        });

        if ($live['status'] === 'online') {
            return $live;
        }

        // Router tidak terjangkau: pakai spesifikasi CPU hasil deteksi terakhir (hardware tidak berubah)
        $last = Cache::get($lastKey);
        if ($last) {
            return array_merge($last, [
                'status'     => 'offline',
                'cpu_load'   => null,
                'error'      => $live['error'] ?? null,
            ]);
        }

        return $live; // offline & belum pernah terdeteksi (capacity 0)
    }

    private function readRouterResource(Nas $nas): array
    {
        $name = $nas->shortname ?: $nas->nasname;
        $host = $nas->vpn_ip ?: ($nas->nasname !== '0.0.0.0/0' ? $nas->nasname : null);
        $port = (int) (Router::where('nas_id', $nas->id)->value('api_port') ?: 8728);

        $base = [
            'type'      => 'router',
            'nas_id'    => $nas->id,
            'name'      => $name,
            'status'    => 'offline',
            'capacity'  => 0,
            'cpu_load'  => null,
            'detected_at' => now()->timezone('Asia/Jakarta')->format('d M Y H:i'),
        ];

        if (!$host || !$nas->api_user) {
            return $base + ['error' => 'IP / akun API router belum tersedia'];
        }

        $client = new RouterOsApiClient();
        try {
            $client->connect($host, $port, $nas->api_user, (string) $nas->api_password);
            $res = $client->command('/system/resource/print')['rows'][0] ?? [];
        } catch (Throwable $e) {
            return $base + ['error' => $e->getMessage()];
        } finally {
            $client->close();
        }

        $cores = max(1, (int) ($res['cpu-count'] ?? 1));
        $mhz   = (int) ($res['cpu-frequency'] ?? 0);
        $arch  = strtolower((string) ($res['architecture-name'] ?? ''));

        return array_merge($base, [
            'status'    => 'online',
            'board'     => $res['board-name'] ?? null,
            'cpu'       => $res['cpu'] ?? null,
            'arch'      => $arch ?: null,
            'cores'     => $cores,
            'mhz'       => $mhz,
            'cpu_load'  => isset($res['cpu-load']) ? (int) $res['cpu-load'] : null,
            'version'   => $res['version'] ?? null,
            'capacity'  => $this->estimateCapacity($cores, $mhz, $arch),
            'error'     => null,
        ]);
    }

    private function probeServer(): array
    {
        return Cache::remember('server_cpu_capacity', 3600, function () {
            $cores = 0;
            $mhz = 0;
            $model = null;

            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if ($cpuinfo) {
                $cores = preg_match_all('/^processor\s*:/m', $cpuinfo);
                if (preg_match('/^cpu MHz\s*:\s*([\d.]+)/m', $cpuinfo, $m)) {
                    $mhz = (int) round((float) $m[1]);
                }
                if (preg_match('/^model name\s*:\s*(.+)$/m', $cpuinfo, $m)) {
                    $model = trim($m[1]);
                }
            }
            if ($cores < 1) {
                $cores = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 1);
            }

            $load = null;
            if (function_exists('sys_getloadavg') && ($avg = @sys_getloadavg())) {
                $load = (int) min(100, round(($avg[0] / max(1, $cores)) * 100));
            }

            return [
                'type'     => 'server',
                'name'     => 'Server RADIUS / Aplikasi',
                'status'   => 'online',
                'cpu'      => $model,
                'arch'     => 'x86_64',
                'cores'    => $cores,
                'mhz'      => $mhz,
                'cpu_load' => $load,
                'capacity' => $this->estimateCapacity($cores, $mhz, 'x86_64'),
                'detected_at' => now()->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ];
        });
    }

    private function estimateCapacity(int $cores, int $mhz, string $arch): int
    {
        $ghz = $mhz > 0 ? $mhz / 1000 : 1.0;
        $factor = self::DEFAULT_SESSIONS_PER_CORE_GHZ;

        foreach (self::SESSIONS_PER_CORE_GHZ as $key => $value) {
            if ($arch !== '' && str_starts_with($arch, $key)) {
                $factor = $value;
                break;
            }
        }

        $raw = $cores * $ghz * $factor;

        return max(50, (int) (floor($raw / 50) * 50));
    }
}
