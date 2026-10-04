<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Voucher;
use App\Models\VoucherProfile;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadGroupCheck;
use App\Models\Radius\RadGroupReply;
use App\Models\Radius\RadReply;
use App\Models\Radius\RadUserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Jembatan antara data aplikasi (paket, pelanggan, voucher) dan tabel FreeRADIUS.
 *
 * Pemetaan:
 *  - InternetPackage  -> group "pkg_{id}"  (radgroupreply / radgroupcheck)
 *  - VoucherProfile   -> group "vcp_{id}"
 *  - Customer (ONT pppoe_user / hotspot_user) -> radcheck + radusergroup
 *  - Voucher          -> radcheck + radusergroup
 *  - Customer suspended -> group isolir
 */
class RadiusService
{
    // Status pelanggan yang boleh login (selain ini user dihapus dari RADIUS)
    public const LOGIN_STATUSES = ['active', 'suspended'];

    public function enabled(): bool
    {
        return (bool) config('radius.enabled');
    }

    /**
     * Jalankan callback hanya jika RADIUS aktif. Error dicatat ke log
     * agar operasi utama aplikasi tidak ikut gagal.
     */
    public function guard(callable $callback, mixed $default = null): mixed
    {
        if (!$this->enabled()) {
            return $default;
        }

        try {
            return $callback($this);
        } catch (\Throwable $e) {
            Log::error('[RADIUS] ' . $e->getMessage(), ['exception' => $e]);
            return $default;
        }
    }

    protected function db()
    {
        return DB::connection(config('radius.connection', 'radius'));
    }

    // ── Nama Group ─────────────────────────────────────────────

    public function packageGroup(int $packageId): string
    {
        return config('radius.group_prefix.package', 'pkg_') . $packageId;
    }

    public function voucherGroup(int $profileId): string
    {
        return config('radius.group_prefix.voucher', 'vcp_') . $profileId;
    }

    public function isolirGroup(): string
    {
        return config('radius.isolir.group', 'isolir');
    }

    // ── Group (Paket / Profil Voucher / Isolir) ────────────────

    /**
     * Tulis ulang atribut sebuah group.
     *
     * @param array<string,string|int|null> $replies  atribut reply  (op :=)
     * @param array<string,string|int|null> $checks   atribut check  (op :=)
     */
    public function writeGroup(string $group, array $replies, array $checks = []): void
    {
        $this->db()->transaction(function () use ($group, $replies, $checks) {
            RadGroupReply::where('groupname', $group)->delete();
            RadGroupCheck::where('groupname', $group)->delete();

            foreach (array_filter($replies, fn ($v) => $v !== null && $v !== '') as $attr => $value) {
                RadGroupReply::create(['groupname' => $group, 'attribute' => $attr, 'op' => ':=', 'value' => (string) $value]);
            }
            foreach (array_filter($checks, fn ($v) => $v !== null && $v !== '') as $attr => $value) {
                RadGroupCheck::create(['groupname' => $group, 'attribute' => $attr, 'op' => ':=', 'value' => (string) $value]);
            }
        });
    }

    public function removeGroup(string $group): void
    {
        RadGroupReply::where('groupname', $group)->delete();
        RadGroupCheck::where('groupname', $group)->delete();
    }

    public function syncPackage(InternetPackage $package): void
    {
        $rateLimit = $package->rate_limit;
        if (!$rateLimit && $package->speed_mbps > 0) {
            $rateLimit = "{$package->speed_mbps}M/{$package->speed_mbps}M";
        }

        $this->writeGroup($this->packageGroup($package->id), [
            'Mikrotik-Rate-Limit' => $rateLimit,
            'Mikrotik-Group' => $package->is_mikrotik_group_custom ? $package->mikrotik_group : null,
            'Mikrotik-Address-List' => $package->is_mikrotik_address_list_custom ? $package->mikrotik_address_list : null,
        ], [
            'Simultaneous-Use' => $package->shared_device > 0 ? $package->shared_device : null,
        ]);
    }

    public function syncVoucherProfile(VoucherProfile $profile): void
    {
        $this->writeGroup($this->voucherGroup($profile->id), [
            'Mikrotik-Rate-Limit' => $profile->limit_rate,
        ], [
            'Simultaneous-Use' => $profile->shared_users > 0 ? $profile->shared_users : null,
            // Masa berlaku dihitung sejak login pertama (sqlcounter "accessperiod")
            'Access-Period' => self::parseDuration($profile->duration),
        ]);
    }

    public function syncIsolirGroup(): void
    {
        $this->writeGroup($this->isolirGroup(), [
            'Mikrotik-Address-List' => config('radius.isolir.address_list'),
            'Mikrotik-Rate-Limit' => config('radius.isolir.rate_limit'),
            'Framed-Pool' => config('radius.isolir.pool'),
        ]);
    }

    // ── User ───────────────────────────────────────────────────

    public function upsertUser(string $username, string $password, string $group): void
    {
        $this->db()->transaction(function () use ($username, $password, $group) {
            RadCheck::where('username', $username)->delete();
            RadUserGroup::where('username', $username)->delete();

            RadCheck::create(['username' => $username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $password]);
            RadUserGroup::create(['username' => $username, 'groupname' => $group, 'priority' => 1]);
        });
    }

    public function removeUser(?string $username): void
    {
        if (!$username) {
            return;
        }
        RadCheck::where('username', $username)->delete();
        RadReply::where('username', $username)->delete();
        RadUserGroup::where('username', $username)->delete();
    }

    /**
     * Akun RADIUS milik pelanggan (diambil dari data ONT).
     *
     * @return array<string,string> username => password
     */
    public function customerAccounts(Customer $customer): array
    {
        $ont = $customer->ont;
        if (!$ont) {
            return [];
        }

        $accounts = [];
        if ($ont->pppoe_user && $ont->pppoe_password) {
            $accounts[$ont->pppoe_user] = $ont->pppoe_password;
        }
        if ($ont->free_hotspot && $ont->hotspot_user && $ont->hotspot_password) {
            $accounts[$ont->hotspot_user] = $ont->hotspot_password;
        }

        return $accounts;
    }

    /**
     * Sinkronkan akun pelanggan berdasarkan status & paketnya.
     *
     * @param string[] $staleUsernames username lama yang harus dihapus (mis. setelah rename)
     */
    public function syncCustomer(Customer $customer, array $staleUsernames = []): void
    {
        $customer->loadMissing('ont');
        $accounts = $this->customerAccounts($customer);

        foreach ($staleUsernames as $old) {
            if ($old && !array_key_exists($old, $accounts)) {
                $this->removeUser($old);
            }
        }

        $group = match (true) {
            $customer->status === 'suspended' => $this->isolirGroup(),
            $customer->status === 'active' && $customer->package_id => $this->packageGroup($customer->package_id),
            default => null,
        };

        foreach ($accounts as $username => $password) {
            if ($group) {
                $this->upsertUser($username, $password, $group);
            } else {
                $this->removeUser($username);
            }
        }
    }

    public function removeCustomer(Customer $customer): void
    {
        foreach (array_keys($this->customerAccounts($customer)) as $username) {
            $this->removeUser($username);
        }
    }

    /**
     * @param iterable<Voucher> $vouchers
     */
    public function syncVouchers(iterable $vouchers): void
    {
        foreach ($vouchers as $voucher) {
            if (!$voucher->username) {
                continue;
            }
            if ($voucher->status === 'expired') {
                $this->removeUser($voucher->username);
                continue;
            }
            $this->upsertUser(
                $voucher->username,
                (string) ($voucher->password ?: $voucher->username),
                $this->voucherGroup($voucher->voucher_profile_id)
            );
        }
    }

    // ── Sesi & NAS ─────────────────────────────────────────────

    /**
     * Putuskan semua sesi aktif user (Disconnect-Request ke NAS, port CoA).
     * User akan reconnect dan mendapat atribut terbaru (paket baru / isolir).
     */
    public function disconnect(string $username): int
    {
        $sessions = RadAcct::online()->where('username', $username)->get();
        $count = 0;

        foreach ($sessions as $session) {
            if ($this->disconnectSession($session)) {
                $count++;
            }
        }

        return $count;
    }

    public function disconnectSession(RadAcct $session): bool
    {
        $nas = Nas::where('nasname', $session->nasipaddress)->first();
        if (!$nas) {
            Log::warning("[RADIUS] NAS {$session->nasipaddress} tidak terdaftar, tidak bisa disconnect {$session->username}");
            return false;
        }

        $attrs = sprintf(
            "User-Name = \"%s\"\nAcct-Session-Id = \"%s\"\n",
            addslashes($session->username),
            addslashes($session->acctsessionid)
        );
        if ($session->framedipaddress) {
            $attrs .= "Framed-IP-Address = {$session->framedipaddress}\n";
        }

        $result = Process::timeout(15)->input($attrs)->run([
            config('radius.radclient'), '-r', '2', '-t', '3',
            $nas->nasname . ':' . ($nas->coa_port ?: 3799),
            'disconnect', $nas->secret,
        ]);

        $ok = $result->successful() && str_contains($result->output(), 'Disconnect-ACK');
        if (!$ok) {
            Log::warning("[RADIUS] Disconnect {$session->username} gagal: " . trim($result->output() . ' ' . $result->errorOutput()));
        }

        return $ok;
    }

    /**
     * Restart FreeRADIUS (diperlukan agar perubahan tabel "nas" terbaca).
     *
     * @return array{0: bool, 1: string}
     */
    public function restartServer(): array
    {
        $result = Process::timeout(30)->run(config('radius.restart_command'));

        return [$result->successful(), trim($result->output() . ' ' . $result->errorOutput())];
    }

    // ── Full Sync ──────────────────────────────────────────────

    /**
     * Sinkron ulang seluruh data ke RADIUS (dipakai saat setup awal / perbaikan).
     *
     * @return array<string,int>
     */
    public function syncAll(): array
    {
        $stats = ['packages' => 0, 'voucher_profiles' => 0, 'customers' => 0, 'vouchers' => 0];

        $this->syncIsolirGroup();

        foreach (InternetPackage::all() as $package) {
            $this->syncPackage($package);
            $stats['packages']++;
        }

        foreach (VoucherProfile::all() as $profile) {
            $this->syncVoucherProfile($profile);
            $stats['voucher_profiles']++;
        }

        Customer::with('ont')->whereHas('ont')->chunkById(200, function ($customers) use (&$stats) {
            foreach ($customers as $customer) {
                $this->syncCustomer($customer);
                $stats['customers']++;
            }
        });

        Voucher::whereNotNull('username')->chunkById(500, function ($vouchers) use (&$stats) {
            $this->syncVouchers($vouchers);
            $stats['vouchers'] += $vouchers->count();
        });

        return $stats;
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Ubah durasi format MikroTik ("30m", "1h", "1d12h", "1w") ke detik.
     */
    public static function parseDuration(?string $duration): ?int
    {
        if (!$duration) {
            return null;
        }

        $duration = strtolower(trim($duration));
        if (ctype_digit($duration)) {
            return (int) $duration; // angka polos dianggap detik
        }

        preg_match_all('/(\d+)\s*([wdhms])/', $duration, $matches, PREG_SET_ORDER);
        if (!$matches) {
            return null;
        }

        $units = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];
        $seconds = 0;
        foreach ($matches as [, $value, $unit]) {
            $seconds += (int) $value * $units[$unit];
        }

        return $seconds ?: null;
    }
}
