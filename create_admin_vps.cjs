const { Client } = require('ssh2');
const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan tinker --execute="\\$u = \\App\\Models\\User::firstOrNew(['email' => 'administrator@apm.com']); \\$u->name = 'Administrator'; \\$u->password = \\Hash::make('admin12345'); \\$u->role = 'admin'; \\$u->is_active = true; \\$u->save(); \\$u->syncRoles(['admin']); echo 'User Created!';"
`;

conn.on('ready', () => {
  console.log('Connecting to VPS...');
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      console.log('Connection closed.');
      conn.end();
    }).on('data', (data) => {
      console.log('STDOUT: ' + data);
    }).stderr.on('data', (data) => {
      console.log('STDERR: ' + data);
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});
