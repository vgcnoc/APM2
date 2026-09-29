const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const script = `cat << 'EOF' > /var/www/APM2/fix_sopi.php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

$customer = App\\Models\\Customer::where('name', 'SOPI')->first();
if ($customer) {
    $ont = App\\Models\\Ont::where('customer_id', $customer->id)->first();
    if ($ont && !$ont->odp_id) {
        $odp = App\\Models\\Odp::where('area_id', $customer->area_id)->first();
        if ($odp) {
            $ont->odp_id = $odp->id;
            $ont->port_number = 1;
            $ont->save();
            echo "Fixed ONT for SOPI. Assigned ODP ID: " . $odp->id;
        } else {
            echo "No ODP found for Area ID: " . $customer->area_id;
        }
    } else {
        echo "ONT already has ODP or not found.";
    }
} else {
    echo "Customer SOPI not found.";
}
EOF
php /var/www/APM2/fix_sopi.php
rm /var/www/APM2/fix_sopi.php
`;
  conn.exec(script, (err, stream) => {
    if (err) throw err;
    stream.on('close', () => { conn.end(); })
          .on('data', (d) => process.stdout.write(d))
          .stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
