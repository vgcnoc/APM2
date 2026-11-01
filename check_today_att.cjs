const { Client } = require('ssh2');

const conn = new Client();
const cmd = `cd /var/www/APM2 && php artisan tinker --execute="
\\$today = now()->toDateString();
echo 'Date checked: ' . \\$today . PHP_EOL;
\\$att = \\App\\Models\\Attendance::with('employee')->whereDate('date', \\$today)->get();
echo 'Today attendances: ' . \\$att->count() . PHP_EOL;
foreach (\\$att as \\$a) {
    echo 'ID: ' . \\$a->id . ' | Emp: ' . (\\$a->employee ? \\$a->employee->name : 'No Emp') . ' | In: ' . \\$a->clock_in . ' | Out: ' . \\$a->clock_out . ' | Status: ' . \\$a->status . PHP_EOL;
}
"`;

conn.on('ready', () => {
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    stream.on('close', () => { conn.end(); process.exit(0); })
          .on('data', d => process.stdout.write(d.toString()))
          .stderr.on('data', d => process.stderr.write(d.toString()));
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123'
});
