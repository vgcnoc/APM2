<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;
use App\Models\MaterialTransactionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rekonsiliasi Pigtail 50cm (ID 278):
        // Initial stock Pusat: 10 pcs.
        // Telah diambil area via Order Toko: 10 pcs (BC 3: 8 pcs, BC 1: 2 pcs).
        // Sisa stok di Gudang Pusat = 0 pcs (Habis).
        $pigtail = Material::find(278);
        if ($pigtail) {
            $pigtail->stock = 0;
            $pigtail->recalcUnitTotals()->save();
        }

        // 2. Rekonsiliasi Adaptor 1.5A 12V (ID 305):
        // Initial stock Pusat: 13 pcs.
        // Telah diambil area BC 1 via Order Toko: 13 pcs (Trx #1612: 1 pcs, Trx #1651: 12 pcs).
        // Sisa stok di Gudang Pusat = 0 pcs (Habis di pusat, sisa ada di cabang BC 1).
        $adaptor = Material::find(305);
        if ($adaptor) {
            $adaptor->stock = 0;
            $adaptor->recalcUnitTotals()->save();
        }

        // 3. Sinkronkan semua material katalog lainnya secara dinamis
        $catalogMaterials = Material::where('is_catalog', true)->get();
        foreach ($catalogMaterials as $mat) {
            // Kecuali ID 284 yang ONT Rusak
            if ($mat->id === 284) {
                continue;
            }

            $poTotal = (float) MaterialTransactionItem::where('material_id', $mat->id)
                ->whereHas('transaction', function ($q) {
                    $q->where('type', 'in')
                      ->where('status', 'approved')
                      ->where(function ($sq) {
                          $sq->where('transaction_number', 'like', 'PO-%')
                             ->orWhere('purpose', 'Pembelian Toko / Supplier');
                      });
                })
                ->sum('quantity');

            if ((float) $mat->initial_stock > 0) {
                $reconciledStock = max(0, (float) $mat->initial_stock - $poTotal);
                if (abs((float) $mat->stock - $reconciledStock) > 0.001) {
                    $mat->stock = $reconciledStock;
                    $mat->recalcUnitTotals()->save();
                }
            }
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

