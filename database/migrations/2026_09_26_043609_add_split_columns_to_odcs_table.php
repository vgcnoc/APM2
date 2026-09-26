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
            $table->string('start_point')->nullable();
            $table->string('end_point')->nullable();
            $table->string('cable_pull')->nullable();
            $table->boolean('is_split')->default(false);
            $table->foreignId('parent_odc_id')->nullable()->constrained('odcs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['parent_odc_id']);
            $table->dropColumn(['start_point', 'end_point', 'cable_pull', 'is_split', 'parent_odc_id']);
        });
    }
};
