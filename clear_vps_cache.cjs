const { Client } = require('ssh2');
const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear
npm run build
echo "Caches cleared and rebuilt!"
`;

conn.on('ready', () => {
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      conn.end();
    }).on('data', (data) => {
      console.log('STDOUT: ' + data);
    }).stderr.on('data', (data) => {
      console.log('STDERR: ' + data);
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
