<?php

use App\Models\Area;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bc2Area = Area::find(1);
        $bc2Name = $bc2Area?->name ?? 'BC 2';

        // 1. Bersihkan item duplikat MAT #282 pada TRX #1609 (OUT-PGS-2026103123343454 untuk PGS-20261031-0002)
        // yang ter-submit ganda 3 menit sebelum TRX #1611 (OUT-PGS-2026103123371854).
        $trx1609 = MaterialTransaction::where('id', 1609)
            ->where('transaction_number', 'OUT-PGS-2026103123343454')
            ->first();

        if ($trx1609) {
            MaterialTransactionItem::where('material_transaction_id', $trx1609->id)
                ->where('material_id', 282)
                ->delete();

            if (MaterialTransactionItem::where('material_transaction_id', $trx1609->id)->count() === 0) {
                $trx1609->skipStockRestoreOnDelete = true;
                $trx1609->delete();
            }
        }

        // 2. Kaitkan transaksi pencatatan Rusak MAT #282 oleh Aep / Aep S (BC 2) yang sebelumnya area_id = NULL
        $trx1626 = MaterialTransaction::where('id', 1626)
            ->where('transaction_number', 'RET-20261101-015')
            ->whereNull('area_id')
            ->first();
        if ($trx1626) {
            $trx1626->update([
                'area_id' => 1,
                'area' => $bc2Name,
            ]);
        }

        $trx1807 = MaterialTransaction::where('id', 1807)
            ->where('transaction_number', 'OUT-20261104-001')
            ->whereNull('area_id')
            ->first();
        if ($trx1807) {
            $trx1807->update([
                'area_id' => 1,
                'area' => $bc2Name,
            ]);

            MaterialTransactionItem::where('material_transaction_id', $trx1807->id)
                ->where('material_id', 282)
                ->update([
                    'stock_before' => 3,
                    'stock_after' => 2,
                ]);
        }

        // 3. Sesuaikan stok fisik & stok awal MAT #282 (ONT ZVOLIN P613s Non Adaptor) di BC 2 (area_id = 1):
        // Order Toko BC 2 = 12 pcs (10 + 2), Terpasang PSB = 9 pcs, Rusak/Afkir (V3135) = 1 pcs -> Sisa Siap Pasang = 2 pcs.
        MaterialStock::updateOrCreate(
            [
                'material_id' => 282,
                'area_id' => 1,
            ],
            [
                'initial_stock' => 12,
                'stock' => 2,
                'total_pieces' => 2,
                'total_rolls' => 0,
                'total_packs' => 0,
            ]
        );

        // 4. Sesuaikan stok katalog master MAT #282 (Gudang Pusat: 17 pcs masuk dan 17 pcs terdistribusi ke BC 1/2/3 -> sisa pusat 0)
        $mat282 = Material::find(282);
        if ($mat282) {
            $totalOrderedByAreas = (float) MaterialStock::where('material_id', 282)->sum('initial_stock');
            $mat282->initial_stock = $totalOrderedByAreas > 0 ? $totalOrderedByAreas : 17;
            $mat282->stock = max(0.0, (float) $mat282->initial_stock - $totalOrderedByAreas);
            $mat282->recalcUnitTotals()->save();
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

