const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  // Check what the page actually returns for app_logo prop
  const cmd = `cd /var/www/APM2 && php artisan tinker --execute="
    \\\$logo = App\\\\Models\\\\Setting::get('app_logo');
    echo 'RAW app_logo value: ' . var_export(\\\$logo, true) . PHP_EOL;
    echo 'asset URL: ' . (\\\$logo ? asset('storage/' . \\\$logo) : 'NULL') . PHP_EOL;
    echo 'File exists: ' . var_export(\\\\Illuminate\\\\Support\\\\Facades\\\\Storage::disk('public')->exists(\\\$logo ?? ''), true) . PHP_EOL;
    echo 'Public path: ' . public_path('storage/' . \\\$logo) . PHP_EOL;
    echo 'File exists on disk: ' . var_export(file_exists(public_path('storage/' . \\\$logo)), true) . PHP_EOL;
  "`;
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('close', () => { console.log(out); conn.end(); })
      .on('data', (d) => { out += d.toString(); })
      .stderr.on('data', (d) => { out += d.toString(); });
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123', readyTimeout: 30000 });
