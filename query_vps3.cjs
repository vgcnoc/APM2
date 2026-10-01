const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

\\$transactions = \\App\\Models\\MaterialTransactionItem::where('material_id', 8)->with('transaction')->get();
foreach(\\$transactions as \\$trxItem) {
    echo 'Trx: ' . \\$trxItem->transaction->transaction_number . ' | Area: ' . \\$trxItem->transaction->area_id . ' | Qty: ' . \\$trxItem->quantity . ' | Unit: ' . \\$trxItem->unit . '\\n';
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
