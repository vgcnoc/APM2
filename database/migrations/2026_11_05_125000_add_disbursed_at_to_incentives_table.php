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
        if (!Schema::hasColumn('incentives', 'disbursed_at')) {
            Schema::table('incentives', function (Blueprint $table) {
                $table->date('disbursed_at')->nullable()->after('status');
            });
        }

        // Backfill existing paid incentives
        try {
            DB::table('incentives')
                ->where('status', 'paid')
                ->whereNull('disbursed_at')
                ->update([
                    'disbursed_at' => DB::raw('COALESCE(DATE(updated_at), incentive_date)')
                ]);
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('incentives', 'disbursed_at')) {
            Schema::table('incentives', function (Blueprint $table) {
                $table->dropColumn('disbursed_at');
            });
        }
    }
};

