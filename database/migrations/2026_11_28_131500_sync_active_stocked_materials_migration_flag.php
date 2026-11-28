<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan material yang memiliki stok fisik nyata di area (> 0) tidak dianggap legacy migrasi
        DB::table('materials')
            ->where('is_migration', true)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('material_stocks')
                    ->whereColumn('material_stocks.material_id', 'materials.id')
                    ->where('material_stocks.stock', '>', 0);
            })
            ->update([
                'is_migration' => false,
                'is_active' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu reverse
    }
};
