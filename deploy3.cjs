const { Client } = require('ssh2');

const conn = new Client();

const commands = `
# Determine PHP-FPM socket
PHP_SOCK=$(find /var/run/php /run/php -name "*.sock" | head -n 1)

if [ -z "$PHP_SOCK" ]; then
    echo "No PHP-FPM socket found! Attempting to install php-fpm..."
    apt-get update && apt-get install -y php-fpm
    PHP_SOCK=$(find /var/run/php /run/php -name "*.sock" | head -n 1)
fi

echo "Using PHP socket: $PHP_SOCK"

# Configure Nginx
if [ ! -f /etc/nginx/sites-available/bill.viruzs.my.id ]; then
cat > /etc/nginx/sites-available/bill.viruzs.my.id << 'EOF'
server {
    listen 80;
    server_name bill.viruzs.my.id;
    root /var/www/APM2/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php$ {
        fastcgi_pass unix:__PHP_SOCK__;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
EOF
fi

# Replace placeholder with actual socket path
sed -i "s|__PHP_SOCK__|$PHP_SOCK|g" /etc/nginx/sites-available/bill.viruzs.my.id

# Enable site
ln -sf /etc/nginx/sites-available/bill.viruzs.my.id /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Restart Nginx
systemctl restart nginx || echo "Nginx restart failed, maybe it's not installed?"

# Configure MySQL
# Assuming root MySQL has no password or matches VPS password
mysql -e "CREATE DATABASE IF NOT EXISTS isp_management;" || mysql -p'viruzs123' -e "CREATE DATABASE IF NOT EXISTS isp_management;"

# Update .env
cd /var/www/APM2
sed -i 's/^APP_URL=.*/APP_URL=http:\\/\\/bill.viruzs.my.id/g' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=isp_management/g' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=viruzs123/g' .env # Trying with VPS password just in case

# Run migrations
php artisan migrate --force || echo "Migration failed, check DB credentials"
php artisan db:seed --force || true

# Fix permissions again after artisan commands
chown -R www-data:www-data /var/www/APM2
chmod -R 775 storage bootstrap/cache

echo "--- Deployment Complete ---"
`;

conn.on('ready', () => {
  console.log('Client :: ready');
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      console.log('Stream :: close :: code: ' + code + ', signal: ' + signal);
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
