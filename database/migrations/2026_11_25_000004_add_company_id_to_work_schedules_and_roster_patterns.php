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
        // 1. Tambah company_id ke tabel work_schedules
        if (Schema::hasTable('work_schedules') && !Schema::hasColumn('work_schedules', 'company_id')) {
            Schema::table('work_schedules', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });

            // Sinkronkan company_id jadwal kerja sesuai company_id karyawan pemilik jadwal
            try {
                DB::statement("UPDATE work_schedules ws 
                    JOIN employees e ON e.id = ws.employee_id 
                    SET ws.company_id = COALESCE(e.company_id, 1) 
                    WHERE ws.company_id IS NULL OR ws.company_id = 1");
            } catch (\Throwable $e) {
                DB::table('work_schedules')->whereNull('company_id')->update(['company_id' => 1]);
            }
        }

        // 2. Tambah company_id ke tabel roster_patterns
        if (Schema::hasTable('roster_patterns') && !Schema::hasColumn('roster_patterns', 'company_id')) {
            Schema::table('roster_patterns', function (Blueprint $table) {
                $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
            });

            // Seluruh pola roster awal adalah milik Holding / Pusat (company_id = 1)
            DB::table('roster_patterns')->whereNull('company_id')->update(['company_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('work_schedules') && Schema::hasColumn('work_schedules', 'company_id')) {
            Schema::table('work_schedules', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('roster_patterns') && Schema::hasColumn('roster_patterns', 'company_id')) {
            Schema::table('roster_patterns', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
