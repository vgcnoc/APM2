const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require '/var/www/APM2/vendor/autoload.php';
$app = require_once '/var/www/APM2/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\Schema;
echo "ONT cols: " . implode(', ', Schema::getColumnListing('onts')) . "\\n";
echo "Material cols: " . implode(', ', Schema::getColumnListing('materials')) . "\\n";
echo "Cbp cols: " . implode(', ', Schema::getColumnListing('cbp_requests')) . "\\n";
`;

    conn.sftp((err, sftp) => {
        if (err) { console.error(err); conn.end(); process.exit(1); }
        const writeStream = sftp.createWriteStream('/tmp/audit_cbp.php');
        writeStream.end(phpScript, () => {
            conn.exec('php /tmp/audit_cbp.php && rm /tmp/audit_cbp.php', (err, stream) => {
                stream.on('close', () => { conn.end(); process.exit(0); })
                      .on('data', d => process.stdout.write(d))
                      .stderr.on('data', d => process.stderr.write(d));
            });
        });
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: process.env.VPS_PASSWORD || 'viruzs123' });
