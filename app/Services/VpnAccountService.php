<?php

namespace App\Services;

use App\Models\Radius\Nas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mengelola akun VPN L2TP per router (NAS) dan menyinkronkan
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
        $dirty = false;

        if (!$nas->vpn_user) {
            $nas->vpn_user = 'apm2-' . Str::lower(Str::random(8));
            $dirty = true;
        }
        if (!$nas->vpn_password) {
            $nas->vpn_password = Str::random(16);
            $dirty = true;
        }
        if (!$nas->vpn_ip) {
            $nas->vpn_ip = $this->nextFreeIp();
            $dirty = true;
        }
        if (!$nas->api_user) {
            $nas->api_user = 'APM2' . Str::upper(Str::random(6));
            $dirty = true;
        }
        if (!$nas->api_password) {
            $nas->api_password = Str::random(20);
            $dirty = true;
        }

        if ($dirty) {
            $nas->save();
        }

        $this->sync();

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

        $used = Nas::whereNotNull('vpn_ip')->pluck('vpn_ip')
            ->map(fn ($ip) => ip2long($ip))
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

        $lines = [self::MARK_BEGIN];
        Nas::whereNotNull('vpn_user')->whereNotNull('vpn_ip')->orderBy('id')->get()
            ->each(function (Nas $n) use (&$lines) {
                $lines[] = sprintf('"%s" * "%s" %s', $n->vpn_user, $n->vpn_password, $n->vpn_ip);
            });
        $lines[] = self::MARK_END;
        $block = implode("\n", $lines);

        $content = (string) file_get_contents($path);
        $pattern = '/' . preg_quote(self::MARK_BEGIN, '/') . '.*?' . preg_quote(self::MARK_END, '/') . '/s';

        $content = preg_match($pattern, $content)
            ? preg_replace($pattern, $block, $content)
            : rtrim($content) . "\n\n" . $block . "\n";

        return file_put_contents($path, $content, LOCK_EX) !== false;
    }
}
