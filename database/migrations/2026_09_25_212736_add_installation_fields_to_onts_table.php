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
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('photo_odp')->nullable();
            $table->string('photo_installation')->nullable();
            $table->string('photo_ont')->nullable();
            $table->string('photo_customer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            $table->dropColumn([
                'start_time',
                'end_time',
                'photo_odp',
                'photo_installation',
                'photo_ont',
                'photo_customer'
            ]);
        });
    }
};
