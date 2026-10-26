const { Client } = require('ssh2');

const conn = new Client();

const commands = `
cd /var/www/APM2
echo "Pulling latest changes from GitHub..."
git fetch origin
git reset --hard origin/main

echo "Running Database Migrations..."
php artisan migrate --force

echo "Syncing Master Permissions (Granular Menu/Card/Tab/Filter/Button)..."
php artisan permissions:sync

echo "Fixing corrupted ONTs..."
php artisan app:fix-corrupted-onts

echo "Cleaning Orphaned Incentives (where customer was deleted)..."
php artisan payroll:clean-orphaned-incentives --force

echo "Creating storage symlink if missing..."
php artisan storage:link

echo "Installing root npm dependencies..."
npm install --no-audit --no-fund --legacy-peer-deps

echo "Rebuilding assets safely with zero-downtime protection..."
rm -rf /var/www/APM2/public/build_temp
npx vite build --outDir public/build_temp
if [ -f /var/www/APM2/public/build_temp/manifest.json ]; then
    echo "Build succeeded. Swapping assets atomically..."
    rm -rf /var/www/APM2/public/build_old
    [ -d /var/www/APM2/public/build ] && mv /var/www/APM2/public/build /var/www/APM2/public/build_old
    mv /var/www/APM2/public/build_temp /var/www/APM2/public/build
    rm -rf /var/www/APM2/public/build_old
    echo "Atomic asset swap complete. Site had 0 ms downtime!"
else
    echo "Warning: Temporary build did not produce manifest.json! Retaining existing build."
    rm -rf /var/www/APM2/public/build_temp
fi

echo "Setting up WhatsApp Gateway Node Service..."
cd /var/www/APM2/wa-gateway
npm install --no-audit --no-fund
which pm2 > /dev/null 2>&1 || npm install -g pm2
pm2 describe apm2-wa-gateway > /dev/null 2>&1 && pm2 restart apm2-wa-gateway --update-env || pm2 start server.js --name "apm2-wa-gateway"
pm2 save

cd /var/www/APM2
echo "Clearing cache..."
php artisan optimize:clear

echo "Fixing permissions..."
chown -R www-data:www-data /var/www/APM2/public/build
chmod -R 755 /var/www/APM2/public/build
chown -R www-data:www-data /var/www/APM2/storage /var/www/APM2/bootstrap/cache
chmod -R 775 /var/www/APM2/storage /var/www/APM2/bootstrap/cache
mkdir -p /var/www/APM2/wa-gateway/sessions
chown -R www-data:www-data /var/www/APM2/wa-gateway/sessions

echo "Restarting PHP-FPM to flush OPcache and re-reading bytecode..."
systemctl restart php8.5-fpm || systemctl restart php-fpm
systemctl reload nginx

echo "--- Update Complete ---"
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
