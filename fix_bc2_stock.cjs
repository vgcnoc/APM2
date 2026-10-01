const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

// Area BC 2 = id 1
\\$areaId = 1;

// Kabel Frecon 150m = id 8
\\$materialId = 8;

\\$materialStock = \\App\\Models\\MaterialStock::firstOrCreate(
    ['material_id' => \\$materialId, 'area_id' => \\$areaId],
    ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
);

// We know they ordered 2 rolls * 150m = 300 meter
\\$materialStock->stock = 300;
\\$materialStock->save();

echo 'Success! Area Stock for BC 2 is now 300 meter.\\n';
"
`;

conn.on('ready', () => {
  conn.exec(script, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      conn.end();
    }).on('data', (data) => {
      process.stdout.write(data);
    }).stderr.on('data', (data) => {
      process.stderr.write(data);
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
