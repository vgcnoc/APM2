<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cari ID material non-migrasi (ID > 260) yang BELUM PERNAH dilakukan Order Toko
        $orderTokoMaterialIds = DB::table('material_transaction_items')
            ->join('material_transactions', 'material_transactions.id', '=', 'material_transaction_items.material_transaction_id')
            ->where('material_transactions.purpose', 'Pembelian Toko / Supplier')
            ->pluck('material_transaction_items.material_id')
            ->unique()
            ->toArray();

        // Target: hanya data produk baru / non-migrasi (ID > 260) yang belum pernah di-order toko
        $dummyMaterials = DB::table('materials')
            ->where('id', '>', 260)
            ->whereNotIn('id', $orderTokoMaterialIds)
            ->pluck('id');

        if ($dummyMaterials->isNotEmpty()) {
            // 1. Nol-kan stok dan stok awal pada material tersebut (karena murni masih katalog produk, belum order toko)
            DB::table('materials')
                ->whereIn('id', $dummyMaterials)
                ->update([
                    'stock' => 0,
                    'initial_stock' => 0,
                    'total_rolls' => 0,
                    'total_packs' => 0,
                    'total_pieces' => 0,
                ]);

            // 2. Hapus stok area buatan yang terisi otomatis saat input produk baru
            DB::table('material_stocks')
                ->whereIn('material_id', $dummyMaterials)
                ->delete();

            // 3. Hapus transaksi buatan otomatis (Stok Awal Produk Baru) untuk material non-migrasi tersebut
            $initTxIds = DB::table('material_transactions')
                ->where('purpose', 'Stok Awal Produk Baru')
                ->pluck('id');

            if ($initTxIds->isNotEmpty()) {
                DB::table('material_transaction_items')
                    ->whereIn('material_transaction_id', $initTxIds)
                    ->whereIn('material_id', $dummyMaterials)
                    ->delete();

                // Hapus header transaksi jika sudah kosong
                $emptyTxIds = DB::table('material_transactions')
                    ->whereIn('id', $initTxIds)
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('material_transaction_items')
                            ->whereColumn('material_transaction_items.material_transaction_id', 'material_transactions.id');
                    })
                    ->pluck('id');

                if ($emptyTxIds->isNotEmpty()) {
                    DB::table('material_transactions')->whereIn('id', $emptyTxIds)->delete();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revert needed
    }
};

