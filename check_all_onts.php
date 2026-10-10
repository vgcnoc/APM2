<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$onts = \App\Models\Ont::all();
foreach($onts as $o) {
    echo "ONT ID: {$o->id} | SN: {$o->serial_number} | Cust ID: {$o->customer_id} | Status: {$o->status}\n";
}
