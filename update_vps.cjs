const { Client } = require('ssh2');

const conn = new Client();

const commands = `
cd /var/www/APM2
echo "Pulling latest changes from GitHub..."
git fetch origin
git reset --hard origin/main

echo "Running Database Migrations..."
php artisan migrate --force

echo "Fixing corrupted ONTs..."
php artisan app:fix-corrupted-onts

echo "Creating storage symlink if missing..."
php artisan storage:link

echo "Generating missing invoices..."
php artisan app:generate-missing-invoices

echo "Installing npm dependencies..."
npm install --no-audit --no-fund --legacy-peer-deps

echo "Rebuilding assets..."
npm run build

echo "Clearing cache..."
php artisan optimize:clear

echo "Fixing permissions..."
chown -R www-data:www-data /var/www/APM2/public/build
chown -R www-data:www-data /var/www/APM2/storage

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
