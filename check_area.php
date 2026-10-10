<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$onts = \App\Models\Ont::where('status', 'Sudah Set')->get();
echo "-- ONTs Sudah Set --\n";
foreach($onts as $o) {
    echo "ONT ID: {$o->id} | SN: {$o->serial_number} | Area ID: {$o->area_id}\n";
}

$customer = \App\Models\Customer::where('name', 'like', '%Deri%')->first();
if ($customer) {
    echo "\n-- Customer Deri --\n";
    echo "Customer ID: {$customer->id} | Area ID: {$customer->area_id}\n";
}
