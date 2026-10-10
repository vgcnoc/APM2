<?php
$transactions = App\Models\MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')->get(); 
foreach ($transactions as $transaction) { 
    $totalCost = 0; 
    foreach ($transaction->items as $item) { 
        $material = $item->material; 
        if (!$material) continue; 
        
        $pricingUnitPrice = $material->price_per_unit ?? 0; 
        $baseUnitsPerPricingUnit = 1; 
        
        if ($material->category === 'Kabel' || $material->category === 'Patchcord') { 
            $baseUnitsPerPricingUnit = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1; 
        } elseif ($material->category === 'Isolasi' || stripos($material->name, 'isolasi') !== false) { 
            $baseUnitsPerPricingUnit = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 1; 
        } elseif ($material->pcs_per_pack > 0) { 
            $baseUnitsPerPricingUnit = $material->pcs_per_pack; 
        } 
        
        $pricePerBaseUnit = $baseUnitsPerPricingUnit > 0 ? ($pricingUnitPrice / $baseUnitsPerPricingUnit) : 0; 
        $itemTotal = $item->quantity * $pricePerBaseUnit; 
        
        $item->update(['price_per_unit' => $pricePerBaseUnit, 'total_price' => $itemTotal]); 
        $totalCost += $itemTotal; 
    } 
    $transaction->update(['total_cost' => $totalCost]); 
} 
echo "Fixed";
