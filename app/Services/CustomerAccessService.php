<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CustomerAccessService
{
    public function __construct(private RadiusService $radius) {}

    /** Persist the desired state, enforce it, and report partial failures for retry. */
    public function setDisabled(Customer $customer, bool $disabled): array
    {
        return Cache::lock('customer:connection-access:'.$customer->id, 180)->block(3, function () use ($customer, $disabled) {
            $customer->refresh()->load('onts', 'ont', 'package');
            $customer->forceFill(['connection_disabled' => $disabled, 'connection_access_pending' => true])->saveQuietly();
            $accounts = $this->accounts($customer);
            $warnings = [];
            $radiusOk = false;

            if ($this->radius->enabled()) {
                try {
                    if ($disabled) {
                        foreach (array_unique(array_values($accounts)) as $username) $this->radius->blockUser($username);
                    } else {
                        foreach ($customer->onts as $device) {
                            $copy = clone $customer;
                            $copy->setRelation('ont', $device);
                            $this->radius->syncCustomer($copy);
                        }
                    }
                    $radiusOk = true;
                } catch (Throwable $e) {
                    $warnings[] = 'Sinkronisasi RADIUS gagal.';
                    Log::warning('Customer access RADIUS sync failed', ['customer_id' => $customer->id]);
                }
            }

            $targets = $this->targets($customer, array_values($accounts), $warnings);
            if ($accounts && !$targets) $warnings[] = 'Router/NAS pelanggan belum dapat dipetakan.';
            $state = $customer->connection_disable_state ?: [];
            $localAccounts = [];
            foreach ($targets as [$nas, $port]) {
                $client = app(RouterOsApiClient::class);
                $localChecked = false;
                $nasLocalAccounts = [];
                try {
                    if (!$nas->apiHost() || !$nas->api_user || !$nas->api_password) {
                        throw new RuntimeException('API credentials unavailable');
                    }
                    $client->connect($nas->apiHost(), $port, $nas->api_user, (string) $nas->api_password, 1.5);
                    $nasLocalAccounts = $this->applyLocalAccounts($client, $customer, $nas, $port, $accounts, $disabled, $state);
                    $localAccounts += $nasLocalAccounts;
                    $localChecked = true;
                    if ($disabled) $this->removeSessions($client, $accounts);
                } catch (Throwable $e) {
                    Log::warning('Customer access router sync failed', ['customer_id' => $customer->id, 'nas_id' => $nas->id]);
                    // CoA can still end RADIUS sessions when RouterOS API is unavailable.
                    $coaOk = $disabled && $radiusOk && $localChecked && !$nasLocalAccounts;
                    if ($disabled && $radiusOk) {
                        foreach (array_unique(array_values($accounts)) as $username) {
                            try { if (!$this->radius->sendDisconnectToNas($nas, $username)) $coaOk = false; }
                            catch (Throwable $e) { $coaOk = false; }
                        }
                    }
                    if (!$coaOk) $warnings[] = 'Perubahan pada router/NAS #'.$nas->id.' belum terkonfirmasi. Periksa koneksi dan izin write API.';
                } finally {
                    $client->close();
                }
            }

            if ($disabled && !$radiusOk && array_diff(array_keys($accounts), array_keys($localAccounts))) {
                $warnings[] = 'Sebagian akun belum diblokir: RADIUS tidak tersedia dan akun lokal tidak ditemukan.';
            }

            $warnings = array_values(array_unique($warnings));
            $customer->forceFill([
                'connection_access_pending' => (bool) $warnings,
                'connection_disable_state' => $state ?: null,
            ])->saveQuietly();

            return ['complete' => !$warnings, 'warnings' => $warnings];
        });
    }

    private function accounts(Customer $customer): array
    {
        $accounts = [];
        foreach ($customer->onts as $device) {
            if ($device->pppoe_user) $accounts['ppp:'.$device->pppoe_user] = $device->pppoe_user;
            if ($device->hotspot_user) $accounts['hotspot:'.$device->hotspot_user] = $device->hotspot_user;
        }
        foreach (Voucher::where('customer_id', $customer->id)->where('is_free', true)->pluck('username') as $username) {
            if ($username) $accounts['hotspot:'.$username] = $username;
        }
        return $accounts;
    }

    private function targets(Customer $customer, array $usernames, array &$warnings): array
    {
        if (!$usernames && !$customer->connection_disable_state) return [];
        $targets = [];
        $routers = $customer->area_id ? Router::with('nas')->where('area_id', $customer->area_id)->whereNotNull('nas_id')->get() : collect();
        foreach ($routers as $router) {
            if ($router->nas) $targets[$router->nas_id] = [$router->nas, (int) ($router->api_port ?: 8728)];
        }
        if (!$targets && $usernames && $this->radius->enabled()) {
            try {
                $addresses = RadAcct::whereIn('username', $usernames)->distinct()->pluck('nasipaddress')->all();
                foreach (Nas::all() as $nas) {
                    if (array_intersect($addresses, $nas->accountingAddresses())) {
                        $port = Router::where('nas_id', $nas->id)->value('api_port');
                        $targets[$nas->id] = [$nas, (int) ($port ?: 8728)];
                    }
                }
            } catch (Throwable $e) { $warnings[] = 'Pemetaan NAS dari accounting belum tersedia.'; }
        }
        // Restore only devices changed by this feature, even after an area is reassigned.
        foreach ($customer->connection_disable_state ?: [] as $entry) {
            if (!isset($targets[$entry['nas_id']])) {
                $nas = Nas::find($entry['nas_id']);
                if ($nas) $targets[$nas->id] = [$nas, (int) $entry['port']];
                else $warnings[] = 'NAS yang sebelumnya dinonaktifkan sudah tidak tersedia.';
            }
        }
        return $targets;
    }

    private function applyLocalAccounts(RouterOsApiClient $client, Customer $customer, Nas $nas, int $port, array $accounts, bool $disabled, array &$state): array
    {
        $matched = [];
        foreach (['ppp' => '/ppp/secret', 'hotspot' => '/ip/hotspot/user'] as $type => $path) {
            $names = array_values(array_filter($accounts, fn ($key) => str_starts_with($key, $type.':'), ARRAY_FILTER_USE_KEY));
            foreach ($state as $entry) {
                if ($entry['nas_id'] == $nas->id && $entry['type'] === $type) $names[] = $entry['username'];
            }
            foreach (array_unique($names) as $username) {
                $rows = $client->command($path.'/print', ['?name' => $username, '.proplist' => '.id,name,disabled'])['rows'];
                foreach ($rows as $row) {
                    if (($row['name'] ?? null) !== $username || empty($row['.id'])) continue;
                    $matched[$type.':'.$username] = true;
                    $key = $nas->id.':'.$type.':'.$row['.id'];
                    $isDisabled = in_array($row['disabled'] ?? 'false', ['true', 'yes', '1'], true);
                    if ($disabled && !$isDisabled && in_array($username, $accounts, true)) {
                        // Save ownership before writing, so an interrupted request is recoverable.
                        $state[$key] = ['nas_id' => $nas->id, 'port' => $port, 'type' => $type, 'id' => $row['.id'], 'username' => $username];
                        $customer->forceFill(['connection_disable_state' => $state])->saveQuietly();
                        $client->command($path.'/set', ['.id' => $row['.id'], 'disabled' => 'yes']);
                    } elseif (!$disabled && isset($state[$key]) && $state[$key]['username'] === $username) {
                        // Never enable a local account that was already disabled beforehand.
                        if (in_array($username, $accounts, true)) $client->command($path.'/set', ['.id' => $row['.id'], 'disabled' => 'no']);
                        unset($state[$key]);
                    }
                }
                if (!$disabled) {
                    foreach ($state as $key => $entry) {
                        if ($entry['nas_id'] == $nas->id && $entry['type'] === $type && $entry['username'] === $username
                            && !in_array($entry['id'], array_column($rows, '.id'), true)) unset($state[$key]);
                    }
                }
            }
        }
        return $matched;
    }

    private function removeSessions(RouterOsApiClient $client, array $accounts): void
    {
        foreach (['ppp' => ['/ppp/active', 'name'], 'hotspot' => ['/ip/hotspot/active', 'user']] as $type => [$path, $field]) {
            foreach ($accounts as $key => $username) {
                if (!str_starts_with($key, $type.':')) continue;
                $rows = $client->command($path.'/print', ['?'.$field => $username, '.proplist' => '.id,'.$field])['rows'];
                foreach ($rows as $row) {
                    if (($row[$field] ?? null) === $username && !empty($row['.id'])) $client->command($path.'/remove', ['.id' => $row['.id']]);
                }
            }
        }
    }
}
