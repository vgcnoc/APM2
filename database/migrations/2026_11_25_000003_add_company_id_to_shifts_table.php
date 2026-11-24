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
        if (Schema::hasTable('shifts')) {
            // 1. Tambah kolom company_id jika belum ada
            if (!Schema::hasColumn('shifts', 'company_id')) {
                Schema::table('shifts', function (Blueprint $table) {
                    $table->foreignId('company_id')->default(1)->after('id')->constrained('companies')->cascadeOnDelete();
                });
            }

            // 2. Pastikan semua shift lama milik Holding / Pusat (company_id = 1)
            DB::table('shifts')->whereNull('company_id')->update(['company_id' => 1]);

            // 3. Duplikasi template shift Pusat ke setiap Mitra yang sudah terdaftar
            try {
                $mitraCompanies = DB::table('companies')
                    ->where(function ($q) {
                        $q->where('id', '>', 1)->orWhere('is_holding', false);
                    })
                    ->pluck('id');

                $pusatShifts = DB::table('shifts')->where('company_id', 1)->get();

                foreach ($mitraCompanies as $mitraId) {
                    $hasShift = DB::table('shifts')->where('company_id', $mitraId)->exists();
                    if (!$hasShift && $pusatShifts->isNotEmpty()) {
                        foreach ($pusatShifts as $s) {
                            DB::table('shifts')->insert([
                                'company_id' => $mitraId,
                                'name' => $s->name,
                                'start_time' => $s->start_time,
                                'end_time' => $s->end_time,
                                'is_night_shift' => (bool) $s->is_night_shift,
                                'color' => $s->color,
                                'description' => $s->description,
                                'is_active' => (bool) $s->is_active,
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
        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'company_id')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};

