const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
php artisan tinker --execute="\\App\\Models\\TechnicianSchedule::whereIn('id', [69, 70])->update(['status' => 'scheduled']);"
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
