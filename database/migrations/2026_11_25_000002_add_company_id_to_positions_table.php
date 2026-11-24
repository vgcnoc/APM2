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
        if (Schema::hasTable('positions')) {
            // 1. Drop index unique lama pada name jika ada
            try {
                Schema::table('positions', function (Blueprint $table) {
                    $table->dropUnique('positions_name_unique');
                });
            } catch (\Throwable $e) {}

            // 2. Tambah kolom company_id jika belum ada
            if (!Schema::hasColumn('positions', 'company_id')) {
                Schema::table('positions', function (Blueprint $table) {
                    $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
                });
            }

            // 3. Pastikan semua posisi lama milik Holding / Pusat (company_id = 1)
            DB::table('positions')->whereNull('company_id')->update(['company_id' => 1]);

            // 4. Tambah composite unique index [company_id, name]
            try {
                Schema::table('positions', function (Blueprint $table) {
                    $table->unique(['company_id', 'name'], 'positions_company_name_unique');
                });
            } catch (\Throwable $e) {}

            // 5. Duplikasi template posisi Pusat ke setiap Mitra yang sudah terdaftar
            try {
                $mitraCompanies = DB::table('companies')
                    ->where(function ($q) {
                        $q->where('id', '>', 1)->orWhere('is_holding', false);
                    })
                    ->pluck('id');

                $pusatPositions = DB::table('positions')->where('company_id', 1)->get();

                foreach ($mitraCompanies as $mitraId) {
                    $hasPos = DB::table('positions')->where('company_id', $mitraId)->exists();
                    if (!$hasPos && $pusatPositions->isNotEmpty()) {
                        foreach ($pusatPositions as $pos) {
                            DB::table('positions')->insert([
                                'company_id' => $mitraId,
                                'name' => $pos->name,
                                'department' => $pos->department,
                                'description' => $pos->description,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('positions')) {
            try {
                Schema::table('positions', function (Blueprint $table) {
                    $table->dropUnique('positions_company_name_unique');
                });
            } catch (\Throwable $e) {}

            if (Schema::hasColumn('positions', 'company_id')) {
                Schema::table('positions', function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }

            try {
                Schema::table('positions', function (Blueprint $table) {
                    $table->unique('name', 'positions_name_unique');
                });
            } catch (\Throwable $e) {}
        }
    }
};
