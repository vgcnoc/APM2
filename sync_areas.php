<?php
// sync_areas.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Area;
use App\Models\Customer;
use App\Models\Material;
use App\Models\MaterialTransaction;

// Get all unique areas from existing string columns
$areas = collect();

$customerAreas = Customer::whereNotNull('area')->where('area', '!=', '')->pluck('area');
$materialAreas = Material::whereNotNull('area')->where('area', '!=', '')->pluck('area');
$mtAreas = MaterialTransaction::whereNotNull('area')->where('area', '!=', '')->pluck('area');

$allStringAreas = $customerAreas->concat($materialAreas)->concat($mtAreas)->unique();

echo "Found areas: " . implode(', ', $allStringAreas->toArray()) . "\n";

foreach ($allStringAreas as $areaName) {
    Area::firstOrCreate(['name' => $areaName]);
}

$areaMap = Area::pluck('id', 'name')->toArray();

echo "Area map: " . json_encode($areaMap) . "\n";

$updatedCustomers = 0;
foreach (Customer::whereNull('area_id')->whereNotNull('area')->where('area', '!=', '')->cursor() as $c) {
    if (isset($areaMap[$c->area])) {
        $c->update(['area_id' => $areaMap[$c->area]]);
        $updatedCustomers++;
    }
}
echo "Updated Customers: $updatedCustomers\n";

$updatedMaterials = 0;
foreach (Material::whereNull('area_id')->whereNotNull('area')->where('area', '!=', '')->cursor() as $m) {
    if (isset($areaMap[$m->area])) {
        $m->update(['area_id' => $areaMap[$m->area]]);
        $updatedMaterials++;
    }
}
echo "Updated Materials: $updatedMaterials\n";

$updatedTrx = 0;
foreach (MaterialTransaction::whereNull('area_id')->whereNotNull('area')->where('area', '!=', '')->cursor() as $t) {
    if (isset($areaMap[$t->area])) {
        $t->update(['area_id' => $areaMap[$t->area]]);
        $updatedTrx++;
    }
}
echo "Updated MaterialTransactions: $updatedTrx\n";

echo "Done syncing areas.\n";
