<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Reset all Area Stock to 0
\App\Models\MaterialStock::query()->update(['stock' => 0]);

// 2. Loop through all MaterialTransactions that have an area_id
$transactions = \App\Models\MaterialTransaction::whereNotNull('area_id')->with('items.material')->get();

foreach ($transactions as $trx) {
    foreach ($trx->items as $item) {
        $material = $item->material;
        if (!$material) continue;
        
        $materialStock = \App\Models\MaterialStock::firstOrCreate(
            ['material_id' => $material->id, 'area_id' => $trx->area_id],
            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
        );
        
        // Calculate fundamental deduction
        $qty = $item->quantity;
        $unitLower = strtolower($item->unit);
        
        if (in_array($material->category, ['Kabel Drop', 'Kabel Drop / Frecon', 'Kabel Frecon', 'Kabel'])) {
            if ($unitLower === 'roll' || $unitLower === 'rol' || $unitLower === 'pcs') {
                $mpr = floatval($material->meter_per_roll) > 0 ? floatval($material->meter_per_roll) : 1000;
                $qty = $qty * $mpr;
            }
        } else if ($material->category === 'Paku Klem') {
            if ($unitLower === 'pack' || $unitLower === 'bungkus') {
                $ppp = floatval($material->pcs_per_pack) > 0 ? floatval($material->pcs_per_pack) : 1;
                $qty = $qty * $ppp;
            }
        } else if ($material->category === 'Isolasi') {
            if ($unitLower === 'pcs' || $unitLower === 'pcs (utuh)') {
                $cpp = floatval($material->cm_per_pcs) > 0 ? floatval($material->cm_per_pcs) : 50;
                $qty = $qty * $cpp;
            }
        }
        
        // Surat Jalan INCREASES Area Stock
        $materialStock->stock += $qty;
        $materialStock->save();
        
        echo "Added {$qty} to {$material->name} in Area {$trx->area_id}\n";
    }
}
echo "Done fixing all Area Stocks!\n";
