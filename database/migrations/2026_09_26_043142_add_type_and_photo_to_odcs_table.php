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
        Schema::table('odcs', function (Blueprint $table) {
            $table->string('type')->default('Normal')->after('name');
            $table->string('photo')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropColumn(['type', 'photo']);
        });
    }
};
