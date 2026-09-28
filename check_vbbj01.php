<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ont;
use App\Models\Odp;

$odp = Odp::where('name', 'V-BBJ01')->first();
if ($odp) {
    $onts = Ont::where('odp_id', $odp->id)->with('customer')->get();
    echo "ODP: {$odp->name}, Total Ports: {$odp->total_ports}, Used Ports: {$odp->used_ports}\n";
    foreach ($onts as $ont) {
        $cust = $ont->customer ? "Cust: {$ont->customer->name} ({$ont->customer->status})" : "No Cust";
        echo "ONT: {$ont->serial_number}, Port: {$ont->port_number}, Status: {$ont->status}, $cust\n";
    }
} else {
    echo "ODP not found.\n";
}
