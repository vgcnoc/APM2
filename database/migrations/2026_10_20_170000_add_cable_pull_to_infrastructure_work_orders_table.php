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
                if (!Schema::hasColumn('infrastructure_work_orders', 'cable_pull')) {
                    $table->string('cable_pull')->nullable()->default('Individu')->after('end_point');
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
                if (Schema::hasColumn('infrastructure_work_orders', 'cable_pull')) {
                    $table->dropColumn('cable_pull');
                }
            });
        }
    }
};
