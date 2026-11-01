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
        Schema::table('material_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('material_transactions', 'order_user_id')) {
                $table->foreignId('order_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('material_transactions', 'giver_user_id')) {
                $table->foreignId('giver_user_id')->nullable()->after('order_user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('material_transactions', 'order_account_name')) {
                $table->string('order_account_name')->nullable()->after('giver_user_id');
            }
            if (!Schema::hasColumn('material_transactions', 'giver_account_name')) {
                $table->string('giver_account_name')->nullable()->after('order_account_name');
            }
        });

        // Backfill data untuk transaksi Pembelian Toko / Supplier (Order Toko) yang sudah ada
        // order_user_id diset dari user_id pembuat transaksi
        // giver_user_id default ke Super Admin (ID 1) atau user pembuat
        $transactions = DB::table('material_transactions')
            ->where('purpose', 'Pembelian Toko / Supplier')
            ->get();

        foreach ($transactions as $t) {
            $user = $t->user_id ? DB::table('users')->where('id', $t->user_id)->first() : null;
            $orderName = $user ? $user->name : ($t->technician_name ?: 'Petugas Toko');

            DB::table('material_transactions')
                ->where('id', $t->id)
                ->update([
                    'order_user_id' => $t->user_id,
                    'order_account_name' => $orderName,
                    'giver_user_id' => 1, // Super Admin / Gudang Pusat
                    'giver_account_name' => 'Super Admin (Gudang Pusat)',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('material_transactions', 'order_user_id')) {
                $table->dropForeign(['order_user_id']);
                $table->dropColumn('order_user_id');
            }
            if (Schema::hasColumn('material_transactions', 'giver_user_id')) {
                $table->dropForeign(['giver_user_id']);
                $table->dropColumn('giver_user_id');
            }
            if (Schema::hasColumn('material_transactions', 'order_account_name')) {
                $table->dropColumn('order_account_name');
            }
            if (Schema::hasColumn('material_transactions', 'giver_account_name')) {
                $table->dropColumn('giver_account_name');
            }
        });
    }
};
