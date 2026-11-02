<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Ont;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temukan transaksi PO-20261102-015
        $trx = MaterialTransaction::where('transaction_number', 'PO-20261102-015')->first();
        if (!$trx) {
            return;
        }

        $h690Item = MaterialTransactionItem::where('material_transaction_id', $trx->id)
            ->whereHas('material', fn($q) => $q->where('name', 'like', '%H690e%'))
            ->first();

        $zvolinItem = MaterialTransactionItem::where('material_transaction_id', $trx->id)
            ->whereHas('material', fn($q) => $q->where('name', 'like', '%ZVOLIN%'))
            ->first();

        // 1. Hapus 12 ONT duplikat hasil send kedua (TBA-1793601839-...)
        Ont::where('serial_number', 'like', 'TBA-1793601839-%')
            ->whereNull('customer_id')
            ->whereNull('odp_id')
            ->delete();

        // 2. Pulihkan 12 ONT awal (V3156 - V3167 / ID 2775 - 2786)
        // Hubungkan kembali ke item transaksi dan perbarui brand ke nama material sekarang
        if ($h690Item) {
            $h690MatName = $h690Item->material?->name ?? 'ONT H690e ( Non Adaptor )';
            Ont::whereIn('id', [2775, 2776, 2777, 2778, 2779, 2780, 2781, 2782, 2783])
                ->update([
                    'brand' => $h690MatName,
                    'material_transaction_item_id' => $h690Item->id,
                    'area_id' => $trx->area_id ?? 2,
                ]);
        }

        if ($zvolinItem) {
            $zvolinMatName = $zvolinItem->material?->name ?? 'ONT ZVOLIN P613s ( Non Adaptor )';
            Ont::whereIn('id', [2784, 2785, 2786])
                ->update([
                    'brand' => $zvolinMatName,
                    'material_transaction_item_id' => $zvolinItem->id,
                    'area_id' => $trx->area_id ?? 2,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};

