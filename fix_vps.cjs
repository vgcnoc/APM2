const { Client } = require('ssh2');

const conn = new Client();

const commands = `
echo "Checking and fixing Nginx configuration..."

# Update Nginx config to also accept direct IP access
cat > /etc/nginx/sites-available/bill.viruzs.my.id << 'EOF'
server {
    listen 80 default_server;
    server_name bill.viruzs.my.id 157.66.140.17 _;
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
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
EOF

# Ensure the socket path is correct for PHP 8.5
if [ ! -S /run/php/php8.5-fpm.sock ]; then
    REAL_SOCK=$(find /var/run/php /run/php -name "*.sock" | head -n 1)
    if [ ! -z "$REAL_SOCK" ]; then
        sed -i "s|unix:/run/php/php8.5-fpm.sock;|unix:$REAL_SOCK;|g" /etc/nginx/sites-available/bill.viruzs.my.id
    fi
fi

# Ensure default is removed so this becomes the default server
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/bill.viruzs.my.id /etc/nginx/sites-enabled/

# Fix storage permissions properly
cd /var/www/APM2
chown -R www-data:www-data /var/www/APM2
chmod -R 775 storage bootstrap/cache

# Generate compiled assets just in case they failed earlier
# (Assuming node and npm are available from previous scripts)
npm install --legacy-peer-deps
npm run build

# Clear Laravel caches
php artisan optimize:clear

# Check configurations and restart Nginx
nginx -t
systemctl restart nginx

echo "--- Fix Complete ---"
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
