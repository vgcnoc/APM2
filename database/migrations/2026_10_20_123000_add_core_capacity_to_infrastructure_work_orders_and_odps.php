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
        if (Schema::hasTable('infrastructure_work_orders')) {
            Schema::table('infrastructure_work_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('infrastructure_work_orders', 'core_capacity')) {
                    $table->unsignedInteger('core_capacity')->nullable()->after('capacity');
                }
            });
        }

        if (Schema::hasTable('odps')) {
            Schema::table('odps', function (Blueprint $table) {
                if (!Schema::hasColumn('odps', 'core_capacity')) {
                    $table->unsignedInteger('core_capacity')->nullable()->after('total_ports');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('infrastructure_work_orders')) {
            Schema::table('infrastructure_work_orders', function (Blueprint $table) {
                if (Schema::hasColumn('infrastructure_work_orders', 'core_capacity')) {
                    $table->dropColumn('core_capacity');
                }
            });
        }

        if (Schema::hasTable('odps')) {
            Schema::table('odps', function (Blueprint $table) {
                if (Schema::hasColumn('odps', 'core_capacity')) {
                    $table->dropColumn('core_capacity');
                }
            });
        }
    }
};
