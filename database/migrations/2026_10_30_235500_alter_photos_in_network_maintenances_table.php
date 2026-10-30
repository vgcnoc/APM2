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
        Schema::table('network_maintenances', function (Blueprint $table) {
            $table->text('photo_after')->nullable()->change();
            $table->text('photo_before')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('network_maintenances', function (Blueprint $table) {
            $table->string('photo_after', 255)->nullable()->change();
            $table->string('photo_before', 255)->nullable()->change();
        });
    }
};
