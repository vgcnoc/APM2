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
        // 1. Dapatkan semua ID transaksi material yang berasal dari migrasi
        $migrationTransactionIds = DB::table('material_transactions')
            ->where(function ($query) {
                $query->where('transaction_number', 'like', '%MIG%')
                    ->orWhere('purpose', 'like', '%migrasi%')
                    ->orWhere('purpose', 'like', '%Migrasi%')
                    ->orWhere('notes', 'like', '%migrasi%')
                    ->orWhere('source_type', 'migrasi');
            })
            ->pluck('id');

        if ($migrationTransactionIds->isNotEmpty()) {
            // 2. Set total_cost = 0 pada semua transaksi migrasi
            DB::table('material_transactions')
                ->whereIn('id', $migrationTransactionIds)
                ->update(['total_cost' => 0]);

            // 3. Set price_per_unit = 0 dan total_price = 0 pada item transaksi migrasi
            DB::table('material_transaction_items')
                ->whereIn('material_transaction_id', $migrationTransactionIds)
                ->update([
                    'price_per_unit' => 0,
                    'total_price' => 0,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data nominal migrasi tidak perlu dikembalikan karena memang tidak memiliki tagihan riil
    }
};
