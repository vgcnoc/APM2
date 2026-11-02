<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransactionItem;
use App\Models\Area;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bc1 = Area::where('name', 'BC 1')->first();
        $bc1Id = $bc1 ? $bc1->id : 2;

        $source = Material::find(270); // Adaptor 1.5A (duplikat lama)
        $target = Material::find(305); // Adaptor 1.5A 12V (resmi dari PO 15)

        if ($source && $target) {
            // 1. Alihkan seluruh transaksi dari 270 ke 305
            MaterialTransactionItem::where('material_id', $source->id)->update([
                'material_id' => $target->id,
                'material_name' => $target->name,
            ]);

            // 2. Alihkan foreign keys jika ada
            $relationTables = [
                'customer_installed_materials',
                'customer_material_dismantles',
                'pgk_materials',
                'pgs_materials',
                'pgj_materials',
                'pgk_material_requests',
                'pgs_material_requests',
                'infrastructure_work_order_materials',
                'infrastructure_work_order_material_requests',
                'network_maintenance_materials',
            ];

            foreach ($relationTables as $table) {
                if (Schema::hasTable($table)) {
                    \Illuminate\Support\Facades\DB::table($table)
                        ->where('material_id', $source->id)
                        ->update(['material_id' => $target->id]);
                }
            }

            // 3. Hapus stok area dari source (270)
            MaterialStock::where('material_id', $source->id)->delete();

            // 4. Hapus source material duplikat
            $source->delete();

            // 5. Perbarui stok BC 1 untuk target (305)
            // Masuk: PO 1 (1 pcs) + PO 15 (12 pcs) = 13 pcs
            // Terpakai di BC 1: 2 pcs (PGK Damar 2x)
            // Sisa di BC 1: 11 pcs
            MaterialStock::updateOrCreate(
                ['material_id' => $target->id, 'area_id' => $bc1Id],
                [
                    'stock' => 11,
                    'initial_stock' => 13,
                    'total_pieces' => 11,
                ]
            );

            // Bersihkan stok minus di area lain jika ada
            MaterialStock::where('material_id', $target->id)->where('stock', '<', 0)->update(['stock' => 0]);

            $totalStock = MaterialStock::where('material_id', $target->id)->sum('stock');
            $totalInitial = MaterialStock::where('material_id', $target->id)->sum('initial_stock');

            $target->update([
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
