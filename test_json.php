<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customer = App\Models\Customer::with(['technicianSchedules' => function ($q) {
                $q->where('type', 'installation');
            }])->where('status', 'installing')->first();
            
if($customer) {
    echo "KEYS: \n";
    print_r(array_keys($customer->toArray()));
    echo "\n";
} else {
    echo "No customer installing\n";
}
