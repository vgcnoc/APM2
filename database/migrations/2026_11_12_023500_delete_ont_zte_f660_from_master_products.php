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
        // Hapus item ONT ZTE F660 dari Master Data Produk beserta relasi stok dummy area-nya
        $materials = Material::where('name', 'ONT ZTE F660')->get();
        foreach ($materials as $m) {
            MaterialStock::where('material_id', $m->id)->delete();
            $m->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
