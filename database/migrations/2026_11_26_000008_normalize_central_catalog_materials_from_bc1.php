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
        DB::transaction(function () {
            // Rincian 10 material master produk yang merupakan buffer Gudang Pusat
            // dan belum pernah dialokasikan / di-order oleh cabang mana pun:
            $catalogItems = [
                263 => 1.0,     // BOX ODC
                264 => 6.0,     // Joint Closure 4Core
                266 => 30.0,    // Fastconnector
                267 => 395.0,   // Cable Hole
                332 => 25.0,    // Fatchord 5m
                351 => 1.0,     // Adaptor 12v 1a
                355 => 5.0,     // Spliter 1:16
                357 => 1.0,     // Spliter 1:4
                361 => 300.0,   // Frecon 50m (Batch 13/11/2026)
                365 => 400.0,   // Cable Hole-TK
            ];

            foreach ($catalogItems as $matId => $centralStock) {
                $material = Material::find($matId);
                if (!$material) continue;

                // 1. Pastikan statusnya sebagai produk katalog Gudang Pusat dengan stok utuh
                $material->is_catalog = true;
                $material->is_migration = false;
                $material->is_active = true;
                $material->stock = (float) $centralStock;
                $material->initial_stock = max((float) ($material->initial_stock ?? 0), (float) $centralStock);
                $material->recalcUnitTotals()->save();

                // 2. Setel stok di seluruh cabang (termasuk BC-1, BC-2, BC-3, BC-4) menjadi 0
                // karena barang ini tersimpan di Gudang Pusat dan belum didistribusikan / di-order per BC
                MaterialStock::where('material_id', $matId)->update([
                    'stock' => 0.0,
                    'initial_stock' => 0.0,
                    'total_rolls' => 0.0,
                    'total_packs' => 0.0,
                    'total_pieces' => 0.0,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};

