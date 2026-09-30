const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const cmd = `cd /var/www/APM2 && php artisan tinker --execute="
    \\$request = \\\\Illuminate\\\\Http\\\\Request::create('/login', 'GET');
    \\$request->headers->set('X-Inertia', 'true');
    \\$kernel = app()->make(\\\\Illuminate\\\\Contracts\\\\Http\\\\Kernel::class);
    \\$response = \\$kernel->handle(\\$request);
    echo \\$response->getContent();
  "`;
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('close', () => { console.log(out); conn.end(); })
      .on('data', (d) => { out += d.toString(); })
      .stderr.on('data', (d) => { out += d.toString(); });
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123', readyTimeout: 30000 });
