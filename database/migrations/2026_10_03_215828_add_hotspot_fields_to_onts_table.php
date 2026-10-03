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
            $table->boolean('free_hotspot')->default(false)->after('pppoe_password');
            $table->string('hotspot_user')->nullable()->after('free_hotspot');
            $table->string('hotspot_password')->nullable()->after('hotspot_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            $table->dropColumn(['free_hotspot', 'hotspot_user', 'hotspot_password']);
        });
    }
};
