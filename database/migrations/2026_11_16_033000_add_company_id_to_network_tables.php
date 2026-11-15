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
        $networkTables = [
            'olts',
            'odcs',
            'odps',
            'onts',
            'routers',
            'network_maintenances',
            'ftth_designs',
            'ftth_cable_routes',
        ];

        foreach ($networkTables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('company_id')->default(1)->after('id')->index();
                });

                // Pastikan seluruh data jaringan lama terikat 100% ke Holding Pusat (ID 1)
                DB::table($table)->whereNull('company_id')->orWhere('company_id', 0)->update(['company_id' => 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $networkTables = [
            'olts',
            'odcs',
            'odps',
            'onts',
            'routers',
            'network_maintenances',
            'ftth_designs',
            'ftth_cable_routes',
        ];

        foreach ($networkTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('company_id');
                });
            }
        }
    }
};

