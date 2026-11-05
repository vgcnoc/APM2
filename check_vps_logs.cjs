const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    conn.exec('tail -n 60 /var/www/APM2/storage/logs/laravel.log', (err, stream) => {
        if (err) throw err;
        stream.on('close', () => conn.end()).on('data', data => console.log(data.toString())).stderr.on('data', data => console.error(data.toString()));
    });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123' });
