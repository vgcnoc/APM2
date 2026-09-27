const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec('systemctl restart apache2 || systemctl restart nginx || systemctl restart php8.2-fpm || systemctl restart php8.1-fpm', (err, stream) => {
    let data = '';
    stream.on('data', d => data += d).on('close', () => {
      console.log('Done', data);
      conn.end();
    });
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123', readyTimeout: 30000 });
