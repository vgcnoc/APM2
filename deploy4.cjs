const { Client } = require('ssh2');

const conn = new Client();

const commands = `
echo "Installing MariaDB and PHP MySQL driver..."
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-server php-mysql php-mbstring php-xml php-curl php-zip unzip

# Start MariaDB
systemctl enable mariadb
systemctl start mariadb

# Create database and user
mariadb -e "CREATE DATABASE IF NOT EXISTS isp_management;"
mariadb -e "CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'viruzs123';"
mariadb -e "GRANT ALL PRIVILEGES ON isp_management.* TO 'admin'@'localhost';"
mariadb -e "FLUSH PRIVILEGES;"

# Update .env with new DB credentials
cd /var/www/APM2
sed -i 's/DB_USERNAME=root/DB_USERNAME=admin/g' .env
sed -i 's/DB_PASSWORD=viruzs123/DB_PASSWORD=viruzs123/g' .env

# Run migrations
echo "Running Migrations..."
php artisan migrate --force
php artisan db:seed --force

# Restart PHP-FPM and Nginx
PHP_FPM_SVC=$(systemctl list-units --type=service | grep php | grep fpm | awk '{print $1}')
if [ ! -z "$PHP_FPM_SVC" ]; then
    systemctl restart $PHP_FPM_SVC
fi
systemctl restart nginx

echo "--- Full Setup Complete ---"
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
