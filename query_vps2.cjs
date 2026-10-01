const { Client } = require('ssh2');

const conn = new Client();

const script = `
cd /var/www/APM2
php -r "
require 'vendor/autoload.php';
\\$app = require_once 'bootstrap/app.php';
\\$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

\\$materials = \\App\\Models\\Material::where('category', 'like', '%Frecon%')->orWhere('name', 'like', '%Frecon%')->get();
foreach(\\$materials as \\$m) {
    echo 'ID: ' . \\$m->id . ' | Name: ' . \\$m->name . ' | Stock: ' . \\$m->stock . ' ' . \\$m->unit . ' | MPR: ' . \\$m->meter_per_roll . '\\n';
    foreach(\\App\\Models\\MaterialStock::where('material_id', \\$m->id)->get() as \\$s) {
        echo '  - Area ' . \\$s->area_id . ' Stock: ' . \\$s->stock . '\\n';
    }
}
"
`;

conn.on('ready', () => {
  conn.exec(script, (err, stream) => {
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
