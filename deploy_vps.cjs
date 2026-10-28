const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
git fetch origin
git reset --hard origin/main
git clean -fd
php artisan migrate --force
composer dump-autoload -o
php artisan permissions:sync
npm install --legacy-peer-deps
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache public/build
chmod -R 775 storage bootstrap/cache public/build
sed -i 's/# gzip_vary on;/gzip_vary on;/' /etc/nginx/nginx.conf
sed -i 's/# gzip_proxied any;/gzip_proxied any;/' /etc/nginx/nginx.conf
sed -i 's/# gzip_comp_level 6;/gzip_comp_level 6;/' /etc/nginx/nginx.conf
sed -i 's/# gzip_types /gzip_types /' /etc/nginx/nginx.conf
nginx -t && systemctl reload nginx
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
