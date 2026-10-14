<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('infrastructure_work_orders')) {
            Schema::table('infrastructure_work_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('infrastructure_work_orders', 'device_type')) {
                    $table->string('device_type')->default('Normal')->after('type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('infrastructure_work_orders')) {
            Schema::table('infrastructure_work_orders', function (Blueprint $table) {
                if (Schema::hasColumn('infrastructure_work_orders', 'device_type')) {
                    $table->dropColumn('device_type');
                }
            });
        }
    }
};
