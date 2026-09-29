const { Client } = require('ssh2');

const conn = new Client();

const commands = `
cd /var/www/APM2
sed -i 's/APP_URL=http:\\/\\/bill.viruzs.my.id/APP_URL=https:\\/\\/bill.viruzs.my.id/g' .env
php artisan config:clear
php artisan cache:clear
`;

conn.on('ready', () => {
  console.log('Client :: ready');
  conn.exec(commands, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      console.log('Stream :: close :: code: ' + code + ', signal: ' + signal);
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
