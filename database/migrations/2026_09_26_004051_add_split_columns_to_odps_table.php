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
        Schema::table('odps', function (Blueprint $table) {
            $table->string('start_point')->nullable();
            $table->string('end_point')->nullable();
            $table->string('cable_pull')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_split')->default(false);
            $table->foreignId('parent_odp_id')->nullable()->constrained('odps')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropForeign(['parent_odp_id']);
            $table->dropColumn(['start_point', 'end_point', 'cable_pull', 'photo', 'is_split', 'parent_odp_id']);
        });
    }
};
