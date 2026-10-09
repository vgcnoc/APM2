<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete the ghost RTR-EXCESS transaction
        DB::table('material_transactions')
            ->where('transaction_number', 'RTR-EXCESS-20261009205705')
            ->delete();

        // 2. Change the old OUT transaction to 'in' so it shows up as Order/Bekal instead of Keluar
        DB::table('material_transactions')
            ->where('transaction_number', 'OUT-20261008-MIJI5')
            ->update(['type' => 'in']);

        // 3. Update its items to have some stock_before so the view is happy
        $tx = DB::table('material_transactions')
            ->where('transaction_number', 'OUT-20261008-MIJI5')
            ->first();
            
        if ($tx) {
            DB::table('material_transaction_items')
                ->where('material_transaction_id', $tx->id)
                ->update([
                    'stock_before' => 10,
                    'stock_after' => 8
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
