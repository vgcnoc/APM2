<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\Expense;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Koreksi item Solasi Internasional pada PO-20261104-014 (Trx #1854)
        $item = MaterialTransactionItem::find(3780);
        if ($item && $item->material_id == 323) {
            $item->total_price = 19400.00;
            $item->price_per_unit = 19400.00 / (float) $item->quantity; // ~3.2333 per cm
            $item->save();
        }

        // Koreksi total_cost transaksi PO-20261104-014
        $trx = MaterialTransaction::find(1854);
        if ($trx) {
            $sum = (float) MaterialTransactionItem::where('material_transaction_id', $trx->id)->sum('total_price');
            $trx->total_cost = $sum;
            $trx->save();
        }

        // Koreksi catatan kas pengeluaran Expense #70
        $exp = Expense::find(70);
        if ($exp) {
            $exp->amount = 135600;
            $exp->save();
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
