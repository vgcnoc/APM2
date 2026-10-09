<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_transaction_items', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('stock_after')->comment('Kondisi barang: Layak Pakai, Rusak, Hilang, dll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_transaction_items', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
