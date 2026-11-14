const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH Connection established');
    const cmd = [
        'cd /var/www/APM2',
        'git fetch origin main',
        'git reset --hard origin/main',
        'php artisan migrate --force',
        'php artisan optimize:clear',
        'php artisan config:cache',
        'php artisan route:cache',
        'php artisan view:cache',
        'chown -R www-data:www-data /var/www/APM2/storage /var/www/APM2/bootstrap/cache /var/www/APM2/public/build',
        'git log -1 --oneline'
    ].join(' && ');

    conn.exec(cmd, (err, stream) => {
        if (err) {
            console.error('Exec error:', err);
            conn.end();
            process.exit(1);
        }
        stream.on('close', (code, signal) => {
            console.log(`\nDeployment finished with code: ${code}`);
            conn.end();
            process.exit(code);
        }).on('data', (data) => {
            process.stdout.write(data);
        }).stderr.on('data', (data) => {
            process.stderr.write(data);
        });
    });
}).on('error', (err) => {
    console.error('Handled SSH error:', err.message);
    process.exit(1);
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: process.env.VPS_PASSWORD || 'viruzs123',
    readyTimeout: 30000,
});
