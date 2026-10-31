const { Client } = require('ssh2');

const conn = new Client();
const cmd = `cd /var/www/APM2 && php artisan tinker --execute="
\\$emps = \\App\\Models\\Employee::take(10)->get(['id', 'name', 'position', 'base_salary']);
echo 'Employees base_salary sample: ' . json_encode(\\$emps) . PHP_EOL;
\\$withSalary = \\App\\Models\\Employee::whereNotNull('base_salary')->where('base_salary', '>', 0)->count();
\\$total = \\App\\Models\\Employee::count();
echo 'Employees with base_salary > 0: ' . \\$withSalary . ' / ' . \\$total . PHP_EOL;
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

