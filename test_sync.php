<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$radius = app(\App\Services\RadiusService::class);
$customer = \App\Models\Customer::where('name', 'Yosep ihsan')->first();
var_dump($customer->name);
var_dump($customer->ont->free_hotspot);
var_dump($customer->ont->hotspot_user);
$accounts = $radius->customerAccounts($customer);
var_dump($accounts);
$radius->syncCustomer($customer);
echo "Done sync.\n";
