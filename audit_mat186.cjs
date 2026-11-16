const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require '/var/www/APM2/vendor/autoload.php';
$app = require_once '/var/www/APM2/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\Material;

$m186 = Material::find(186);
echo "Mat 186:\\n";
echo "ID: " . $m186->id . "\\n";
echo "Name: " . $m186->name . "\\n";
echo "Category: " . $m186->category . "\\n";
echo "Unit: " . $m186->unit . "\\n";
echo "Stock: " . $m186->stock . "\\n";
echo "AreaID: " . $m186->area_id . "\\n";
`;

    conn.sftp((err, sftp) => {
        if (err) { console.error(err); conn.end(); process.exit(1); }
        const writeStream = sftp.createWriteStream('/tmp/audit_mat186.php');
        writeStream.end(phpScript, () => {
            conn.exec('php /tmp/audit_mat186.php && rm /tmp/audit_mat186.php', (err, stream) => {
                stream.on('close', () => { conn.end(); process.exit(0); })
                      .on('data', d => process.stdout.write(d))
                      .stderr.on('data', d => process.stderr.write(d));
            });
        });
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: process.env.VPS_PASSWORD || 'viruzs123' });

