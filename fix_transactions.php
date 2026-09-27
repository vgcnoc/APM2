<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MaterialTransaction;

$transactions = MaterialTransaction::where('type', 'out')->get();
$fixedCount = 0;

foreach ($transactions as $t) {
    $totalCost = 0;
    $changed = false;
    foreach ($t->items as $item) {
        $mat = $item->material;
        if (!$mat) {
            $totalCost += $item->total_price;
            continue;
        }
        
        $ppu = $mat->selling_price; // Changed from price_per_unit to selling_price
        if ($mat->category == 'Kabel' && strtolower($item->unit) == 'meter') {
            $mpr = $mat->meter_per_roll > 0 ? $mat->meter_per_roll : 1000;
            $ppu = $ppu / $mpr;
        }
        
        $correctTotalPrice = $item->quantity * $ppu;
        if (abs($item->total_price - $correctTotalPrice) > 0.01 || $item->price_per_unit != $ppu) {
            $item->price_per_unit = $ppu;
            $item->total_price = $correctTotalPrice;
            $item->save();
            $changed = true;
        }
        $totalCost += $item->total_price;
    }
    
    if ($changed || abs($t->total_cost - $totalCost) > 0.01) {
        $t->total_cost = $totalCost;
        $t->save();
        $fixedCount++;
        echo "Fixed transaction: {$t->transaction_number}\n";
    }
}

echo "Done. Fixed $fixedCount transactions.\n";
