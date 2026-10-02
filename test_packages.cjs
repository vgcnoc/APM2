const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan tinker --execute="echo json_encode(['packages' => \\App\\Models\\InternetPackage::get(['id', 'name']), 'users' => \\App\\Models\\User::get(['id', 'name'])]);"
`;

conn.on('ready', () => {
  let fullData = '';
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      fs.writeFileSync('test_packages_output.txt', fullData, 'utf8');
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
