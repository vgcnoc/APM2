<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reconcile BC 1 & BC 2 ONT ZVOLIN P613s (Material 282)
        // PO 15 ordered 3 pcs for BC 1. Redundant PO updates inflated stock to 27.
        MaterialStock::where('material_id', 282)
            ->where('area_id', 1)
            ->update([
                'stock' => 3,
                'initial_stock' => 3,
                'total_pieces' => 3,
            ]);

        // PO 16 ordered 14 pcs for BC 2. 12 were used, true remaining is 2.
        MaterialStock::where('material_id', 282)
            ->where('area_id', 2)
            ->update([
                'stock' => 2,
                'initial_stock' => 14,
                'total_pieces' => 2,
            ]);

        // 2. Fix Frecon 200m (Material 283) in OUT-INSTALASI-20261101155651
        // Technician inputted 1 roll, which incorrectly saved as 1 meter instead of 200 meters.
        $freconTrx = MaterialTransaction::where('transaction_number', 'OUT-INSTALASI-20261101155651')->first();
        if ($freconTrx) {
            MaterialTransactionItem::where('material_transaction_id', $freconTrx->id)
                ->where('material_id', 283)
                ->update([
                    'quantity' => 200,
                    'unit' => 'meter',
                ]);
        }

        // BC 2 received 2 rolls (400m). Both rolls (200m each) were installed -> true stock is 0m.
        MaterialStock::where('material_id', 283)
            ->where('area_id', 2)
            ->update([
                'stock' => 0,
                'initial_stock' => 400,
                'total_rolls' => 0,
            ]);

        // 3. Fix kabel fo 1c - 60m (Material 289) in OUT-INSTALASI-20261102155915
        // Technician inputted 1 roll (60m), which incorrectly saved as 1 meter.
        $cableTrx = MaterialTransaction::where('transaction_number', 'OUT-INSTALASI-20261102155915')->first();
        if ($cableTrx) {
            MaterialTransactionItem::where('material_transaction_id', $cableTrx->id)
                ->where('material_id', 289)
                ->update([
                    'quantity' => 60,
                    'unit' => 'meter',
                ]);

            // Fix Pigtail unit in same transaction
            MaterialTransactionItem::where('material_transaction_id', $cableTrx->id)
                ->where('unit', 'cm')
                ->update([
                    'unit' => 'pcs',
                ]);
        }

        // BC 3 had 1 roll of 60m, which was installed -> true stock is 0.
        MaterialStock::where('material_id', 289)
            ->where('area_id', 3)
            ->update([
                'stock' => 0,
                'initial_stock' => 60,
                'total_rolls' => 0,
            ]);

        // 4. BC 3 kabel fo 1c - 100m (Material 297): 3 rolls received (300m), 0 used -> true stock 300m
        MaterialStock::where('material_id', 297)
            ->where('area_id', 3)
            ->update([
                'stock' => 300,
                'initial_stock' => 300,
                'total_rolls' => 3,
            ]);

        // 5. BC 3 kabel fo 1c - 50m (Material 301): 1 roll received (50m), 0 used -> true stock 50m
        MaterialStock::where('material_id', 301)
            ->where('area_id', 3)
            ->update([
                'stock' => 50,
                'initial_stock' => 50,
                'total_rolls' => 1,
            ]);

        // 6. Clamp any negative stocks to 0
        MaterialStock::where('stock', '<', 0)->update(['stock' => 0]);
        Material::where('stock', '<', 0)->update(['stock' => 0]);

        // 7. Synchronize total_rolls on all cable materials
        $cableMaterials = Material::where('meter_per_roll', '>', 0)->get();
        foreach ($cableMaterials as $mat) {
            $mpr = (float) $mat->meter_per_roll;
            if ($mpr > 0) {
                $mat->total_rolls = max(0, (float) $mat->stock / $mpr);
                $mat->save();

                foreach ($mat->stocks as $ms) {
                    $ms->total_rolls = max(0, (float) $ms->stock / $mpr);
                    $ms->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for stock reconciliation
    }
};

