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
        // 1. Tambah company_id ke tabel special_teams
        if (Schema::hasTable('special_teams') && !Schema::hasColumn('special_teams', 'company_id')) {
            Schema::table('special_teams', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });

            DB::table('special_teams')->whereNull('company_id')->update(['company_id' => 1]);
        }

        // 2. Tambah company_id ke tabel special_attendances
        if (Schema::hasTable('special_attendances') && !Schema::hasColumn('special_attendances', 'company_id')) {
            Schema::table('special_attendances', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });

            try {
                DB::statement("UPDATE special_attendances sa 
                    JOIN employees e ON e.id = sa.employee_id 
                    SET sa.company_id = COALESCE(e.company_id, 1) 
                    WHERE sa.company_id IS NULL OR sa.company_id = 1");
            } catch (\Throwable $e) {
                DB::table('special_attendances')->whereNull('company_id')->update(['company_id' => 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('special_attendances') && Schema::hasColumn('special_attendances', 'company_id')) {
            Schema::table('special_attendances', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('special_teams') && Schema::hasColumn('special_teams', 'company_id')) {
            Schema::table('special_teams', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
