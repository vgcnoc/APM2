<?php

use App\Models\Material;
use App\Models\MaterialStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan stok MAT #282 (ONT ZVOLIN P613s) di BC 3 tersisa 1 pcs (2 masuk PO - 1 keluar terpasang Wiwin Winarti)
        $stock282Bc3 = MaterialStock::where('material_id', 282)->where('area_id', 3)->first();
        if ($stock282Bc3) {
            $stock282Bc3->stock = 1;
            $stock282Bc3->initial_stock = 2;
            $stock282Bc3->total_pieces = 1;
            $stock282Bc3->save();
        } else {
            MaterialStock::create([
                'material_id' => 282,
                'area_id' => 3,
                'stock' => 1,
                'initial_stock' => 2,
                'total_pieces' => 1,
            ]);
        }

        // Sinkronisasi total global stok MAT #282
        $mat282 = Material::find(282);
        if ($mat282) {
            $mat282->stock = MaterialStock::where('material_id', 282)->sum('stock');
            $mat282->save();
        }

        // 2. Sinkronisasi global stok untuk MAT #305 (Adaptor 1.5A 12V)
        $mat305 = Material::find(305);
        if ($mat305) {
            $mat305->stock = MaterialStock::where('material_id', 305)->sum('stock');
            $mat305->save();
        }

        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // ignore
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
