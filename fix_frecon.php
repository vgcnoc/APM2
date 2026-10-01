<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$materials = \App\Models\Material::where('category', 'like', '%Frecon%')->orWhere('name', 'like', '%Frecon%')->get();
foreach($materials as $m) {
    if ($m->meter_per_roll > 0 && $m->stock > 100) {
        $rolls = $m->stock / $m->meter_per_roll;
        echo "Fixing {$m->name}: {$m->stock} -> {$rolls} rolls\n";
        $m->stock = $rolls;
        $m->unit = 'roll';
        $m->category = 'Kabel Drop / Frecon';
        $m->save();
        
        foreach(\App\Models\MaterialStock::where('material_id', $m->id)->get() as $s) {
            if ($s->stock > 100) {
                $s_rolls = $s->stock / $m->meter_per_roll;
                echo "  Fixing area stock {$s->area_id}: {$s->stock} -> {$s_rolls}\n";
                $s->stock = $s_rolls;
                $s->save();
            }
        }
    }
}
echo "Done\n";
