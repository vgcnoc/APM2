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
        if (Schema::hasTable('infrastructure_work_order_materials')) {
            Schema::table('infrastructure_work_order_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('infrastructure_work_order_materials', 'material_transaction_item_id')) {
                    $table->unsignedBigInteger('material_transaction_item_id')->nullable()->after('material_id');
                }
                if (!Schema::hasColumn('infrastructure_work_order_materials', 'trx_number')) {
                    $table->string('trx_number', 100)->nullable()->after('material_transaction_item_id');
                }
                if (!Schema::hasColumn('infrastructure_work_order_materials', 'notes')) {
                    $table->string('notes', 255)->nullable()->after('unit');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('infrastructure_work_order_materials')) {
            Schema::table('infrastructure_work_order_materials', function (Blueprint $table) {
                if (Schema::hasColumn('infrastructure_work_order_materials', 'material_transaction_item_id')) {
                    $table->dropColumn('material_transaction_item_id');
                }
                if (Schema::hasColumn('infrastructure_work_order_materials', 'trx_number')) {
                    $table->dropColumn('trx_number');
                }
                if (Schema::hasColumn('infrastructure_work_order_materials', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }
    }
};
