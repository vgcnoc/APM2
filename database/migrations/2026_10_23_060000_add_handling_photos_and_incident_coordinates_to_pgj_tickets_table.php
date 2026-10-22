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
        Schema::table('pgj_tickets', function (Blueprint $table) {
            $table->json('incident_coordinates')->nullable()->after('longitude');
            $table->json('handling_photos')->nullable()->after('photo_after');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pgj_tickets', function (Blueprint $table) {
            $table->dropColumn(['incident_coordinates', 'handling_photos']);
        });
    }
};
