const { Client } = require('ssh2');

const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan tinker --execute="
\\$c = App\\Models\\Customer::where('name', 'like', '%Hikmah%')->with('technicianSchedules', 'ont')->first();
echo json_encode([
    'id' => \\$c->id,
    'status' => \\$c->status,
    'is_audited' => \\$c->is_audited,
    'schedules' => \\$c->technicianSchedules,
    'ont' => \\$c->ont
], JSON_PRETTY_PRINT);
"
`;

conn.on('ready', () => {
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
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
