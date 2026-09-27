const { Client } = require('ssh2');
const c = new Client();

const commands = `
cd /var/www/APM2

# Switch session driver to file (more reliable, no DB dependency)
sed -i 's|SESSION_DRIVER=database|SESSION_DRIVER=file|' .env

# Fix storage permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear old file sessions
rm -f storage/framework/sessions/*

# Re-clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# Restart PHP-FPM
systemctl restart php8.5-fpm
systemctl restart nginx

echo "=== Verification ==="
cat .env | grep SESSION_DRIVER
ls -la storage/framework/sessions/
curl -s -o /dev/null -w "%{http_code}" http://bill.viruzs.my.id/login
echo ""
echo "--- All Done ---"
`;

c.on('ready', () => {
  console.log('Connected');
  c.exec(commands, (e, s) => {
    if (e) throw e;
    s.on('data', d => process.stdout.write(d))
     .on('close', (code) => { console.log('Exit:', code); c.end(); })
     .stderr.on('data', d => process.stderr.write(d));
  });
}).connect({host:'157.66.140.17',port:22,username:'root',password:'viruzs123'});
