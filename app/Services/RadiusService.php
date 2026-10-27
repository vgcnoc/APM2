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

        $shared = max(1, (int) ($package->shared_device ?? 1));

        $this->writeGroup($this->packageGroup($package->id), [
            'Mikrotik-Rate-Limit' => $rateLimit,
            'Mikrotik-Group' => $package->is_mikrotik_group_custom ? $package->mikrotik_group : null,
            'Mikrotik-Address-List' => $package->is_mikrotik_address_list_custom ? $package->mikrotik_address_list : null,
            'Acct-Interim-Interval' => config('radius.interim_interval', 60),
            'Port-Limit' => $shared,
        ], [
            'Simultaneous-Use' => $shared,
        ]);
    }

    public function syncVoucherProfile(VoucherProfile $profile): void
    {
        $sharedUsers = $profile->sessionLimit();
        $durationSeconds = self::parseDuration($profile->duration);

        // 1. Check Items (FreeRADIUS Layer)
        $checks = [
            'Simultaneous-Use' => $sharedUsers,
        ];

        if (!$profile->is_customer_free && $profile->duration !== 'Mengikuti Pelanggan' && $durationSeconds > 0) {
            $checks['Max-All-Session'] = $durationSeconds;
        }

        // 2. Reply Items (MikroTik RouterOS & Hotspot Layer)
        $replies = [
            // Standard RFC 2865 attribute: MikroTik Hotspot uses Port-Limit as strict shared-users per login!
            'Port-Limit' => $sharedUsers,
            'Acct-Interim-Interval' => config('radius.interim_interval', 60),
        ];

        // Rate Limit (Upload/Download bandwidth queue)
        if (!empty($profile->limit_rate)) {
            $replies['Mikrotik-Rate-Limit'] = trim($profile->limit_rate);
        }

        // Session Timeout (Countdown timer on MikroTik Hotspot session)
        if (!$profile->is_customer_free && $profile->duration !== 'Mengikuti Pelanggan' && $durationSeconds > 0) {
            $replies['Session-Timeout'] = $durationSeconds;
        }

        $this->writeGroup($this->voucherGroup($profile->id), $replies, $checks);
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

    public function blockUser(string $username): void
    {
        $this->db()->transaction(function () use ($username) {
            $this->removeUser($username);
            RadCheck::create(['username' => $username, 'attribute' => 'Auth-Type', 'op' => ':=', 'value' => 'Reject']);
        });
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

        // Akun hotspot aktif jika paket bertipe hotspot atau fitur free_hotspot aktif atau access_mode HOTSPOT/MEMBER
        $isHotspotPackage = strtolower($customer->package?->access_mode ?? '') === 'hotspot';
        $isHotspotMode = in_array(strtoupper($ont->access_mode ?? ''), ['HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER']);
        if (($ont->free_hotspot || $isHotspotPackage || $isHotspotMode) && $ont->hotspot_user && $ont->hotspot_password) {
            $accounts[$ont->hotspot_user] = $ont->hotspot_password;
        }

        return $accounts;
    }

    /**
     * Sinkronkan akun pelanggan berdasarkan status & paketnya.
     * PPPoE / Hotspot mengikuti paket langganan / isolir.
     * Hotspot gratis mengikuti VoucherProfile khusus (is_customer_free) dan aktif selama status pelanggan active.
     *
     * @param string[] $staleUsernames username lama yang harus dihapus (mis. setelah rename)
     */
    public function syncCustomer(Customer $customer, array $staleUsernames = []): void
    {
        $customer->loadMissing('ont', 'package');
        $accounts = $this->customerAccounts($customer);

        foreach ($staleUsernames as $old) {
            if ($old && !array_key_exists($old, $accounts)) {
                $this->removeUser($old);
                $this->disconnect($old);
            }
        }

        $ont = $customer->ont;

        // Manual disable is independent of billing/isolir and survives every resync.
        if ($customer->connection_disabled) {
            foreach ($customer->onts as $device) {
                foreach (array_filter([$device->pppoe_user, $device->hotspot_user]) as $username) {
                    $this->blockUser($username);
                }
            }
            foreach (Voucher::where('customer_id', $customer->id)->where('is_free', true)->get() as $voucher) {
                if ($voucher->username) $this->blockUser($voucher->username);
            }
            return;
        }

        $targetGroup = match (true) {
            $customer->status === 'suspended' || $customer->service_status === 'isolir' => $this->isolirGroup(),
            $customer->status === 'active' && $customer->service_status !== 'isolir' && $customer->package_id => $this->packageGroup($customer->package_id),
            default => null,
        };

        $isHotspotPackage = strtolower($customer->package?->access_mode ?? '') === 'hotspot';

        // 1. Sinkronkan akun PPPoE / Primary Connection
        if ($ont && $ont->pppoe_user && $ont->pppoe_password) {
            if ($targetGroup) {
                $this->upsertUser($ont->pppoe_user, $ont->pppoe_password, $targetGroup);
            } else {
                $this->removeUser($ont->pppoe_user);
                $this->disconnect($ont->pppoe_user);
            }
        }

        // 2. Sinkronkan akun Hotspot
        if ($ont && $ont->hotspot_user && $ont->hotspot_password) {
            if ($isHotspotPackage) {
                // Pelanggan paket Mode Hotspot Berlangganan:
                // Akun hotspot masuk ke group paket pelanggan (pkg_{id}), mengikuti Port-Limit & Simultaneous-Use sesuai shared_device!
                if ($targetGroup) {
                    $this->upsertUser($ont->hotspot_user, $ont->hotspot_password, $targetGroup);
                } else {
                    $this->removeUser($ont->hotspot_user);
                    $this->disconnect($ont->hotspot_user);
                }
            } elseif ($ont->free_hotspot) {
                // Pelanggan dengan add-on Hotspot Gratis (1 user, profile gratis)
                $freeProfile = \App\Models\VoucherProfile::where('is_customer_free', true)->first();
                if (!$freeProfile) {
                    $freeProfile = \App\Models\VoucherProfile::firstOrCreate(
                        ['name' => 'Hotspot Gratis Pelanggan'],
                        [
                            'price' => 0,
                            'fee_admin' => 0,
                            'fee_reseller' => 0,
                            'fee_partner' => 0,
                            'duration' => 'Mengikuti Pelanggan',
                            'limit_rate' => '5M/5M',
                            'shared_users' => 1,
                            'description' => 'Voucher khusus gratis 1 user hotspot saat aktivasi pelanggan (masa aktif mengikuti data pelanggan)',
                            'is_customer_free' => true,
                        ]
                    );
                }

                if ($customer->status === 'active') {
                    $this->upsertUser($ont->hotspot_user, $ont->hotspot_password, $this->voucherGroup($freeProfile->id));

                    // Pastikan voucher record tersimpan di tabel vouchers
                    \App\Models\Voucher::updateOrCreate(
                        [
                            'customer_id' => $customer->id,
                            'is_free' => true,
                        ],
                        [
                            'voucher_profile_id' => $freeProfile->id,
                            'code' => $ont->hotspot_user,
                            'username' => $ont->hotspot_user,
                            'password' => $ont->hotspot_password,
                            'status' => 'used',
                            'is_active' => true,
                            'payment_method' => 'free',
                            'used_at' => $customer->activation_date ?: now(),
                        ]
                    );
                } else {
                    // Pelanggan suspend/isolir atau tidak aktif -> nonaktifkan hotspot gratis
                    $this->removeUser($ont->hotspot_user);
                    $this->disconnect($ont->hotspot_user);

                    \App\Models\Voucher::where('customer_id', $customer->id)
                        ->where('is_free', true)
                        ->update([
                            'is_active' => false,
                            'status' => $customer->status === 'suspended' ? 'used' : 'expired',
                        ]);
                }
            } else {
                // Free hotspot tidak aktif & bukan paket hotspot -> bersihkan
                $this->removeUser($ont->hotspot_user);
                $this->disconnect($ont->hotspot_user);
                \App\Models\Voucher::where('customer_id', $customer->id)->where('is_free', true)->delete();
            }
        }
    }

    public function removeCustomer(Customer $customer): void
    {
        foreach (array_keys($this->customerAccounts($customer)) as $username) {
            $this->removeUser($username);
            $this->disconnect($username);
        }
        \App\Models\Voucher::where('customer_id', $customer->id)->delete();
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
            if ($voucher->customer_id && $voucher->customer?->connection_disabled) {
                $this->blockUser($voucher->username);
                continue;
            }
            if ($voucher->status === 'expired' || !$voucher->is_active) {
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
                $session->acctstoptime = now();
                $session->acctterminatecause = 'Admin-Reset';
                $session->save();
                $count++;
            }
        }

        // Jika berhasil disconnect, bersihkan sisa sesi stale/ghost untuk user ini di RadAcct
        if ($count > 0) {
            RadAcct::online()->where('username', $username)->update([
                'acctstoptime' => now(),
                'acctterminatecause' => 'Admin-Reset',
            ]);
        }

        // Jika tidak ada sesi di RadAcct atau belum berhasil, coba kirim ke NAS router terdaftar
        if ($count === 0) {
            $nases = Nas::whereNotNull('vpn_ip')
                ->orWhere('nasname', '!=', '0.0.0.0/0')
                ->get();
            foreach ($nases as $nas) {
                if ($this->sendDisconnectToNas($nas, $username)) {
                    $count++;
                    RadAcct::online()->where('username', $username)->update([
                        'acctstoptime' => now(),
                        'acctterminatecause' => 'Admin-Reset'
                    ]);
                }
            }
        }

        return $count;
    }

    public function disconnectSession(RadAcct $session): bool
    {
        // 1. Cari NAS berdasarkan nasname atau vpn_ip
        $nas = Nas::where('nasname', $session->nasipaddress)
            ->orWhere('vpn_ip', $session->nasipaddress)
            ->first();

        if (!$nas) {
            Log::warning("[RADIUS] NAS {$session->nasipaddress} tidak terdaftar, tidak bisa disconnect {$session->username}");
            return false;
        }

        // Tentukan IP tujuan pengiriman paket CoA (jangan kirim ke 0.0.0.0/0)
        $targetIp = $session->nasipaddress;
        if (!$targetIp || $targetIp === '0.0.0.0' || str_contains($targetIp, '/')) {
            $targetIp = $nas->vpn_ip ?: $nas->nasname;
        }

        if (!$targetIp || str_contains($targetIp, '/')) {
            Log::warning("[RADIUS] IP tujuan NAS tidak valid ({$targetIp}), tidak bisa disconnect {$session->username}");
            return false;
        }

        return $this->sendDisconnectToNas($nas, $session->username, $session->framedipaddress, $targetIp);
    }

    /**
     * Kirim paket Disconnect-Request ke NAS via radclient
     */
    public function sendDisconnectToNas(Nas $nas, string $username, ?string $framedIp = null, ?string $targetIp = null): bool
    {
        $ip = $targetIp ?: ($nas->vpn_ip ?: $nas->nasname);
        if (!$ip || $ip === '0.0.0.0' || str_contains($ip, '/')) {
            return false;
        }

        $port = $nas->coa_port ?: 3799;
        $secret = $nas->secret;
        $radclient = config('radius.radclient', '/usr/bin/radclient');

        // CATATAN PENTING UNTUK MIKROTIK ROUTEROS:
        // Jangan sertakan Acct-Session-Id! MikroTik RouterOS akan merespons
        // dengan "Disconnect-NAK: Error-Cause = Unsupported-Extension".
        // Gunakan User-Name dan (opsional) Framed-IP-Address.
        $attrs = "User-Name = \"" . addslashes($username) . "\"\n";
        if ($framedIp) {
            $attrs .= "Framed-IP-Address = {$framedIp}\n";
        }

        $result = Process::timeout(10)->input($attrs)->run([
            $radclient, '-r', '2', '-t', '3',
            "{$ip}:{$port}",
            'disconnect', $secret,
        ]);

        $ok = $result->successful() && str_contains($result->output(), 'Disconnect-ACK');

        // Jika kombinasi gagal, coba kirim kembali hanya dengan User-Name
        if (!$ok && $framedIp) {
            $retryAttrs = "User-Name = \"" . addslashes($username) . "\"\n";
            $retryResult = Process::timeout(10)->input($retryAttrs)->run([
                $radclient, '-r', '2', '-t', '3',
                "{$ip}:{$port}",
                'disconnect', $secret,
            ]);
            $ok = $retryResult->successful() && str_contains($retryResult->output(), 'Disconnect-ACK');
        }

        if (!$ok) {
            Log::warning("[RADIUS] Disconnect {$username} pada NAS {$ip}:{$port} gagal: " . trim($result->output() . ' ' . $result->errorOutput()));
        } else {
            Log::info("[RADIUS] Disconnect {$username} pada NAS {$ip}:{$port} BERHASIL (Disconnect-ACK).");
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
     * Ubah durasi format MikroTik ("30m", "1h", "1d12h", "1w") atau teks Indonesia ("1 jam", "30 menit", "1 hari") ke detik.
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

        // Dukung padanan kata Indonesia
        $duration = str_replace(
            ['minggu', 'hari', 'jam', 'menit', 'detik', 'bulan'],
            ['w', 'd', 'h', 'm', 's', '30d'],
            $duration
        );

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

    /**
     * Audit seluruh sesi online di RadAcct.
     * Jika sesi aktif untuk suatu username melebihi Port-Limit / Simultaneous-Use yang diizinkan (misal diset 5 tapi ada 6 sesi),
     * putuskan sesi yang melebihi batas via paket CoA Disconnect.
     */
    public function auditSimultaneousSessions(): int
    {
        try {
            $onlineSessions = RadAcct::online()->get()->groupBy('username');
            if ($onlineSessions->isEmpty()) {
                return 0;
            }

            $kicked = 0;
            foreach ($onlineSessions as $username => $sessions) {
                // Cari group user dari RadUserGroup
                $userGroup = RadUserGroup::where('username', $username)->first();
                if (!$userGroup) {
                    continue;
                }

                // Cari batas limit simultan dari RadGroupReply (Port-Limit) atau RadGroupCheck (Simultaneous-Use)
                $limitAttr = RadGroupReply::where('groupname', $userGroup->groupname)
                    ->where('attribute', 'Port-Limit')
                    ->first();

                if (!$limitAttr) {
                    $limitAttr = RadGroupCheck::where('groupname', $userGroup->groupname)
                        ->where('attribute', 'Simultaneous-Use')
                        ->first();
                }

                $maxLimit = $limitAttr ? (int) $limitAttr->value : 1;
                if ($maxLimit <= 0) {
                    $maxLimit = 1;
                }

                if ($sessions->count() > $maxLimit) {
                    // Ada sesi melebihi kuota! Urutkan sesi dari yang terbaru ke terlama
                    $sorted = $sessions->sortByDesc('acctstarttime');
                    $excess = $sorted->slice($maxLimit);

                    foreach ($excess as $session) {
                        $this->disconnectSession($session);
                        $session->acctstoptime = now();
                        $session->acctterminatecause = 'Simultaneous-Limit-Exceeded';
                        $session->save();
                        $kicked++;
                    }
                }
            }

            return $kicked;
        } catch (\Throwable $e) {
            Log::warning("[RADIUS] Audit simultaneous sessions error: " . $e->getMessage());
            return 0;
        }
    }
}
