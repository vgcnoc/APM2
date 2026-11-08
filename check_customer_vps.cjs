const { Client } = require('ssh2');

const script = `cd /var/www/APM2 && cat << 'EOF' > check_customer.php
<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$pkg = App\\Models\\InternetPackage::where('name', 'like', '%5 DEVICE%')->get();
foreach ($pkg as $p) {
    echo "Package ID: " . $p->id . " | Name: " . $p->name . "\n";
}

$c = App\\Models\\Customer::where('activation_date', 'like', '%2022-11-08%')->get();
foreach ($c as $item) {
    echo "ID: " . $item->id . " | Name: " . $item->name . " | pkg: " . ($item->package_id) . " | ont: " . json_encode($item->ont) . "\n";
}
EOF
php check_customer.php
rm check_customer.php
`;

const conn = new Client();
conn.on('ready', () => {
  conn.exec(script, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code) => {
      conn.end();
      process.exit(code);
    }).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
