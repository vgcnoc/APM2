<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integrasi FreeRADIUS
    |--------------------------------------------------------------------------
    | Jika dinonaktifkan, aplikasi tidak akan menulis apa pun ke tabel RADIUS
    | (berguna untuk development lokal tanpa FreeRADIUS).
    */
    'enabled' => env('RADIUS_ENABLED', false),

    // Nama koneksi database yang berisi tabel FreeRADIUS (lihat config/database.php)
    'connection' => env('RADIUS_DB_CONNECTION', 'radius'),

    // IP publik server FreeRADIUS (dipakai untuk generate script MikroTik)
    'server_ip' => env('RADIUS_SERVER_IP', '127.0.0.1'),
    'auth_port' => (int) env('RADIUS_AUTH_PORT', 1812),
    'acct_port' => (int) env('RADIUS_ACCT_PORT', 1813),

    // Binary radclient (paket freeradius-utils) untuk kirim Disconnect/CoA
    'radclient' => env('RADIUS_RADCLIENT_BIN', '/usr/bin/radclient'),

    // Perintah untuk restart FreeRADIUS setelah NAS berubah (butuh sudoers untuk www-data)
    'restart_command' => env('RADIUS_RESTART_COMMAND', 'sudo /usr/bin/systemctl restart freeradius'),

    // Interval (detik) NAS mengirim Accounting Interim-Update (traffic & uptime real-time)
    'interim_interval' => (int) env('RADIUS_INTERIM_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Group Isolir
    |--------------------------------------------------------------------------
    | Pelanggan berstatus "suspended" dipindah ke group ini.
    */
    'isolir' => [
        'group' => env('RADIUS_ISOLIR_GROUP', 'isolir'),
        'address_list' => env('RADIUS_ISOLIR_ADDRESS_LIST', 'APM2ISOLIR'),
        'rate_limit' => env('RADIUS_ISOLIR_RATE_LIMIT', '512k/512k'),
        'pool' => env('RADIUS_ISOLIR_POOL'), // opsional: Framed-Pool khusus isolir
    ],

    /*
    |--------------------------------------------------------------------------
    | VPN Tunnel (L2TP) Router -> Server
    |--------------------------------------------------------------------------
    | Router client terhubung ke VPS via L2TP. RADIUS & API diakses lewat IP
    | tunnel, sehingga router tanpa IP publik tetap bisa dikelola (CoA/API).
    */
    'vpn' => [
        'enabled' => env('RADIUS_VPN_ENABLED', env('RADIUS_ENABLED', false)),
        'gateway' => env('RADIUS_VPN_GATEWAY', '10.9.0.1'),      // local ip xl2tpd
        'pool_start' => env('RADIUS_VPN_POOL_START', '10.9.0.10'),
        'pool_end' => env('RADIUS_VPN_POOL_END', '10.9.0.250'),
        // Endpoint VPN publik (bisa lebih dari satu, pisahkan koma) untuk failover
        'endpoints' => array_values(array_filter(array_map('trim', explode(',', (string) env('RADIUS_VPN_ENDPOINTS', env('RADIUS_SERVER_IP', '127.0.0.1')))))),
        'chap_secrets' => env('RADIUS_VPN_CHAP_SECRETS', '/etc/ppp/chap-secrets'),
    ],

    // Pool IP pelanggan default yang dibuat oleh script di router
    'client_pool' => [
        'name' => 'APM2POOL',
        'network' => env('RADIUS_CLIENT_POOL_NETWORK', '10.200.192.0/20'),
        'local_address' => env('RADIUS_CLIENT_POOL_LOCAL', '10.200.192.1'),
        'ranges' => env('RADIUS_CLIENT_POOL_RANGES', '10.200.192.2-10.200.207.254'),
    ],

    // Domain halaman isolir (redirect web-proxy)
    'isolir_url' => env('RADIUS_ISOLIR_URL', 'isolir.apm2.com'),

    // Prefix nama group di tabel radusergroup
    'group_prefix' => [
        'package' => 'pkg_',
        'voucher' => 'vcp_',
    ],
];
