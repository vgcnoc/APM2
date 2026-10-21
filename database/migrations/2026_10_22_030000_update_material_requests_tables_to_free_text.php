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
        // Update pgs_material_requests
        if (Schema::hasTable('pgs_material_requests')) {
            Schema::table('pgs_material_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('pgs_material_requests', 'item_name')) {
                    $table->string('item_name')->nullable()->after('pgs_ticket_id');
                }
            });

            // Make material_id nullable using raw DB statement for reliable MySQL execution
            try {
                DB::statement("ALTER TABLE pgs_material_requests MODIFY material_id BIGINT UNSIGNED NULL");
            } catch (\Throwable $e) {
                // Fallback using Schema change if supported
                Schema::table('pgs_material_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('material_id')->nullable()->change();
                });
            }
        }

        // Update pgk_material_requests
        if (Schema::hasTable('pgk_material_requests')) {
            Schema::table('pgk_material_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('pgk_material_requests', 'item_name')) {
                    $table->string('item_name')->nullable()->after('pgk_ticket_id');
                }
            });

            try {
                DB::statement("ALTER TABLE pgk_material_requests MODIFY material_id BIGINT UNSIGNED NULL");
            } catch (\Throwable $e) {
                Schema::table('pgk_material_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('material_id')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pgs_material_requests')) {
            Schema::table('pgs_material_requests', function (Blueprint $table) {
                if (Schema::hasColumn('pgs_material_requests', 'item_name')) {
                    $table->dropColumn('item_name');
                }
            });
        }

        if (Schema::hasTable('pgk_material_requests')) {
            Schema::table('pgk_material_requests', function (Blueprint $table) {
                if (Schema::hasColumn('pgk_material_requests', 'item_name')) {
                    $table->dropColumn('item_name');
                }
            });
        }
    }
};
