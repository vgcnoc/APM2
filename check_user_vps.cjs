const { Client } = require('ssh2');
const conn = new Client();

const commands = `
cd /var/www/APM2
php artisan tinker --execute="\\$custs = \\App\\Models\\Customer::whereIn('customer_code', ['CUS-001001', 'CUS-001002', 'CUS-001003', 'CUS-001004'])->with(['technicianSchedules', 'areaModel'])->get(); echo json_encode(\\$custs->map(fn(\\$c) => ['code' => \\$c->customer_code, 'name' => \\$c->name, 'status' => \\$c->status, 'area' => \\$c->areaModel?->name, 'area_id' => \\$c->area_id, 'schedules' => \\$c->technicianSchedules->map(fn(\\$s) => ['tech_id' => \\$s->technician_id, 'type' => \\$s->type, 'status' => \\$s->status])]));"
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
