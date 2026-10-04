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

    /*
    |--------------------------------------------------------------------------
    | Group Isolir
    |--------------------------------------------------------------------------
    | Pelanggan berstatus "suspended" dipindah ke group ini.
    */
    'isolir' => [
        'group' => env('RADIUS_ISOLIR_GROUP', 'isolir'),
        'address_list' => env('RADIUS_ISOLIR_ADDRESS_LIST', 'isolir'),
        'rate_limit' => env('RADIUS_ISOLIR_RATE_LIMIT', '512k/512k'),
        'pool' => env('RADIUS_ISOLIR_POOL'), // opsional: Framed-Pool khusus isolir
    ],

    // Prefix nama group di tabel radusergroup
    'group_prefix' => [
        'package' => 'pkg_',
        'voucher' => 'vcp_',
    ],
];
