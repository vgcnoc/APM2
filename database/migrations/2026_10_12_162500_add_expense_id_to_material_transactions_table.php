<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('material_transactions', 'expense_id')) {
                $table->foreignId('expense_id')->nullable()->after('user_id')->constrained('expenses')->nullOnDelete();
            }
        });

        // Sinkronisasi data pembelian toko yang sudah ada ke tabel expenses
        try {
            $catId = DB::table('expense_categories')->where('code', 'MAT')->value('id');
            if (!$catId) {
                $catId = DB::table('expense_categories')->insertGetId([
                    'code' => 'MAT',
                    'name' => 'Pembelian Material & Restock',
                    'description' => 'Pembelian kabel, ONT, dropcore, aksesoris dari toko/supplier',
                    'color' => 'indigo',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $pastOrders = DB::table('material_transactions')
                ->where('purpose', 'Pembelian Toko / Supplier')
                ->where('total_cost', '>', 0)
                ->whereNull('expense_id')
                ->get();

            foreach ($pastOrders as $po) {
                $expenseId = DB::table('expenses')->insertGetId([
                    'amount' => $po->total_cost,
                    'expense_category_id' => $catId,
                    'description' => "Order Toko [{$po->transaction_number}] Wilayah {$po->technician_name}",
                    'expense_date' => $po->date ?? $po->created_at,
                    'user_id' => $po->user_id,
                    'created_at' => $po->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                DB::table('material_transactions')->where('id', $po->id)->update([
                    'expense_id' => $expenseId,
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore if tables are empty or setup in progress
        }
    }

    public function down(): void
    {
        Schema::table('material_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('material_transactions', 'expense_id')) {
                $table->dropForeign(['expense_id']);
                $table->dropColumn('expense_id');
            }
        });
    }
};
