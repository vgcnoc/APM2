const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpCode = `<?php
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
    $kernel->bootstrap();

    use Illuminate\\Support\\Facades\\DB;

    $stocks = DB::table('material_stocks')
        ->whereIn('material_id', [276, 279, 341, 342])
        ->where('area_id', 1)
        ->get();

    foreach ($stocks as $s) {
        echo "ID: {$s->id} | Mat: {$s->material_id} | Area: {$s->area_id} | Stock: {$s->stock} | Init: {$s->initial_stock} | Rolls: {$s->total_rolls} | Created: {$s->created_at} | Updated: {$s->updated_at}\\n";
    }
    `;

    const b64 = Buffer.from(phpCode).toString('base64');
    const cmd = `echo "${b64}" | base64 -d > /tmp/audit_st.php && cd /var/www/APM2 && php /tmp/audit_st.php && rm /tmp/audit_st.php`;

    conn.exec(cmd, (err, stream) => {
        if (err) {
            console.error(err);
            conn.end();
            process.exit(1);
        }
        stream.on('close', (code) => {
            conn.end();
            process.exit(code);
        }).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: process.env.VPS_PASSWORD || 'viruzs123',
    readyTimeout: 30000,
});

