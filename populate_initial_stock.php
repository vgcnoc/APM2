<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Material;
use App\Models\MaterialTransactionItem;

foreach(Material::all() as $m) {
    $taken = MaterialTransactionItem::where('material_id', $m->id)->sum('quantity');
    if($m->category == 'Kabel') {
        $mpr = $m->meter_per_roll > 0 ? $m->meter_per_roll : 1000;
        $takenRolls = MaterialTransactionItem::where('material_id', $m->id)->whereIn('unit', ['roll', 'rol'])->sum('quantity');
        $takenMeters = MaterialTransactionItem::where('material_id', $m->id)->whereNotIn('unit', ['roll', 'rol'])->sum('quantity');
        $taken = ($takenRolls * $mpr) + $takenMeters;
    }
    $m->initial_stock = $m->stock + $taken;
    $m->save();
}
echo "Done\n";
