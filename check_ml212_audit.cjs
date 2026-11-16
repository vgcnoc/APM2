const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require '/var/www/APM2/vendor/autoload.php';
$app = require_once '/var/www/APM2/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\CustomerInstalledMaterial;
use App\\Models\\CustomerMaterialDismantle;
use App\\Models\\MaterialTransactionItem;
use App\\Models\\CbpRequest;

echo "=== customer_installed_materials with ML212 ===\\n";
CustomerInstalledMaterial::where('item_name', 'like', '%ML212%')->get()->each(function($m) {
    echo "ID: {$m->id} | CustID: {$m->customer_id} | MatID: {$m->material_id} | Name: {$m->item_name} | SN: {$m->serial_number} | Status: {$m->status} | Source: {$m->installation_source} | Notes: {$m->notes}\\n";
});

echo "\\n=== customer_material_dismantles with ML212 ===\\n";
CustomerMaterialDismantle::where('item_name', 'like', '%ML212%')->get()->each(function($m) {
    echo "ID: {$m->id} | CustID: {$m->customer_id} | Ticket: {$m->ticket_type} #{$m->ticket_number} | MatID: {$m->material_id} | Name: {$m->item_name} | Qty: {$m->quantity}\\n";
});

echo "\\n=== material_transaction_items with ML212 from CBP ===\\n";
MaterialTransactionItem::where('material_name', 'like', '%ML212%')
    ->whereHas('transaction', fn($q) => $q->where('source_type', 'cbp'))
    ->get()->each(function($item) {
        $trx = $item->transaction;
        echo "Trx: {$trx->transaction_number} | SourceRef: {$trx->source_reference} | CustID: {$trx->customer_id} | Item: {$item->material_name} | MatID: {$item->material_id} | Qty: {$item->quantity}\\n";
    });

echo "\\n=== Recent completed CBP and their materials ===\\n";
CbpRequest::where('status', 'completed')->latest()->limit(5)->get()->each(function($c) {
    echo "CBP: {$c->cbp_number} | CustID: {$c->customer_id} | Notes: {$c->notes}\\n";
    $dismantles = CustomerMaterialDismantle::where('ticket_number', $c->cbp_number)->get();
    foreach ($dismantles as $d) {
        echo "  - Dismantle: ID: {$d->id} | MatID: {$d->material_id} | Name: {$d->item_name}\\n";
    }
});
`;

    conn.sftp((err, sftp) => {
        if (err) { console.error(err); conn.end(); process.exit(1); }
        const writeStream = sftp.createWriteStream('/tmp/check_ml212_audit.php');
        writeStream.end(phpScript, () => {
            conn.exec('php /tmp/check_ml212_audit.php && rm /tmp/check_ml212_audit.php', (err, stream) => {
                stream.on('close', () => { conn.end(); process.exit(0); })
                      .on('data', d => process.stdout.write(d))
                      .stderr.on('data', d => process.stderr.write(d));
            });
        });
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: process.env.VPS_PASSWORD || 'viruzs123' });

