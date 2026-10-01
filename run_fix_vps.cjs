const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
const phpScript = fs.readFileSync('fix_all_area_stock.php', 'utf8');

const script = `
cd /var/www/APM2
cat << 'EOF' > fix_all_area_stock.php
${phpScript}
EOF
php fix_all_area_stock.php
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
