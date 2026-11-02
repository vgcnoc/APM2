<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\Area;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Di tabel areas:
        // Area ID 2 = BC 1
        // Area ID 1 = BC 2
        // Area ID 3 = BC 3

        $bc1 = Area::where('name', 'BC 1')->first();
        $bc2 = Area::where('name', 'BC 2')->first();
        $bc3 = Area::where('name', 'BC 3')->first();

        $bc1Id = $bc1 ? $bc1->id : 2;
        $bc2Id = $bc2 ? $bc2->id : 1;
        $bc3Id = $bc3 ? $bc3->id : 3;

        // 1. Perbaiki Stok BC 1 untuk ONT ZVOLIN P613s (Material 282)
        // BC 1 hanya memesan 3 pcs pada PO-20261102-015 dan belum ada yang terpakai
        MaterialStock::where('material_id', 282)
            ->where('area_id', $bc1Id)
            ->update([
                'stock' => 3,
                'initial_stock' => 3,
                'total_pieces' => 3,
            ]);

        // 2. Perbaiki Stok BC 2 untuk ONT ZVOLIN P613s (Material 282)
        // BC 2 memiliki total masuk 14 pcs (PO 10 pcs + PO 2 pcs + Retur 2 pcs), terpakai 11 pcs, sisa 3 pcs
        MaterialStock::where('material_id', 282)
            ->where('area_id', $bc2Id)
            ->update([
                'stock' => 3,
                'initial_stock' => 14,
                'total_pieces' => 3,
            ]);

        // 3. Perbaiki Stok BC 3 untuk ONT ZVOLIN P613s (Material 282)
        // BC 3 memiliki total masuk 2 pcs (PO 2 pcs), belum terpakai, sisa 2 pcs
        MaterialStock::where('material_id', 282)
            ->where('area_id', $bc3Id)
            ->update([
                'stock' => 2,
                'initial_stock' => 2,
                'total_pieces' => 2,
            ]);

        // 4. Sinkronkan Master Material 282 di tabel materials
        $m = Material::find(282);
        if ($m) {
            $totalStock = MaterialStock::where('material_id', 282)->sum('stock');
            $totalInitial = MaterialStock::where('material_id', 282)->sum('initial_stock');
            $m->update([
                'stock' => $totalStock,
                'initial_stock' => $totalInitial,
            ]);
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
