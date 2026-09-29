const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const script = `cd /var/www/APM2 && php artisan migrate`;
  conn.exec(script, (err, stream) => {
    if (err) throw err;
    stream.on('close', () => { conn.end(); }).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
