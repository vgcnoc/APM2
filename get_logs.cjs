const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec('tail -n 1000 /var/www/APM2/storage/logs/laravel.log', (err, stream) => {
    if (err) throw err;
    let data = '';
    stream.on('close', () => {
        const lines = data.split('\n');
        const errIndex = lines.findLastIndex(l => l.includes('local.ERROR:'));
        if (errIndex !== -1) {
            console.log(lines.slice(errIndex, errIndex + 20).join('\n'));
        } else {
            console.log("NO ERRORS FOUND");
        }
        conn.end();
    }).on('data', d => data += d);
  });
}).connect({host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123'});
