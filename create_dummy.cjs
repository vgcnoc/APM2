const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const tinkerCmd = `php artisan tinker --execute="\\App\\Models\\CbpRequest::create(['cbp_number' => 'CBP-'.date('Ymd').'-0001', 'customer_id' => \\App\\Models\\Customer::where('status', 'active')->first()->id ?? \\App\\Models\\Customer::first()->id, 'reason' => 'Dummy data untuk testing pencabutan (Pindah Rumah)', 'status' => 'pending', 'created_by' => 1]);"`;
  conn.exec(`cd /var/www/APM2 && ${tinkerCmd}`, (err, stream) => {
    if (err) throw err;
    stream.on('close', () => { conn.end(); }).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
