const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
php artisan tinker --execute="\\$t = \\App\\Models\\MaterialTransaction::where('transaction_number', 'OUT-20261008-MIJI5')->first(); echo json_encode(\\$t);"
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
