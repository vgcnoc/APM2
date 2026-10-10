<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$availableOnts = \App\Models\Ont::whereIn('status', ['Sudah Set', 'Belum Set/Baru Input', 'inactive'])
    ->whereNull('customer_id')
    ->get();

echo "Total available ONTs: " . $availableOnts->count() . "\n";
foreach($availableOnts as $ont) {
    echo "ID: {$ont->id} | SN: {$ont->serial_number} | Status: {$ont->status} | Area ID: {$ont->area_id}\n";
}
