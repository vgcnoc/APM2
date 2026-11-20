<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\Ont;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Nol-kan sisa stok semu (ghost stock) pada Material 17: ONT NETVIBES NVB663nv9 di seluruh area
            // karena seluruh unit migrasi historis sudah terpasang ke pelanggan aktif di lapangan.
            $m17 = Material::find(17);
            if ($m17) {
                MaterialStock::where('material_id', 17)->update([
                    'stock' => 0,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]);
                $m17->update([
                    'stock' => 0,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]);
            }

            // 2. Audit dan bersihkan seluruh sisa stok migrasi ONT (is_migration = true atau ID <= 260)
            // yang di database tercatat > 0 padahal tidak memiliki unit fisik unassigned di gudang (tabel onts).
            $migOntMaterials = Material::where(function ($q) {
                $q->where('is_migration', true)->orWhere('id', '<=', 260);
            })->where(function ($q) {
                $q->where('category', 'ONT')->orWhere('category', 'like', '%ont%');
            })->get();

            foreach ($migOntMaterials as $m) {
                $areaStocks = MaterialStock::where('material_id', $m->id)->get();
                foreach ($areaStocks as $as) {
                    $cleanName = trim(str_replace(['ONT ', ' (Bekas Layak Pakai)', ' (migrasi)'], '', $m->name));
                    $availCount = Ont::where('area_id', $as->area_id)
                        ->whereNull('customer_id')
                        ->where(function ($oq) use ($m, $cleanName) {
                            $oq->where('model', $m->name)
                               ->orWhere('model', $cleanName)
                               ->orWhere('brand', $cleanName);
                        })
                        ->whereNotIn('status', ['installed', 'rusak', 'afkir'])
                        ->count();

                    if ($as->stock > 0 && $availCount == 0) {
                        $as->update([
                            'stock' => 0,
                            'total_rolls' => 0,
                            'total_packs' => 0,
                            'total_pieces' => 0,
                        ]);
                    }
                }

                // Sinkronkan stok master gudang pusat dari total area
                $newTotal = MaterialStock::where('material_id', $m->id)->sum('stock');
                $m->update([
                    'stock' => $newTotal,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => $newTotal,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

