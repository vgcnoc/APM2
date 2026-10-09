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
        $tx = DB::table('material_transactions')
            ->where('transaction_number', 'OUT-20261008-MIJI5')
            ->first();
            
        if ($tx) {
            DB::table('material_transaction_items')
                ->where('material_transaction_id', $tx->id)
                ->update([
                    'stock_before' => 0,
                    'stock_after' => 2
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
