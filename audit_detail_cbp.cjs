const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require '/var/www/APM2/vendor/autoload.php';
$app = require_once '/var/www/APM2/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\CbpRequest;
use App\\Models\\Customer;
use App\\Models\\Ont;
use App\\Models\\CustomerInstalledMaterial;
use App\\Models\\MaterialTransaction;

foreach (['CBP-20261114-0001', 'CBP-20261114-0002', 'CBP-20261114-0003', 'CBP-20261114-0004'] as $cbpNum) {
    echo "=========================================================\\n";
    $cbp = CbpRequest::where('cbp_number', $cbpNum)->with('customer')->first();
    if (!$cbp) { echo "CBP {$cbpNum} not found\\n"; continue; }
    echo "CBP: {$cbp->cbp_number} | CustID: {$cbp->customer_id} | Name: " . ($cbp->customer?->name ?? 'NULL') . "\\n";

    // Cek CustomerInstalledMaterials
    $cMats = CustomerInstalledMaterial::where('customer_id', $cbp->customer_id)->get();
    echo "  Installed Materials in DB for this customer (" . $cMats->count() . "):\\n";
    foreach ($cMats as $cm) {
        echo "    - MatID: {$cm->material_id} | Name: {$cm->item_name} | SN: {$cm->serial_number} | Status: {$cm->status}\\n";
    }

    // Cek ONT yang pernah terhubung ke customer ini
    $onts = Ont::where('description', 'like', "%{$cbpNum}%")->orWhere('customer_id', $cbp->customer_id)->get();
    echo "  ONTs linked to this CBP or Customer (" . $onts->count() . "):\\n";
    foreach ($onts as $o) {
        echo "    - ONT ID: {$o->id} | Brand: {$o->brand} | Model: {$o->model} | SN: {$o->serial_number} | Status: {$o->status} | Desc: {$o->description}\\n";
    }

    // Cek Transaksi Retur
    $trx = MaterialTransaction::where('source_type', 'cbp')->where('source_id', $cbp->id)->with('items')->first();
    if ($trx) {
        echo "  Retur Trx: {$trx->transaction_number} | old_ont_id: {$trx->old_ont_id}\\n";
        foreach ($trx->items as $ti) {
            echo "    - TrxItem: MatID: {$ti->material_id} | Name: {$ti->material_name} | Qty: {$ti->quantity}\\n";
        }
    }
}
`;

    conn.sftp((err, sftp) => {
        if (err) { console.error(err); conn.end(); process.exit(1); }
        const writeStream = sftp.createWriteStream('/tmp/audit_detail_cbp.php');
        writeStream.end(phpScript, () => {
            conn.exec('php /tmp/audit_detail_cbp.php && rm /tmp/audit_detail_cbp.php', (err, stream) => {
                stream.on('close', () => { conn.end(); process.exit(0); })
                      .on('data', d => process.stdout.write(d))
                      .stderr.on('data', d => process.stderr.write(d));
            });
        });
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: process.env.VPS_PASSWORD || 'viruzs123' });
