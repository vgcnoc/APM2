const { Client } = require('ssh2');
const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan tinker --execute="
\\$users = \\App\\Models\\User::with('roles.permissions', 'permissions')->get();
foreach(\\$users as \\$u) {
    echo \\$u->email . ' (Role: ' . \\$u->role . ') -> Roles: ' . json_encode(\\$u->getRoleNames()) . ' -> Perms: ' . json_encode(\\$u->getAllPermissions()->pluck('name')) . \"\\n\";
}
"
`;

conn.on('ready', () => {
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
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
