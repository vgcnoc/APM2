<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MaterialStock;
use App\Models\Material;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seluruh material migrasi (ID <= 260 atau is_migration = true) adalah barang historis
        // yang sudah terpasang di rumah pelanggan lama sejak awal, sehingga sisa stok fisik di gudang adalah 0.
        // Riwayat 'initial_stock' (Awal Area) tetap dipertahankan untuk pencatatan audit aset.
        MaterialStock::where(function ($q) {
            $q->where('material_id', '<=', 260)
              ->orWhereHas('material', fn ($mq) => $mq->where('is_migration', true));
        })->update([
            'stock' => 0,
            'total_rolls' => 0,
            'total_packs' => 0,
            'total_pieces' => 0,
        ]);

        Material::where('id', '<=', 260)
            ->orWhere('is_migration', true)
            ->update([
                'stock' => 0,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
