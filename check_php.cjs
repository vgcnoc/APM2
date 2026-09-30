const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec('php -i | grep -E "upload_max_filesize|post_max_size"', (err, stream) => {
    if (err) throw err;
    stream.on('close', () => conn.end()).on('data', (d) => process.stdout.write(d)).stderr.on('data', (d) => process.stderr.write(d));
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123', readyTimeout: 30000 });
