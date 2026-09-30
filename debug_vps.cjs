const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const cmds = [
    'echo "=== LARAVEL LOG (last 30 lines) ===" && tail -n 30 /var/www/APM2/storage/logs/laravel.log',
    'echo "=== STORAGE LOGOS DIR ===" && ls -la /var/www/APM2/storage/app/public/logos/ 2>&1',
    'echo "=== PUBLIC STORAGE LINK ===" && ls -la /var/www/APM2/public/storage 2>&1',
    'echo "=== SETTINGS TABLE ===" && cd /var/www/APM2 && php artisan tinker --execute="dump(App\\\\Models\\\\Setting::all()->toArray());" 2>&1',
    'echo "=== STORAGE PERMISSIONS ===" && ls -la /var/www/APM2/storage/app/public/ 2>&1',
    'echo "=== NGINX ERROR LOG (last 10) ===" && tail -n 10 /var/log/nginx/error.log 2>&1'
  ];
  conn.exec(cmds.join(' && '), (err, stream) => {
    if (err) throw err;
    stream.on('close', () => conn.end()).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123', readyTimeout: 30000 });
