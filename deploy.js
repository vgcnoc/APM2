import { Client } from 'ssh2';

const conn = new Client();
conn.on('ready', () => {
  console.log('Client :: ready');
  conn.exec('cd /var/www/APM2 && git reset --hard HEAD && git pull origin main && npm install --legacy-peer-deps && php artisan migrate --force && php artisan permissions:sync && php artisan reseller:clean-orphans && php artisan storage:link && npm run build && php artisan optimize:clear && chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache', (err, stream) => {
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
  readyTimeout: 99999
});
