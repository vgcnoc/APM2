const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpCode = `<?php
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
    $kernel->bootstrap();

    use Illuminate\\Support\\Facades\\DB;

    echo "=== DETAIL SEMUA TRANSAKSI MAT 276 DI SEMUA AREA ===\\n";
    $t276 = DB::table('material_transaction_items')
        ->join('material_transactions', 'material_transaction_items.material_transaction_id', '=', 'material_transactions.id')
        ->where('material_transaction_items.material_id', 276)
        ->select('material_transactions.*', 'material_transaction_items.quantity', 'material_transaction_items.stock_before', 'material_transaction_items.stock_after')
        ->orderBy('material_transactions.id', 'asc')
        ->get();
    foreach ($t276 as $t) {
        echo "Tx #{$t->id} | {$t->transaction_number} | Type: {$t->type} | Area: {$t->area_id} | Date: {$t->date} | Qty: {$t->quantity} | {$t->stock_before} -> {$t->stock_after} | Notes: {$t->notes}\\n";
    }

    echo "\\n=== DETAIL SEMUA TRANSAKSI MAT 279 DI SEMUA AREA ===\\n";
    $t279 = DB::table('material_transaction_items')
        ->join('material_transactions', 'material_transaction_items.material_transaction_id', '=', 'material_transactions.id')
        ->where('material_transaction_items.material_id', 279)
        ->select('material_transactions.*', 'material_transaction_items.quantity', 'material_transaction_items.stock_before', 'material_transaction_items.stock_after')
        ->orderBy('material_transactions.id', 'asc')
        ->get();
    foreach ($t279 as $t) {
        echo "Tx #{$t->id} | {$t->transaction_number} | Type: {$t->type} | Area: {$t->area_id} | Date: {$t->date} | Qty: {$t->quantity} | {$t->stock_before} -> {$t->stock_after} | Notes: {$t->notes}\\n";
    }

    echo "\\n=== PERIKSA LOG / MIGRATION RECONCILE ===\\n";
    // Check migrations applied
    $migs = DB::table('migrations')->where('migration', 'like', '%material%')->get();
    foreach ($migs as $m) {
        echo "Migration: {$m->migration} (batch {$m->batch})\\n";
    }
    `;

    const b64 = Buffer.from(phpCode).toString('base64');
    const cmd = `echo "${b64}" | base64 -d > /tmp/audit_detail.php && cd /var/www/APM2 && php /tmp/audit_detail.php && rm /tmp/audit_detail.php`;

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

