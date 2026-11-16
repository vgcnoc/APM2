const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require '/var/www/APM2/vendor/autoload.php';
$app = require_once '/var/www/APM2/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\Customer;
use App\\Models\\CbpRequest;

$cust = Customer::with(['ont', 'installedMaterials', 'technicianSchedules'])->find(179);
echo "Customer 179: " . ($cust?->name) . "\\n";
echo "ONT: " . ($cust?->ont ? "ID: {$cust->ont->id}, Model: {$cust->ont->model}" : "NULL") . "\\n";
echo "Technician Schedules:\\n";
foreach ($cust->technicianSchedules as $s) {
    echo "  - Type: {$s->type} | Notes: {$s->notes}\\n";
}
echo "Installed Materials:\\n";
foreach ($cust->installedMaterials as $m) {
    echo "  - ID: {$m->id} | MatID: {$m->material_id} | Name: {$m->item_name} | Status: {$m->status}\\n";
}
`;

    conn.sftp((err, sftp) => {
        if (err) { console.error(err); conn.end(); process.exit(1); }
        const writeStream = sftp.createWriteStream('/tmp/audit_cust179.php');
        writeStream.end(phpScript, () => {
            conn.exec('php /tmp/audit_cust179.php && rm /tmp/audit_cust179.php', (err, stream) => {
                stream.on('close', () => { conn.end(); process.exit(0); })
                      .on('data', d => process.stdout.write(d))
                      .stderr.on('data', d => process.stderr.write(d));
            });
        });
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: process.env.VPS_PASSWORD || 'viruzs123' });

