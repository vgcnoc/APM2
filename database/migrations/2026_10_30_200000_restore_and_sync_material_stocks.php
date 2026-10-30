<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\MaterialStock;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data restore untuk produk katalog non-migrasi yang sebelumnya sempat ternolkan
        $catalogRestorations = [
            263 => ['name' => 'BOX ODC', 'stock' => 1, 'initial_stock' => 1],
            264 => ['name' => 'Joint Closure 4Core', 'stock' => 6, 'initial_stock' => 6],
            265 => ['name' => 'Protection Medium', 'stock' => 11, 'initial_stock' => 11, 'pcs_per_pack' => 50],
            266 => ['name' => 'Fastconnector', 'stock' => 30, 'initial_stock' => 30, 'pcs_per_pack' => 10],
            267 => ['name' => 'Cable Hole', 'stock' => 395, 'initial_stock' => 395, 'pcs_per_pack' => 100],
            268 => ['name' => 'ONT F609 (Tanpa Adaptor)', 'stock' => 3, 'initial_stock' => 3],
            270 => ['name' => 'Adaptor 1.5A', 'stock' => 3, 'initial_stock' => 3],
            271 => ['name' => 'Adaptor 2A', 'stock' => 10, 'initial_stock' => 10],
        ];

        foreach ($catalogRestorations as $id => $data) {
            $m = Material::find($id);
            if ($m) {
                $m->stock = $data['stock'];
                $m->initial_stock = $data['initial_stock'];
                if (isset($data['pcs_per_pack'])) {
                    $m->pcs_per_pack = $data['pcs_per_pack'];
                }
                $m->recalcUnitTotals();
                $m->saveQuietly();
            }
        }

        // 2. Koreksi stok untuk material hasil transaksi Order Toko yang sempat salah terkurangi
        // ID 274: TEST FRECON 100M -> PO-004 (100m) + PO-005 (100m) = 200m ke BC 3
        $frecon = Material::find(274);
        if ($frecon) {
            $frecon->stock = 200;
            $frecon->initial_stock = 200;
            $frecon->meter_per_roll = 100;
            $frecon->total_rolls = 2;
            $frecon->recalcUnitTotals();
            $frecon->saveQuietly();

            // Sinkronkan stok di Area BC 3 (area_id = 3)
            MaterialStock::updateOrCreate(
                ['material_id' => 274, 'area_id' => 3],
                [
                    'stock' => 200,
                    'initial_stock' => 200,
                    'total_rolls' => 2,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]
            );
        }

        // ID 275: TEST ONT -> PO-004 (1 pcs) + PO-005 (1 pcs) = 2 pcs ke BC 3
        $testOnt = Material::find(275);
        if ($testOnt) {
            $testOnt->stock = 2;
            $testOnt->initial_stock = 2;
            $testOnt->recalcUnitTotals();
            $testOnt->saveQuietly();

            // Sinkronkan stok di Area BC 3 (area_id = 3)
            MaterialStock::updateOrCreate(
                ['material_id' => 275, 'area_id' => 3],
                [
                    'stock' => 2,
                    'initial_stock' => 2,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]
            );
        }

        // ID 269: ONT H609e (Tanpa Adaptor) -> PO-001 (2 pcs) ke BC 2 (area_id = 1)
        $ontH609 = Material::find(269);
        if ($ontH609) {
            $ontH609->stock = 2;
            $ontH609->initial_stock = 2;
            $ontH609->recalcUnitTotals();
            $ontH609->saveQuietly();

            MaterialStock::updateOrCreate(
                ['material_id' => 269, 'area_id' => 1],
                [
                    'stock' => 2,
                    'initial_stock' => 2,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]
            );
        }

        // 3. Pastikan seluruh material non-migrasi (ID > 260) memiliki total konversi unit yang konsisten
        $allNonMigrated = Material::where('id', '>', 260)->get();
        foreach ($allNonMigrated as $mat) {
            $mat->recalcUnitTotals();
            $mat->saveQuietly();
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

