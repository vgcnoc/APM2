const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan tinker --execute="
\\$settings = json_decode(file_exists(storage_path('app/settings.json')) ? file_get_contents(storage_path('app/settings.json')) : '{}', true);
if (empty(\\$settings['app_lk_url'])) { echo 'NO URL'; exit; }
\\$response = \\Illuminate\\Support\\Facades\\Http::withToken(\\$settings['app_lk_token'] ?? '')->get(rtrim(\\$settings['app_lk_url'], '/') . '/api/customers');
echo json_encode(\\$response->json());
"
`;

conn.on('ready', () => {
  let fullData = '';
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      fs.writeFileSync('test_output.txt', fullData, 'utf8');
      conn.end();
    }).on('data', (data) => {
      fullData += data.toString('utf8');
    }).stderr.on('data', (data) => {
      // ignore
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
