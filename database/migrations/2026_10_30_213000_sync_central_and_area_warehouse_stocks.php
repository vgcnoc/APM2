<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;
use App\Models\MaterialStock;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sinkronisasi ONT H609e (Tanpa Adaptor) (ID 269)
        // Awal: 4 pcs, Order Toko ke BC 2: 2 pcs, Sisa Pusat: 2 pcs
        $h609 = Material::find(269);
        if ($h609) {
            $h609->initial_stock = 4;
            $h609->stock = 2;
            $h609->recalcUnitTotals();
            $h609->saveQuietly();
        }
        MaterialStock::updateOrCreate(
            ['material_id' => 269, 'area_id' => 1], // BC 2
            [
                'stock' => 2,
                'initial_stock' => 2,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]
        );

        // 2. Sinkronisasi ONT F609 (Tanpa Adaptor) (ID 268)
        // Awal: 5 pcs, Order Toko: 0, Sisa Pusat: 5 pcs
        $f609 = Material::find(268);
        if ($f609) {
            $f609->initial_stock = 5;
            $f609->stock = 5;
            $f609->recalcUnitTotals();
            $f609->saveQuietly();
        }
        MaterialStock::where('material_id', 268)->delete();

        // 3. Sinkronisasi TEST FRECON 100M (ID 274)
        // Awal: 200 meter, Order Toko ke BC 3: 200 meter (PO-004 & PO-005), Sisa Pusat: 0 meter
        $frecon = Material::find(274);
        if ($frecon) {
            $frecon->meter_per_roll = 100;
            $frecon->initial_stock = 200;
            $frecon->stock = 0;
            $frecon->total_rolls = 0;
            $frecon->recalcUnitTotals();
            $frecon->saveQuietly();
        }
        MaterialStock::updateOrCreate(
            ['material_id' => 274, 'area_id' => 3], // BC 3
            [
                'stock' => 200,
                'initial_stock' => 200,
                'total_rolls' => 2,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]
        );

        // 4. Sinkronisasi TEST ONT (ID 275)
        // Awal: 2 pcs, Order Toko ke BC 3: 2 pcs (PO-004 & PO-005), Sisa Pusat: 0 pcs
        $testOnt = Material::find(275);
        if ($testOnt) {
            $testOnt->initial_stock = 2;
            $testOnt->stock = 0;
            $testOnt->recalcUnitTotals();
            $testOnt->saveQuietly();
        }
        MaterialStock::updateOrCreate(
            ['material_id' => 275, 'area_id' => 3], // BC 3
            [
                'stock' => 2,
                'initial_stock' => 2,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]
        );

        // 5. Pastikan semua produk katalog di Gudang Pusat yang belum diorder tetap utuh stok awalnya
        $catalogItems = [
            263 => ['stock' => 1, 'initial' => 1],
            264 => ['stock' => 6, 'initial' => 6],
            265 => ['stock' => 11, 'initial' => 11, 'pcs_per_pack' => 50],
            266 => ['stock' => 30, 'initial' => 30, 'pcs_per_pack' => 10],
            267 => ['stock' => 395, 'initial' => 395, 'pcs_per_pack' => 100],
            270 => ['stock' => 3, 'initial' => 3],
            271 => ['stock' => 10, 'initial' => 10],
        ];

        foreach ($catalogItems as $id => $data) {
            $item = Material::find($id);
            if ($item) {
                $item->stock = $data['stock'];
                $item->initial_stock = $data['initial'];
                if (isset($data['pcs_per_pack'])) {
                    $item->pcs_per_pack = $data['pcs_per_pack'];
                }
                $item->recalcUnitTotals();
                $item->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revert needed
    }
};

