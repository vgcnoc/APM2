<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

$statuses = Customer::select('status', \DB::raw('count(*) as total'))
    ->groupBy('status')
    ->get();

echo "Customer statuses:\n";
foreach ($statuses as $s) {
    echo "- " . $s->status . ": " . $s->total . "\n";
}
