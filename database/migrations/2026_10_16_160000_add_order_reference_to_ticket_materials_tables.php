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
        // 1. pgk_materials
        if (Schema::hasTable('pgk_materials')) {
            Schema::table('pgk_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('pgk_materials', 'material_transaction_item_id')) {
                    $table->unsignedBigInteger('material_transaction_item_id')->nullable()->after('material_id');
                }
                if (!Schema::hasColumn('pgk_materials', 'trx_number')) {
                    $table->string('trx_number', 100)->nullable()->after('material_transaction_item_id');
                }
            });
        }

        // 2. pgs_materials
        if (Schema::hasTable('pgs_materials')) {
            Schema::table('pgs_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('pgs_materials', 'material_transaction_item_id')) {
                    $table->unsignedBigInteger('material_transaction_item_id')->nullable()->after('material_id');
                }
                if (!Schema::hasColumn('pgs_materials', 'trx_number')) {
                    $table->string('trx_number', 100)->nullable()->after('material_transaction_item_id');
                }
            });
        }

        // 3. pgj_materials
        if (Schema::hasTable('pgj_materials')) {
            Schema::table('pgj_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('pgj_materials', 'material_transaction_item_id')) {
                    $table->unsignedBigInteger('material_transaction_item_id')->nullable()->after('material_id');
                }
                if (!Schema::hasColumn('pgj_materials', 'trx_number')) {
                    $table->string('trx_number', 100)->nullable()->after('material_transaction_item_id');
                }
                if (!Schema::hasColumn('pgj_materials', 'notes')) {
                    $table->text('notes')->nullable()->after('unit');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pgk_materials')) {
            Schema::table('pgk_materials', function (Blueprint $table) {
                if (Schema::hasColumn('pgk_materials', 'trx_number')) {
                    $table->dropColumn('trx_number');
                }
                if (Schema::hasColumn('pgk_materials', 'material_transaction_item_id')) {
                    $table->dropColumn('material_transaction_item_id');
                }
            });
        }

        if (Schema::hasTable('pgs_materials')) {
            Schema::table('pgs_materials', function (Blueprint $table) {
                if (Schema::hasColumn('pgs_materials', 'trx_number')) {
                    $table->dropColumn('trx_number');
                }
                if (Schema::hasColumn('pgs_materials', 'material_transaction_item_id')) {
                    $table->dropColumn('material_transaction_item_id');
                }
            });
        }

        if (Schema::hasTable('pgj_materials')) {
            Schema::table('pgj_materials', function (Blueprint $table) {
                if (Schema::hasColumn('pgj_materials', 'notes')) {
                    $table->dropColumn('notes');
                }
                if (Schema::hasColumn('pgj_materials', 'trx_number')) {
                    $table->dropColumn('trx_number');
                }
                if (Schema::hasColumn('pgj_materials', 'material_transaction_item_id')) {
                    $table->dropColumn('material_transaction_item_id');
                }
            });
        }
    }
};
