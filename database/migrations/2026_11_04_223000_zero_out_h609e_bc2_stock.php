<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sesuaikan stok fisik ONT H609e (Tanpa Adaptor) [ID 269] di Area BC 2 [area_id 1] menjadi 0 pcs
        $stockBc2 = MaterialStock::where('material_id', 269)->where('area_id', 1)->first();
        if ($stockBc2) {
            $stockBc2->stock = 0;
            $stockBc2->save();
        }

        // 2. Buat catatan mutasi penyesuaian stok (Stock Opname) agar ada jejak audit trail yang jelas
        $trx = MaterialTransaction::create([
            'transaction_number' => 'ADJ-BC2-' . date('YmdHis'),
            'type' => 'out',
            'status' => 'approved',
            'date' => now()->toDateString(),
            'technician_name' => 'Super Admin',
            'purpose' => 'Penyesuaian Stok Fisik Area (Stock Opname)',
            'area' => 'BC 2',
            'area_id' => 1,
            'user_id' => 1,
            'notes' => 'Penyesuaian stok fisik ONT H609e (Tanpa Adaptor) di Area BC 2 diset 0 pcs karena unit fisik di rak/gudang kosong (stok fiktif awal PO-20261030-001).',
        ]);

        MaterialTransactionItem::create([
            'material_transaction_id' => $trx->id,
            'material_id' => 269,
            'quantity' => 2.00,
            'unit' => 'pcs',
            'price_per_unit' => 130000.00,
            'total_price' => 260000.00,
            'notes' => 'Stock opname: fisik riil kosong di gudang BC 2',
        ]);

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
