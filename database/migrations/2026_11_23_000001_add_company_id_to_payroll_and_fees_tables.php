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
        // 1. master_fees
        if (Schema::hasTable('master_fees') && !Schema::hasColumn('master_fees', 'company_id')) {
            Schema::table('master_fees', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });
        }

        // 2. payroll_categories
        if (Schema::hasTable('payroll_categories') && !Schema::hasColumn('payroll_categories', 'company_id')) {
            Schema::table('payroll_categories', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });
        }

        // 3. incentives
        if (Schema::hasTable('incentives') && !Schema::hasColumn('incentives', 'company_id')) {
            Schema::table('incentives', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            });

            // Backfill company_id dari tabel users
            try {
                DB::statement("
                    UPDATE incentives i
                    INNER JOIN users u ON i.user_id = u.id
                    SET i.company_id = COALESCE(u.company_id, 1)
                    WHERE i.company_id IS NULL
                ");
            } catch (\Throwable $e) {}

            // Ubah default menjadi 1 jika masih ada null
            DB::table('incentives')->whereNull('company_id')->update(['company_id' => 1]);
        }

        // 4. deductions
        if (Schema::hasTable('deductions') && !Schema::hasColumn('deductions', 'company_id')) {
            Schema::table('deductions', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            });

            // Backfill company_id dari tabel users
            try {
                DB::statement("
                    UPDATE deductions d
                    INNER JOIN users u ON d.user_id = u.id
                    SET d.company_id = COALESCE(u.company_id, 1)
                    WHERE d.company_id IS NULL
                ");
            } catch (\Throwable $e) {}

            // Ubah default menjadi 1 jika masih ada null
            DB::table('deductions')->whereNull('company_id')->update(['company_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_fees') && Schema::hasColumn('master_fees', 'company_id')) {
            Schema::table('master_fees', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('payroll_categories') && Schema::hasColumn('payroll_categories', 'company_id')) {
            Schema::table('payroll_categories', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('incentives') && Schema::hasColumn('incentives', 'company_id')) {
            Schema::table('incentives', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('deductions') && Schema::hasColumn('deductions', 'company_id')) {
            Schema::table('deductions', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};

