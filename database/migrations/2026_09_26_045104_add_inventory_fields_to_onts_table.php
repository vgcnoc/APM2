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
        // First make existing columns nullable
        DB::statement("ALTER TABLE onts MODIFY odp_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE onts MODIFY port_number INT NULL");
        DB::statement("ALTER TABLE onts MODIFY status VARCHAR(50) DEFAULT 'Belum Set/Baru Input'");

        Schema::table('onts', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('vlan_mode')->nullable();
            $table->string('access_mode')->nullable();
            $table->string('ip_login')->nullable();
            $table->string('login_user')->nullable();
            $table->string('login_password')->nullable();
            $table->string('pppoe_user')->nullable();
            $table->string('pppoe_password')->nullable();
            $table->json('input_officers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn([
                'area_id',
                'vlan_mode',
                'access_mode',
                'ip_login',
                'login_user',
                'login_password',
                'pppoe_user',
                'pppoe_password',
                'input_officers'
            ]);
        });
    }
};
