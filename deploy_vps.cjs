const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
git fetch origin
git reset --hard origin/main
git clean -fd -e public/build
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

# 1. Optimasi Nginx Gzip & Static Asset Caching
sed -i 's/# gzip_vary on;/gzip_vary on;/' /etc/nginx/nginx.conf
sed -i 's/# gzip_proxied any;/gzip_proxied any;/' /etc/nginx/nginx.conf
sed -i 's/# gzip_comp_level 6;/gzip_comp_level 6;/' /etc/nginx/nginx.conf
sed -i 's/# gzip_types /gzip_types /' /etc/nginx/nginx.conf
sed -i 's/gzip_types text\\/plain text\\/css application\\/json application\\/javascript text\\/xml application\\/xml application\\/xml+rss text\\/javascript;/gzip_types text\\/plain text\\/css application\\/json application\\/javascript text\\/xml application\\/xml application\\/xml+rss text\\/javascript image\\/svg+xml font\\/woff font\\/woff2 application\\/vnd.ms-fontobject;/' /etc/nginx/nginx.conf

if ! grep -q "max-age=31536000" /etc/nginx/sites-available/bill.viruzs.my.id; then
    sed -i '/location \\/ {/i \\    location ~* \\\\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp|avif)$ {\\n        expires 1y;\\n        add_header Cache-Control "public, max-age=31536000, immutable";\\n        access_log off;\\n        log_not_found off;\\n        try_files $uri =404;\\n    }\\n' /etc/nginx/sites-available/bill.viruzs.my.id
fi

# 2. Optimasi InnoDB Buffer Pool MySQL (1 GB untuk cache tabel dan index di RAM)
if [ -f /etc/mysql/mysql.conf.d/mysqld.cnf ] && ! grep -q "innodb_buffer_pool_size" /etc/mysql/mysql.conf.d/mysqld.cnf; then
    sed -i '/\\[mysqld\\]/a innodb_buffer_pool_size = 1G' /etc/mysql/mysql.conf.d/mysqld.cnf
    systemctl restart mysql || true
fi

nginx -t && systemctl reload nginx
systemctl restart php8.5-fpm || true
echo "=== OPTIMASI SERVER & DEPLOYMENT SUKSES ==="
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
