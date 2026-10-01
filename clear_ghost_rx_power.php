<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Ont;
use Illuminate\Support\Facades\DB;

$customers = Customer::whereHas('technicianSchedules', function($q) {
    $q->where('type', 'installation')->where('status', 'scheduled');
})->get();

$count = 0;
foreach ($customers as $customer) {
    if ($customer->ont && $customer->ont->rx_power !== null) {
        $customer->ont->update([
            'rx_power' => null,
            'start_time' => null,
            'end_time' => null,
            'photo_odp' => null,
            'photo_installation' => null,
            'photo_ont' => null,
            'photo_customer' => null,
            'photo_redaman' => null
        ]);
        $count++;
        echo "Cleared ONT data for Customer ID: {$customer->id}\n";
    }
}
echo "Total ONTs cleared: $count\n";
