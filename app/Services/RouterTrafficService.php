<?php

namespace App\Services;

use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RouterTrafficService
{
    /** Null allowed IDs means unrestricted; an empty list deliberately means no NAS. */
    public function getRealTraffic($nasId = null, ?array $allowedNasIds = null): array
    {
        $query = Nas::orderBy('id');
        if ($allowedNasIds !== null) $query->whereIn('id', $allowedNasIds);
        if (is_numeric($nasId)) $query->where('id', (int) $nasId);
        elseif (!in_array($nasId, [null, '', 'all'], true)) $query->whereRaw('1 = 0');
        $nases = $query->get();
        $now = now()->timezone('Asia/Jakarta');
        $scope = hash('sha256', json_encode([$nasId, $allowedNasIds, $nases->pluck('id')->all()]));
        $rx = 0.0; $tx = 0.0; $sources = []; $measured = []; $online = []; $ppp = []; $hotspot = [];
        $download = 0.0; $upload = 0.0; $observed = false; $partialUsage = false;
        foreach ($nases as $nas) {
            $api = $this->probeRouter($nas);
            if (($api['status'] ?? '') === 'online') $online[] = $nas->id;
            $ppp = array_merge($ppp, $api['ppp_active'] ?? []);
            $hotspot = array_merge($hotspot, $api['hotspot_active'] ?? []);
            $rate = !empty($api['traffic_available']) ? $api : $this->accountingRate($nas);
            if ($rate && isset($rate['rx_mbps'], $rate['tx_mbps'])) {
                $rx += $rate['rx_mbps']; $tx += $rate['tx_mbps'];
                $sources[] = !empty($api['traffic_available']) ? 'routeros' : 'radius';
                $measured[] = $nas->id;
            }
            $usage = $this->observedDailyUsage($nas);
            $download += $usage['download_bytes']; $upload += $usage['upload_bytes'];
            $observed = $observed || $usage['observed'];
            $partialUsage = $partialUsage || $usage['partial'];
        }
        $available = count($measured) > 0;
        $complete = $available && count($measured) === $nases->count();
        $types = array_unique($sources);
        $type = !$available ? 'unavailable' : (count($types) > 1 ? 'mixed' : reset($types));
        $label = ['routeros' => 'Terukur melalui API MikroTik', 'radius' => 'Terukur dari interval accounting RADIUS', 'mixed' => 'Terukur melalui API + RADIUS', 'unavailable' => 'Data tidak tersedia; periksa VPN/API dan accounting'][$type];
        if ($available && !$complete) $label .= ' (sebagian router)';
        $total = $available ? round($rx + $tx, 2) : null;
        $peakKey = 'traffic:v3:peak:'.$now->toDateString().':'.$scope;
        $historyKey = 'traffic:v3:history:'.$now->toDateString().':'.$scope;
        $peak = Cache::get($peakKey);
        if ($available) {
            $peak = max((float) ($peak ?? 0), $total);
            Cache::put($peakKey, $peak, $now->copy()->endOfDay());
        }
        $history = Cache::get($historyKey, []);
        $last = end($history);
        // No synthetic zero points: only append a sample that was actually measured.
        if ($available && (!$last || $now->timestamp - $last['timestamp'] >= 30)) {
            $history[] = ['time' => $now->format('H:i'), 'timestamp' => $now->timestamp, 'rx' => round($rx, 2), 'tx' => round($tx, 2), 'partial' => !$complete];
            $history = array_slice($history, -12);
            Cache::put($historyKey, $history, $now->copy()->endOfDay());
        }
        return [
            'download_mbps' => $available ? round($rx, 2) : null, 'upload_mbps' => $available ? round($tx, 2) : null, 'total_mbps' => $total,
            'peak_mbps_today' => $peak, 'download_gb_today' => $observed ? round($download / 1073741824, 3) : null,
            'upload_gb_today' => $observed ? round($upload / 1073741824, 3) : null, 'total_gb_today' => $observed ? round(($download + $upload) / 1073741824, 3) : null,
            'daily_usage_status' => !$observed ? 'unavailable' : ($partialUsage ? 'partial' : 'recorded'),
            'history' => $history, 'source_type' => $type, 'source_label' => $label,
            'data_available' => $available, 'coverage' => $complete ? 'complete' : ($available ? 'partial' : 'unavailable'),
            'measured_nas_ids' => $measured, 'online_nas_ids' => $online, 'selected_nas_count' => $nases->count(),
            'router_online' => count($online) > 0, 'active_ppp_users' => array_values(array_unique($ppp)), 'active_hotspot_users' => array_values(array_unique($hotspot)),
        ];
    }

    protected function probeRouter(Nas $nas): array
    {
        $port = (int) (Router::where('nas_id', $nas->id)->value('api_port') ?: 8728);
        $key = hash('sha256', implode('|', [$nas->id, $nas->apiHost(), $port, $nas->api_user, $nas->api_password]));
        return Cache::remember('traffic:v3:api:'.$key, 10, function () use ($nas, $port) {
            $result = ['status' => 'offline', 'traffic_available' => false, 'ppp_active' => [], 'hotspot_active' => []];
            if (!$nas->apiHost() || !$nas->api_user || !$nas->api_password) return $result;
            $client = app(RouterOsApiClient::class);
            try {
                $client->connect($nas->apiHost(), $port, $nas->api_user, (string) $nas->api_password, 1.0);
                $interfaces = $client->command('/interface/print', ['.proplist' => 'name,running,type'])['rows'];
                $result['status'] = 'online';
                $running = array_values(array_filter($interfaces, fn ($i) => ($i['running'] ?? 'false') === 'true'));
                $target = null;
                try {
                    $members = $client->command('/interface/list/member/print', ['?list' => 'WAN', '.proplist' => 'interface'])['rows'];
                    $wan = array_column($members, 'interface');
                    $candidates = array_values(array_filter($running, fn ($i) => in_array($i['name'], $wan, true)));
                    if (count($candidates) === 1) $target = $candidates[0]['name'];
                } catch (Throwable $e) {}
                if (!$target) {
                    try {
                        $routes = $client->command('/ip/route/print', ['?dst-address' => '0.0.0.0/0', '?active' => 'true', '.proplist' => 'gateway,immediate-gw'])['rows'];
                        $names = [];
                        foreach ($routes as $route) {
                            $gateway = $route['immediate-gw'] ?? $route['gateway'] ?? '';
                            $names[] = str_contains($gateway, '%') ? substr($gateway, strrpos($gateway, '%') + 1) : $gateway;
                        }
                        $candidates = array_values(array_filter($running, fn ($i) => in_array($i['name'], $names, true)));
                        if (count($candidates) === 1) $target = $candidates[0]['name'];
                    } catch (Throwable $e) {}
                }
                if (!$target) {
                    $candidates = array_values(array_filter($running, fn ($i) => preg_match('/wan|internet|pppoe-out/i', $i['name'])));
                    if (count($candidates) === 1) $target = $candidates[0]['name'];
                }
                if ($target) {
                    try {
                        $row = $client->command('/interface/monitor-traffic', ['interface' => $target, 'once' => ''])['rows'][0] ?? [];
                        if (isset($row['rx-bits-per-second'], $row['tx-bits-per-second'])) {
                            $result += ['rx_mbps' => (float) $row['rx-bits-per-second'] / 1000000, 'tx_mbps' => (float) $row['tx-bits-per-second'] / 1000000, 'interface' => $target];
                            $result['traffic_available'] = true;
                        }
                    } catch (Throwable $e) {}
                }
                try { $result['ppp_active'] = array_values(array_filter(array_column($client->command('/ppp/active/print', ['.proplist' => 'name'])['rows'], 'name'))); } catch (Throwable $e) {}
                try { $result['hotspot_active'] = array_values(array_filter(array_column($client->command('/ip/hotspot/active/print', ['.proplist' => 'user'])['rows'], 'user'))); } catch (Throwable $e) {}
            } catch (Throwable $e) { /* Explicit unavailable state, not a fabricated zero measurement. */ }
            finally { $client->close(); }
            return $result;
        });
    }

    protected function accountingRate(Nas $nas): ?array
    {
        try {
            $rows = RadAcct::online()->fresh()->whereIn('nasipaddress', $nas->accountingAddresses())->get(['radacctid','acctupdatetime','acctinputoctets','acctoutputoctets']);
            $key = 'traffic:v3:accounting:'.$nas->id;
            $previous = Cache::get($key, []); $next = []; $rx = 0.0; $tx = 0.0; $measured = false;
            foreach ($rows as $row) {
                $sample = ['time' => $row->acctupdatetime->timestamp, 'in' => (float) $row->acctinputoctets, 'out' => (float) $row->acctoutputoctets, 'rx' => null, 'tx' => null];
                $old = $previous[$row->radacctid] ?? null;
                if ($old && $sample['time'] > $old['time'] && $sample['in'] >= $old['in'] && $sample['out'] >= $old['out']) {
                    $elapsed = $sample['time'] - $old['time'];
                    $sample['rx'] = ($sample['out'] - $old['out']) * 8 / $elapsed / 1000000;
                    $sample['tx'] = ($sample['in'] - $old['in']) * 8 / $elapsed / 1000000;
                } elseif ($old && $sample['time'] === $old['time'] && $sample['in'] === $old['in'] && $sample['out'] === $old['out']) {
                    $sample['rx'] = $old['rx']; $sample['tx'] = $old['tx'];
                }
                if ($sample['rx'] !== null) { $rx += $sample['rx']; $tx += $sample['tx']; $measured = true; }
                $next[$row->radacctid] = $sample;
            }
            Cache::put($key, $next, max(900, RadAcct::freshnessSeconds() * 2));
            return $measured ? ['rx_mbps' => $rx, 'tx_mbps' => $tx] : null;
        } catch (Throwable $e) { return null; }
    }

    /** Cumulative session counters cannot reconstruct midnight history. Explicitly mark that gap. */
    protected function observedDailyUsage(Nas $nas): array
    {
        $empty = ['download_bytes' => 0, 'upload_bytes' => 0, 'observed' => false, 'partial' => false];
        try {
            $today = now()->timezone('Asia/Jakarta');
            $start = $today->copy()->startOfDay()->setTimezone(config('app.timezone', 'UTC'));
            $rows = RadAcct::whereIn('nasipaddress', $nas->accountingAddresses())->where(fn ($q) => $q->where('acctstarttime', '>=', $start)->orWhere('acctstoptime', '>=', $start)->orWhere('acctupdatetime', '>=', $start))->get();
            $key = 'traffic:v3:daily:'.$today->toDateString().':'.$nas->id;
            $state = Cache::get($key, []);
            foreach ($rows as $row) {
                $currentIn = (float) $row->acctinputoctets; $currentOut = (float) $row->acctoutputoctets;
                $startedToday = $row->acctstarttime && $row->acctstarttime->gte($start);
                $entry = $state[$row->radacctid] ?? ['baseline_in' => $startedToday ? 0 : $currentIn, 'baseline_out' => $startedToday ? 0 : $currentOut, 'partial' => !$startedToday];
                $entry['in'] = max(0, $currentIn - $entry['baseline_in']);
                $entry['out'] = max(0, $currentOut - $entry['baseline_out']);
                $state[$row->radacctid] = $entry;
            }
            Cache::put($key, $state, $today->copy()->addDays(2)->endOfDay());
            $in = array_sum(array_column($state, 'in')); $out = array_sum(array_column($state, 'out'));
            $partial = count(array_filter($state, fn ($entry) => $entry['partial'])) > 0;
            return ['download_bytes' => $out, 'upload_bytes' => $in, 'observed' => count($state) > 0 && (!$partial || $in + $out > 0), 'partial' => $partial];
        } catch (Throwable $e) { return $empty; }
    }
}
