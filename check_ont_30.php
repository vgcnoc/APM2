<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ont = \App\Models\Ont::find(30);
if ($ont) {
    echo "ID: {$ont->id} | SN: {$ont->serial_number} | Customer ID: {$ont->customer_id} | Status: {$ont->status} | Area ID: {$ont->area_id}\n";
} else {
    echo "ONT 30 not found\n";
}
