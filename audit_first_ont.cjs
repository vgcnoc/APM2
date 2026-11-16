const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require '/var/www/APM2/vendor/autoload.php';
$app = require_once '/var/www/APM2/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\Material;

$first = Material::where('category', 'like', '%ont%')->orWhere('name', 'like', '%ont%')->first();
echo "FIRST ONT MATERIAL: ID: " . ($first?->id ?? 'null') . " | Name: " . ($first?->name ?? 'null') . " | Cat: " . ($first?->category ?? 'null') . "\\n";

echo "\\nAll ONT materials in catalog:\\n";
Material::where('category', 'like', '%ont%')->orWhere('name', 'like', '%ont%')->get()->each(function($m) {
    echo "ID: {$m->id} | Name: {$m->name} | Cat: {$m->category}\\n";
});
`;

    conn.sftp((err, sftp) => {
        if (err) { console.error(err); conn.end(); process.exit(1); }
        const writeStream = sftp.createWriteStream('/tmp/audit_first_ont.php');
        writeStream.end(phpScript, () => {
            conn.exec('php /tmp/audit_first_ont.php && rm /tmp/audit_first_ont.php', (err, stream) => {
                stream.on('close', () => { conn.end(); process.exit(0); })
                      .on('data', d => process.stdout.write(d))
                      .stderr.on('data', d => process.stderr.write(d));
            });
        });
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: process.env.VPS_PASSWORD || 'viruzs123' });

