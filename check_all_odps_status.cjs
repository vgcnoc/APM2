const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const phpScript = `php /var/www/APM2/artisan tinker --execute="echo json_encode(\\App\\Models\\Odp::all(['id', 'name', 'status']));"`;
  conn.exec(phpScript, (err, stream) => {
    if (err) throw err;
    stream.on('close', () => { conn.end(); }).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
