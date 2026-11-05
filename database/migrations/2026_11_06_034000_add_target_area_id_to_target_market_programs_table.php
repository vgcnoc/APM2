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
        if (Schema::hasTable('target_market_programs')) {
            Schema::table('target_market_programs', function (Blueprint $table) {
                if (!Schema::hasColumn('target_market_programs', 'target_area_id')) {
                    $table->foreignId('target_area_id')->nullable()->after('target_role')->constrained('areas')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('target_market_programs')) {
            Schema::table('target_market_programs', function (Blueprint $table) {
                if (Schema::hasColumn('target_market_programs', 'target_area_id')) {
                    $table->dropForeign(['target_area_id']);
                    $table->dropColumn('target_area_id');
                }
            });
        }
    }
};

