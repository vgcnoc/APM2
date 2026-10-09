<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$material = \App\Models\Material::find(15);
$stocks = \App\Models\MaterialStock::where('material_id', 15)->get();
echo "Material: " . $material->name . "\n";
echo "Gudang Utama Stock: " . $material->stock . "\n";
foreach ($stocks as $stock) {
    echo "Area ID " . $stock->area_id . " Stock: " . $stock->stock . "\n";
}
