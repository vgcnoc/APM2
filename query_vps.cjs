const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

\\$area = \\App\\Models\\Area::where('name', 'like', '%BC 2%')->orWhere('name', 'like', '%BC2%')->first();
if (!\\$area) { echo 'Area BC2 not found\\n'; exit; }

\\$material = \\App\\Models\\Material::where('category', 'like', '%Frecon%')->orWhere('name', 'like', '%Frecon%')->first();
if (!\\$material) { echo 'Material Frecon not found\\n'; exit; }

\\$stocks = \\App\\Models\\MaterialStock::where('material_id', \\$material->id)->where('area_id', \\$area->id)->first();
echo '--- Area Stock ---\\n';
echo 'Area: ' . \\$area->name . '\\n';
echo 'Stock: ' . (\\$stocks ? \\$stocks->stock : 0) . ' ' . \\$material->unit . '\\n';

echo '\\n--- Transactions ---\\n';
\\$transactions = \\App\\Models\\MaterialTransaction::where('area_id', \\$area->id)
    ->where('type', 'out')
    ->with('items')
    ->get();

foreach(\\$transactions as \\$trx) {
    foreach(\\$trx->items as \\$item) {
        if (\\$item->material_id == \\$material->id) {
            echo \\$trx->transaction_number . ': ' . \\$item->quantity . ' ' . \\$item->unit . '\\n';
        }
    }
}
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
