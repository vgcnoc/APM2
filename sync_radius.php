<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$radius = app(\App\Services\RadiusService::class);
$customers = \App\Models\Customer::whereIn('status', ['active', 'suspended'])->with('ont', 'package')->get();
$count = 0;
foreach($customers as $customer) {
    if ($customer->ont && $customer->ont->free_hotspot) {
        $radius->syncCustomer($customer);
        echo "Synced: {$customer->name}\n";
        $count++;
    }
}
echo "Done sync $count customers.\n";
