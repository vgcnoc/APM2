<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CustomerConnectionService
{
    /** Read each NAS once per sample, not once per customer. No router mutations. */
    public function snapshot(Collection $customers): array
    {
        $usernames = $customers->flatMap(fn ($customer) => array_values($this->credentials($customer)))->unique()->values();
        $latest = collect();
        $fresh = collect();
        if ($usernames->isNotEmpty()) {
            try {
                $lastIds = RadAcct::whereIn('username', $usernames)->groupBy('username')
                    ->selectRaw('MAX(radacctid) AS radacctid')->pluck('radacctid');
                $latest = RadAcct::whereIn('radacctid', $lastIds)->get(['username', 'nasipaddress'])->keyBy('username');
                $fresh = RadAcct::online()->fresh()->where('acctupdatetime', '>=', now()->subSeconds(90))->whereIn('username', $usernames)
                    ->get(['username', 'nasipaddress', 'acctupdatetime'])->groupBy('username');
            } catch (Throwable $e) {
                // API remains usable when accounting is unavailable.
            }
        }

        $routers = Router::whereIn('area_id', $customers->pluck('area_id')->filter()->unique())
            ->whereNotNull('nas_id')->get(['nas_id', 'area_id', 'api_port']);
        $byArea = $routers->groupBy('area_id');
        $ports = Router::whereNotNull('nas_id')->pluck('api_port', 'nas_id');
        $nases = Nas::all()->keyBy('id');
        $addressToNas = [];
        foreach ($nases as $nas) {
            foreach ($nas->accountingAddresses() as $address) {
                $addressToNas[$address][] = $nas->id;
            }
        }

        $samples = [];
        $rows = [];
        foreach ($customers as $customer) {
            $credentials = $this->credentials($customer);
            $candidates = ($byArea->get($customer->area_id) ?? collect())->pluck('nas_id')->unique()->all();
            // An accounting NAS is a fallback only when no area/router mapping exists.
            if (!$candidates) {
                foreach ($credentials as $username) {
                    $address = $latest->get($username)?->nasipaddress;
                    if (is_string($address)) {
                        $candidates = array_merge($candidates, $addressToNas[$address] ?? []);
                    }
                }
                $candidates = array_unique($candidates);
            }
            $row = ['id' => $customer->id, 'connection_status' => 'unknown', 'connection_source' => null,
                'connection_checked_at' => null, 'connection_message' => 'Router/NAS pelanggan belum dapat dipetakan.'];
            if (!$credentials) {
                $row['connection_message'] = 'Username PPPoE/Hotspot pelanggan belum tersedia.';
            } elseif ($candidates) {
                $complete = true;
                $checkedAt = [];
                $row['connection_message'] = 'API router belum dapat membaca seluruh sesi pelanggan.';
                foreach ($candidates as $nasId) {
                    $nas = $nases->get($nasId);
                    if (!$nas) { $complete = false; continue; }
                    $sample = $samples[$nasId] ??= $this->readSessions($nas, (int) ($ports->get($nasId) ?: 8728));
                    $checkedAt[] = $sample['checked_at'];
                    foreach ($credentials as $type => $username) {
                        if ($sample['readable'][$type]) {
                            if (isset($sample['users'][$type][$username])) {
                                $row = array_replace($row, ['connection_status' => 'online', 'connection_source' => 'routeros',
                                    'connection_checked_at' => $sample['checked_at'], 'connection_message' => 'Sesi aktif terbaca melalui API MikroTik.']);
                                break 2;
                            }
                        } else {
                            $complete = false;
                            // A fresh positive accounting record is usable; absence is never proof of offline.
                            $session = ($fresh->get($username) ?? collect())->first(fn ($s) => in_array($s->nasipaddress, $nas->accountingAddresses(), true));
                            if ($session) {
                                $row = array_replace($row, ['connection_status' => 'online', 'connection_source' => 'radius',
                                    'connection_checked_at' => $session->acctupdatetime->toIso8601String(), 'connection_message' => 'Sesi aktif dari accounting RADIUS terbaru; API belum tersedia.']);
                                // Prefer API evidence on the remaining routers if available.
                            }
                        }
                    }
                }
                if ($row['connection_status'] !== 'online' && $complete) {
                    sort($checkedAt);
                    $row = array_replace($row, ['connection_status' => 'offline', 'connection_source' => 'routeros',
                        'connection_checked_at' => $checkedAt[0] ?? null, 'connection_message' => 'Tidak ada sesi aktif pada router/NAS yang terkait.']);
                }
            }
            $rows[] = $row;
        }

        return ['customers' => $rows, 'generated_at' => now()->toIso8601String(), 'refresh_after' => 30, 'max_age' => 90];
    }

    private function credentials(Customer $customer): array
    {
        return array_filter(['ppp' => $customer->ont?->pppoe_user, 'hotspot' => $customer->ont?->hotspot_user],
            fn ($value) => is_string($value) && $value !== '');
    }

    protected function readSessions(Nas $nas, int $port): array
    {
        $key = hash('sha256', json_encode([$nas->id, $nas->apiHost(), $port, $nas->api_user, $nas->api_password]));
        return Cache::remember('gis:sessions:v1:'.$key, 20, function () use ($nas, $port) {
            $sample = ['readable' => ['ppp' => false, 'hotspot' => false], 'users' => ['ppp' => [], 'hotspot' => []],
                'checked_at' => now()->toIso8601String()];
            if (!$nas->apiHost() || !$nas->api_user || !$nas->api_password) return $sample;
            $client = app(RouterOsApiClient::class);
            try {
                $client->connect($nas->apiHost(), $port, $nas->api_user, (string) $nas->api_password, 1.0);
                foreach (['ppp' => ['/ppp/active/print', 'name'], 'hotspot' => ['/ip/hotspot/active/print', 'user']] as $type => [$command, $field]) {
                    try {
                        $rows = $client->command($command, ['.proplist' => $field])['rows'];
                        foreach ($rows as $row) {
                            if (isset($row[$field]) && $row[$field] !== '') $sample['users'][$type][$row[$field]] = true;
                        }
                        $sample['readable'][$type] = true;
                    } catch (Throwable $e) { /* A denied/failed command must not become an offline result. */ }
                }
            } catch (Throwable $e) { /* Unknown on failed authentication or unreachable router. */ }
            finally { $client->close(); }
            $sample['checked_at'] = now()->toIso8601String();
            return $sample;
        });
    }
}
