const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  const phpScript = `php /var/www/APM2/artisan tinker --execute="echo json_encode(\\Illuminate\\Support\\Facades\\DB::select('SHOW TABLES'));"`;
  conn.exec(phpScript, (err, stream) => {
    if (err) throw err;
    let data = '';
    stream.on('close', () => { console.log(data); conn.end(); }).on('data', (d) => data += d).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
