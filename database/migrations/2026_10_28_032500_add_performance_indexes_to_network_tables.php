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
        // 1. Index on odp_ports.status for fast filtering and lock prevention
        Schema::table('odp_ports', function (Blueprint $table) {
            $table->index('status', 'odp_ports_status_idx');
        });

        // 2. Composite index on surveys(odp_id, port_number)
        Schema::table('surveys', function (Blueprint $table) {
            $table->index(['odp_id', 'port_number'], 'surveys_odp_port_idx');
        });

        // 3. Composite index on onts(odp_id, port_number)
        Schema::table('onts', function (Blueprint $table) {
            $table->index(['odp_id', 'port_number'], 'onts_odp_port_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odp_ports', function (Blueprint $table) {
            $table->dropIndex('odp_ports_status_idx');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropIndex('surveys_odp_port_idx');
        });

        Schema::table('onts', function (Blueprint $table) {
            $table->dropIndex('onts_odp_port_idx');
        });
    }
};
