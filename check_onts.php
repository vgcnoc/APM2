<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$onts = \App\Models\Ont::all();
echo 'Total ONTs: ' . $onts->count() . "\n";
foreach($onts as $o) {
    echo $o->id . ' | SN: ' . $o->serial_number . ' | Status: ' . $o->status . "\n";
}
