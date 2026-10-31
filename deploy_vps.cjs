const { Client } = require('ssh2');

const conn = new Client();
const commands = [
  'cd /var/www/APM2',
  'git reset --hard HEAD',
  'git clean -fd public/build',
  'git pull origin main',
  'php artisan migrate --force',
  'php artisan permissions:sync',
  'php artisan optimize:clear',
  'php artisan config:cache',
  'php artisan route:cache',
  'php artisan view:cache',
  'php artisan queue:restart',
  'systemctl is-active php8.5-fpm && systemctl reload php8.5-fpm || systemctl is-active php8.2-fpm && systemctl reload php8.2-fpm || true',
  'systemctl is-active nginx && systemctl reload nginx || true',
  'echo "DEPLOYMENT_SUCCESSFUL"'
].join(' && ');

console.log('Connecting to VPS 157.66.140.17...');

conn.on('ready', () => {
  console.log('SSH Connection ready. Executing deployment pipeline...');
  conn.exec(commands, (err, stream) => {
    if (err) {
      console.error('Exec error:', err);
      conn.end();
      process.exit(1);
    }

    stream.on('close', (code, signal) => {
      console.log(`Command closed with code: ${code}`);
      conn.end();
      process.exit(code || 0);
    }).on('data', (data) => {
      process.stdout.write(data.toString());
    }).stderr.on('data', (data) => {
      process.stderr.write(data.toString());
    });
  });
}).on('error', (err) => {
  console.error('SSH Connection failed:', err);
  process.exit(1);
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
