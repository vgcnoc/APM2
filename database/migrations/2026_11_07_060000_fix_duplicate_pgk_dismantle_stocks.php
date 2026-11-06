<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\CustomerMaterialDismantle;
use App\Models\Ont;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbaiki stok ONT NVB663nv9 XPON (Material ID 7) di BC 2 (Area ID 1) kembali ke fisik asli (1 unit)
        $mat7 = Material::find(7);
        if ($mat7) {
            $mat7->update(['stock' => 1.00]);
        }

        $stock7Area1 = MaterialStock::where('material_id', 7)->where('area_id', 1)->first();
        if ($stock7Area1) {
            $stock7Area1->update([
                'stock' => 1.00,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]);
        }

        // 2. Void transaksi serah terima duplikat TX #1911 (RET-DISM-PGK-20261106-004)
        $tx1911 = MaterialTransaction::find(1911);
        if ($tx1911) {
            $tx1911->update([
                'notes' => '[DUPLIKAT VOID] Serah terima ganda tiket PGK-20261105-0002 Yuliani. Stok dikoreksi kembali ke fisik asli 1 unit.',
            ]);
            MaterialTransactionItem::where('material_transaction_id', 1911)->update([
                'quantity' => 0.00,
                'notes' => '[DUPLIKAT VOID] Duplikasi serah terima dari TX #1891',
            ]);
        }

        // 3. Void transaksi double-count TX #1914 (IN-REPAIR-20261106-007)
        $tx1914 = MaterialTransaction::find(1914);
        if ($tx1914) {
            $tx1914->update([
                'notes' => '[DOUBLE COUNT DIKOREKSI] Unit HWTC07A170CF sudah direstock via RET-DISM-PGK-20261105-017 (#1891). Stok disesuaikan.',
            ]);
            MaterialTransactionItem::where('material_transaction_id', 1914)->update([
                'quantity' => 0.00,
                'notes' => '[DOUBLE COUNT DIKOREKSI] Unit sudah memiliki stok aktif dari serah terima bongkaran',
            ]);
        }

        // 4. Update data CustomerMaterialDismantle duplikat agar tidak membingungkan audit
        CustomerMaterialDismantle::where('id', 7)->update([
            'disposition' => 'duplicate_void',
            'damage_reason' => '[DUPLIKAT VOID] Duplikasi serah terima tiket PGK-20261105-0002 Yuliani.',
        ]);

        CustomerMaterialDismantle::where('id', 2)->update([
            'disposition' => 'duplicate_void',
            'damage_reason' => '[DUPLIKAT VOID] Duplikasi serah terima tiket PGK-20261101-0006 Damar.',
        ]);

        // 5. Hubungkan ONT #909 (HWTC07A170CF) ke item transaksi bongkaran yang sah (TX #1891 item #3850)
        $ont909 = Ont::where('serial_number', 'HWTC07A170CF')->first();
        if ($ont909) {
            $genuineItem = MaterialTransactionItem::where('material_transaction_id', 1891)->first();
            $ont909->update([
                'status' => 'Sudah Set',
                'material_transaction_item_id' => $genuineItem?->id ?? 3850,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for data correction migration
    }
};

