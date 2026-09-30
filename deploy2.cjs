const { Client } = require('ssh2');

const conn = new Client();

const commands = `
cd /var/www/APM2

# Fix git ownership issue
git config --global --add safe.directory /var/www/APM2

# Ensure we have the latest
git reset --hard HEAD
git clean -fd
git pull origin main

# Set proper permissions for Laravel
chown -R www-data:www-data /var/www/APM2 || true
chmod -R 775 storage bootstrap/cache || true

# Install PHP dependencies ignoring PHP version mismatch (VPS has 8.5.4, package wants <= 8.4)
export COMPOSER_ALLOW_SUPERUSER=1
composer install --optimize-autoloader --no-dev --ignore-platform-req=php

# Install Node dependencies with legacy peer deps
npm install --legacy-peer-deps
npm run build

# Generate app key if not generated
php artisan key:generate --force

# Run migrations
php artisan migrate --force
php artisan db:seed --class=RoleAndPermissionSeeder --force

# Sync existing string areas to area_id
php sync_areas.php

# Sync used_ports to match physical onts
php sync_odp_ports.php

php fix_frecon_unit.php

echo "--- Deployment Script Part 2 Fixed Done ---"
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
