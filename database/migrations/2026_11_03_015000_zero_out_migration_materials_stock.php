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
        // Material migrasi pelanggan lama dari CSV (ID <= 260) adalah barang historis
        // yang sudah terpasang di rumah pelanggan lama sejak awal, sehingga sisa stok fisik di gudang adalah 0.
        // Material fisik area baru (ID > 260) TIDAK boleh di-nol-kan.
        MaterialStock::where('material_id', '<=', 260)->update([
            'stock' => 0,
            'total_rolls' => 0,
            'total_packs' => 0,
            'total_pieces' => 0,
        ]);

        Material::where('id', '<=', 260)->update([
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
