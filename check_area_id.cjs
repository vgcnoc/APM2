const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const phpScript = `php /var/www/APM2/artisan tinker --execute="\\$c = \\App\\Models\\Customer::whereNotNull('area_id')->first(); \\$o = \\App\\Models\\Odp::whereNotNull('area_id')->first(); echo json_encode(['customer_area_id' => \\$c ? \\$c->area_id : null, 'odp_area_id' => \\$o ? \\$o->area_id : null]);"`;
  conn.exec(phpScript, (err, stream) => {
    if (err) throw err;
    stream.on('close', () => { conn.end(); }).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
