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
        Schema::table('onts', function (Blueprint $table) {
            $table->foreignId('material_transaction_item_id')->nullable()->constrained('material_transaction_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            $table->dropForeign(['material_transaction_item_id']);
            $table->dropColumn('material_transaction_item_id');
        });
    }
};
