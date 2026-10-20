const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec(process.argv[2] || 'tail -n 200 /var/www/APM2/storage/logs/laravel.log', (err, stream) => {
    if (err) throw err;
    stream.on('data', d => process.stdout.write(d));
    stream.stderr.on('data', d => process.stderr.write(d));
    stream.on('close', () => conn.end());
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
