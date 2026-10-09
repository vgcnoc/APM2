const { Client } = require('ssh2'); 
const conn = new Client(); 
conn.on('ready', () => { 
  conn.exec('php /var/www/APM2/artisan tinker --execute="echo json_encode(\\\\App\\\\Models\\\\Material::where(\'name\', \'like\', \'%klem%\')->first()->toArray());"', (err, stream) => { 
    if (err) throw err; 
    stream.on('close', () => conn.end()).on('data', d => console.log(d.toString())); 
  }); 
}).connect({host: '157.66.140.17', username: 'root', password: 'viruzs123'});
