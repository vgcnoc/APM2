const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
  // Add a temp debug API route to see what Inertia actually shares
  const addRoute = `cd /var/www/APM2 && grep -q "debug-props" routes/web.php || sed -i '/require __DIR__/i\\
Route::get(\"/debug-props\", function() {\\
    $logo = \\\\App\\\\Models\\\\Setting::get(\"app_logo\");\\
    return response()->json([\\
        \"app_logo_raw\" => $logo,\\
        \"app_logo_url\" => $logo ? asset(\"storage/\" . $logo) : null,\\
        \"app_name\" => \\\\App\\\\Models\\\\Setting::get(\"app_name\", \"ISP Manager\"),\\
        \"file_exists\" => $logo ? \\\\Illuminate\\\\Support\\\\Facades\\\\Storage::disk(\"public\")->exists($logo) : false,\\
    ]);\\
});\\
' routes/web.php && php artisan optimize:clear && echo "Route added" && curl -sk https://bill.viruzs.my.id/debug-props`;
  conn.exec(addRoute, (err, stream) => {
    if (err) throw err;
    let out = '';
    stream.on('close', () => { console.log(out); conn.end(); })
      .on('data', (d) => { out += d.toString(); })
      .stderr.on('data', (d) => { out += d.toString(); });
  });
}).connect({ host: '157.66.140.17', port: 22, username: 'root', password: 'viruzs123', readyTimeout: 30000 });
