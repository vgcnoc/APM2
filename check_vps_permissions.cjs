const { Client } = require('ssh2');

const conn = new Client();
const cmd = `cd /var/www/APM2 && php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$t1 = Spatie\\Permission\\Models\\Role::where("name", "teknisi")->first()->permissions->pluck("name")->toArray();
$t2 = Spatie\\Permission\\Models\\Role::where("name", "teknisi-2")->first()->permissions->pluck("name")->toArray();

$diff1 = array_diff($t1, $t2);
$diff2 = array_diff($t2, $t1);

echo "In teknisi but NOT in teknisi-2: " . implode(", ", $diff1) . "\n";
echo "In teknisi-2 but NOT in teknisi: " . implode(", ", $diff2) . "\n";
'`;

conn.on('ready', () => {
  conn.exec(cmd, (err, stream) => {
    if (err) {
      console.error('Exec error:', err);
      conn.end();
      process.exit(1);
    }
    stream.on('close', (code) => {
      conn.end();
      process.exit(code || 0);
    }).on('data', (data) => {
      process.stdout.write(data.toString());
    }).stderr.on('data', (data) => {
      process.stderr.write(data.toString());
    });
  });
}).on('error', (err) => {
  console.error('SSH Connection failed:', err);
  process.exit(1);
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: process.env.VPS_PASSWORD || 'viruzs123'
});
