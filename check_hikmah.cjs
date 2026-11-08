const { Client } = require('ssh2');

const script = `cd /var/www/APM2 && cat << 'EOF' > check_hikmah.php
<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$user = App\\Models\\User::where('name', 'like', '%Hikmah%')->first();
if ($user) {
    echo "User: " . $user->name . " (ID: " . $user->id . ", Role: " . $user->role . ")\n";
    echo "Roles: " . json_encode($user->roles->pluck('name')) . "\n";
    echo "Has customers_tax_toggle: " . ($user->can('customers_tax_toggle') ? 'YES' : 'NO') . "\n";
    $roleCS = Spatie\\Permission\\Models\\Role::where('name', 'like', '%cs%')->orWhere('name', 'like', '%customer%')->get();
    foreach ($roleCS as $rcs) {
        echo "Role " . $rcs->name . " has tax_toggle: " . ($rcs->hasPermissionTo('customers_tax_toggle') ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "User Hikmah not found\n";
}
EOF
php check_hikmah.php
rm check_hikmah.php
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

