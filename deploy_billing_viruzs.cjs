const { Client } = require('ssh2');
const fs = require('fs');
const path = require('path');

const conn = new Client();

const script = `
set -e
cd /var/www/APM2
echo "=== 1. Git Fetch & Reset ==="
git fetch origin
git reset --hard origin/main

echo "=== 2. Composer Autoload ==="
composer dump-autoload

echo "=== 3. Database Migration & Seeding ==="
php artisan migrate --force
sudo php artisan permission:cache-reset
sudo php artisan db:seed --class=RoleAndPermissionSeeder --force

echo "=== 4. Vite Build ==="
npm run build

echo "=== 5. Optimasi & Clear Cache ==="
php artisan optimize:clear
php artisan view:clear

echo "=== 6. Atur Permission Folder ==="
sudo chown -R www-data:www-data storage bootstrap/cache public/build
sudo chmod -R 775 storage bootstrap/cache public/build

echo "=== 7. Restart Layanan ==="
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx

echo "=== DEPLOYMENT SELESAI & BERHASIL ==="
`;

const privateKeyPath = path.join(process.env.USERPROFILE || 'C:\\Users\\v', '.ssh', 'id_ed25519_viruzs');

conn.on('ready', () => {
  console.log('[SSH] Terhubung ke billing.viruzs.net (139.190.96.74)... Menjalankan deployment.');
  conn.exec(script, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      console.log(`[SSH] Proses selesai dengan exit code: ${code}`);
      conn.end();
    }).on('data', (data) => {
      process.stdout.write(data);
    }).stderr.on('data', (data) => {
      process.stderr.write(data);
    });
  });
}).connect({
  host: '139.190.96.74',
  port: 22,
  username: 'viruzs',
  privateKey: fs.readFileSync(privateKeyPath),
  readyTimeout: 30000
});
