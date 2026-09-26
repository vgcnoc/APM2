const { Client } = require('ssh2');

const conn = new Client();

const commands = `
# Ensure web directory exists
mkdir -p /var/www/APM2
cd /var/www/APM2

# Pull the repository
if [ -d ".git" ]; then
    echo "Directory exists, pulling latest..."
    git pull origin main
else
    echo "Cloning repository..."
    git clone https://github.com/vgcnoc/APM2.git .
fi

# Set up environment if not exists
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# We can output the status
echo "--- Deployment Script Part 1 Done ---"
ls -la
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
