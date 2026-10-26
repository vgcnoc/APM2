<?php

namespace App\Services;

use App\Models\Radius\Nas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mengelola akun VPN per router (NAS) dan menyinkronkan
 * /etc/ppp/chap-secrets di server. pppd membaca chap-secrets setiap kali
 * ada autentikasi, jadi tidak perlu restart xl2tpd.
 *
 * Syarat di VPS: file chap-secrets harus bisa ditulis oleh www-data, mis.
 *   chgrp www-data /etc/ppp/chap-secrets && chmod 660 /etc/ppp/chap-secrets
 */
class VpnAccountService
{
    private const MARK_BEGIN = '# BEGIN APM2 MANAGED';
    private const MARK_END = '# END APM2 MANAGED';

    /**
     * Pastikan NAS punya akun VPN, IP tunnel tetap, dan akun API.
     */
    public function ensureAccount(Nas $nas): Nas
    {
        $this->prepareAccount($nas);
        if ($nas->isDirty()) $nas->save();
        $this->sync();
        return $nas;
    }

    /** Prepare only; callers hold the NAS configuration lock before allocating/saving. */
    public function prepareAccount(Nas $nas): Nas
    {

        if (!$nas->vpn_user) {
            $nas->vpn_user = 'apm2-' . Str::lower(Str::random(8));
        }
        if (!$nas->vpn_password) {
            $nas->vpn_password = Str::random(16);
        }
        if (!$nas->vpn_ip) {
            $nas->vpn_ip = $this->nextFreeIp();
        }
        if (!$nas->api_user) {
            $nas->api_user = 'APM2' . Str::upper(Str::random(6));
        }
        if (!$nas->api_password) {
            $nas->api_password = Str::random(20);
        }

        return $nas;
    }

    /**
     * Reset kredensial VPN/API (dipakai saat "Generate Ulang (Force)").
     */
    public function regenerate(Nas $nas): Nas
    {
        $nas->vpn_password = Str::random(16);
        $nas->api_password = Str::random(20);
        $nas->save();

        $this->sync();

        return $nas;
    }

    public function nextFreeIp(): string
    {
        $start = ip2long(config('radius.vpn.pool_start'));
        $end = ip2long(config('radius.vpn.pool_end'));
        if ($start === false || $end === false || $end < $start || $end - $start > 65535) {
            throw new \RuntimeException('Konfigurasi pool IP VPN tidak valid.');
        }

        $used = Nas::pluck('vpn_ip')->merge(Nas::pluck('nasname'))->filter()
            ->map(fn ($ip) => ip2long(\App\Support\NasAddress::ip($ip) ?? ''))
            ->filter(fn ($ip) => $ip !== false)
            ->flip();

        for ($i = $start; $i <= $end; $i++) {
            if (!isset($used[$i])) {
                return long2ip($i);
            }
        }

        throw new \RuntimeException('Pool IP VPN habis, perbesar RADIUS_VPN_POOL_END.');
    }

    /**
     * Tulis ulang blok terkelola APM2 di chap-secrets, entri manual lain dipertahankan.
     */
    public function sync(): bool
    {
        if (!config('radius.vpn.enabled')) {
            return false;
        }

        $path = config('radius.vpn.chap_secrets');

        if (!is_file($path) || !is_writable($path)) {
            Log::warning("VPN: {$path} tidak ada / tidak bisa ditulis, akun VPN tidak tersinkron.");
            return false;
        }

        $accounts = Nas::whereNotNull('vpn_user')->whereNotNull('vpn_password')->whereNotNull('vpn_ip')->orderBy('id')->get();
        $lines = [self::MARK_BEGIN];
        $accounts->each(function (Nas $n) use (&$lines) {
                $lines[] = sprintf('"%s" * "%s" %s', $n->vpn_user, $n->vpn_password, $n->vpn_ip);
        });
        $lines[] = self::MARK_END;
        $block = implode("\n", $lines);

        if (!$this->writeManagedBlock($path, $block)) {
            return false;
        }

        return $this->syncBackup($accounts);
    }

    /** Data cadangan yang boleh diberikan ke generator script NAS. */
    public function backupClientConfig(): array
    {
        $enabled = (bool) config('radius.vpn.backup.enabled');
        $l2tpEnabled = $enabled && (bool) config('radius.vpn.backup.l2tp_enabled');

        return [
            'enabled' => $enabled,
            'l2tp_enabled' => $l2tpEnabled,
            'endpoint' => trim((string) config('radius.vpn.backup.endpoint')),
            // PSK hanya dikirim ke generator bila L2TP memang diaktifkan.
            'ipsec_secret' => $l2tpEnabled ? $this->backupIpsecSecret() : null,
        ];
    }

    private function backupIpsecSecret(): ?string
    {
        $file = (string) config('radius.vpn.backup.ipsec_secrets_file');
        if ($file === '' || !is_readable($file)) {
            return null;
        }

        $content = (string) file_get_contents($file);
        return preg_match('/\\bPSK\\s+"([^"\\r\\n]+)"/i', $content, $matches)
            ? $matches[1]
            : null;
    }

    private function syncBackup(iterable $accounts): bool
    {
        if (!config('radius.vpn.backup.enabled')) {
            return true;
        }

        $host = trim((string) config('radius.vpn.backup.ssh_host'));
        $user = trim((string) config('radius.vpn.backup.ssh_user'));
        $endpoint = trim((string) config('radius.vpn.backup.endpoint'));
        $key = (string) config('radius.vpn.backup.ssh_key');
        $knownHosts = (string) config('radius.vpn.backup.ssh_known_hosts');
        $port = (int) config('radius.vpn.backup.ssh_port');
        $binary = (string) config('radius.vpn.backup.ssh_binary');

        if ($host === '' || $user === '' || $endpoint === '' || !is_file($key) || !is_file($knownHosts) || $port < 1 || $port > 65535 || !$this->backupIpsecSecret()) {
            Log::warning('VPN backup belum siap: endpoint, SSH key, known_hosts, atau PSK IPsec tidak valid.');
            return false;
        }

        $payload = json_encode([
            'accounts' => collect($accounts)->map(fn (Nas $nas) => [
                'username' => $nas->vpn_user,
                'password' => $nas->vpn_password,
                'ip' => $nas->vpn_ip,
            ])->values()->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $command = implode(' ', array_map('escapeshellarg', [
            $binary, '-i', $key, '-p', (string) $port,
            '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes',
            '-o', 'StrictHostKeyChecking=yes', '-o', "UserKnownHostsFile={$knownHosts}",
            "{$user}@{$host}",
        ]));

        try {
            $result = Process::timeout(20)->input($payload)->run($command);
            if ($result->successful()) {
                return true;
            }
            Log::warning('Sinkronisasi akun VPN ke VPS cadangan gagal.', ['exit_code' => $result->exitCode()]);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Sinkronisasi akun VPN ke VPS cadangan tidak dapat dijalankan.');
        }

        return false;
    }

    private function writeManagedBlock(string $path, string $block): bool
    {

        $handle = fopen($path, 'r+');
        if (!$handle || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) fclose($handle);
            return false;
        }
        $content = (string) stream_get_contents($handle);
        $pattern = '/' . preg_quote(self::MARK_BEGIN, '/') . '.*?' . preg_quote(self::MARK_END, '/') . '/s';

        $content = preg_match($pattern, $content)
            ? preg_replace_callback($pattern, fn () => $block, $content)
            : rtrim($content) . "\n\n" . $block . "\n";

        try {
            rewind($handle);
            $written = fwrite($handle, $content);
            if ($written !== strlen($content)) return false;
            ftruncate($handle, $written);
            fflush($handle);
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
