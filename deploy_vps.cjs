const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
git fetch origin
git reset --hard origin/main
git clean -fd
composer dump-autoload
php artisan migrate --force
php artisan db:seed --class=RbcaSeeder --force
npm install --legacy-peer-deps
npm run build
php artisan optimize:clear
php artisan view:clear
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
systemctl restart php8.2-fpm || true
systemctl restart php8.3-fpm || true
systemctl restart php8.4-fpm || true
systemctl restart php8.5-fpm || true
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
