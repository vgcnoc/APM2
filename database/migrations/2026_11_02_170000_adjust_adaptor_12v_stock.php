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
        $material = Material::where('name', 'Adaptor 1.5A 12V')->first();
        if ($material) {
            // Gudang Pusat: Awal 42 - 12 (Order ke BC 1) = 30 pcs
            $material->update([
                'stock' => 30,
                'initial_stock' => 42,
            ]);

            // Area BC 1: Awal order 12 pcs - 1 pcs (dipakai di PGK Damar) = 11 pcs
            MaterialStock::where('material_id', $material->id)
                ->where('area_id', 2)
                ->update([
                    'stock' => 11,
                    'initial_stock' => 12,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
