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
        $h609 = Material::find(269);
        if ($h609) {
            $h609->initial_stock = 4;
            $h609->stock = 2;
            $h609->recalcUnitTotals();
            $h609->saveQuietly();
        }

        // Pastikan stok di Area BC 2 (area_id = 1) adalah 2 pcs sesuai transaksi PO-20261030-001
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revert needed
    }
};
