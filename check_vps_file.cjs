const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  conn.exec(`cd /var/www/APM2 && php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
    
    $dashOnts = App\\Models\\Ont::where("pppoe_user", "-")
        ->orWhere("hotspot_user", "-")
        ->orWhere("pppoe_password", "-")
        ->orWhere("hotspot_password", "-")
        ->get();
    echo "ONTs with dash (-): " . $dashOnts->count() . "\n";
    foreach ($dashOnts->take(10) as $ont) {
        $c = App\\Models\\Customer::find($ont->customer_id);
        echo "ONT ID {$ont->id}, Cust: " . ($c ? "{$c->id} - {$c->name}" : "none") . 
             ", Cust pppoe_user: " . ($c->pppoe_username ?? "null") . 
             ", ONT pppoe_user: {$ont->pppoe_user}, hotspot_user: {$ont->hotspot_user}\n";
    }
  '`, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('data', d => out += d);
    stream.stderr.on('data', d => out += 'STDERR: ' + d);
    stream.on('close', () => {
      console.log('Result:\n' + out);
      conn.end();
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123',
  readyTimeout: 30000
});

