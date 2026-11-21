const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpCode = `<?php
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
    $kernel->bootstrap();

    use Illuminate\\Support\\Facades\\DB;

    echo "=== JADWAL TEKNISI MAUNAL HAKIM ===\\n";
    $scheds = DB::table('technician_schedules')->where('customer_id', 1693)->get();
    foreach ($scheds as $s) {
        echo "Sched #{$s->id} | Type: {$s->type} | Status: {$s->status} | Date: {$s->scheduled_date} | Notes: {$s->notes}\\n";
    }

    echo "\\n=== PERIKSA TRANSAKSI MAUNAL HAKIM ===\\n";
    $txs = DB::table('material_transactions')->where('customer_id', 1693)->orWhere('source_id', 1693)->get();
    foreach ($txs as $t) {
        echo "Tx #{$t->id} | {$t->transaction_number} | Type: {$t->type} | Date: {$t->date} | Notes: {$t->notes}\\n";
    }

    echo "\\n=== PERIKSA APAKAH ADA OUT-BEKAL MAUNAL HAKIM DI LOG / DB ===\\n";
    $bekal = DB::table('material_transactions')->where('notes', 'like', '%Hakim%')->get();
    foreach ($bekal as $b) {
        echo "Tx #{$b->id} | {$b->transaction_number} | Type: {$b->type} | Date: {$b->date} | Notes: {$b->notes}\\n";
    }
    `;

    const b64 = Buffer.from(phpCode).toString('base64');
    const cmd = `echo "${b64}" | base64 -d > /tmp/audit_hakim.php && cd /var/www/APM2 && php /tmp/audit_hakim.php && rm /tmp/audit_hakim.php`;

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
