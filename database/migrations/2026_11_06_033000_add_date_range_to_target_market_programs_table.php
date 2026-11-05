<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('target_market_programs')) {
            Schema::table('target_market_programs', function (Blueprint $table) {
                if (!Schema::hasColumn('target_market_programs', 'start_date')) {
                    $table->date('start_date')->nullable()->after('period_year');
                }
                if (!Schema::hasColumn('target_market_programs', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
            });

            // Backfill data program yang sudah ada agar memiliki rentang tanggal awal s/d akhir bulan
            try {
                $programs = DB::table('target_market_programs')->get();
                foreach ($programs as $p) {
                    if (empty($p->start_date) && !empty($p->period_month) && !empty($p->period_year)) {
                        $start = Carbon::create((int) $p->period_year, (int) $p->period_month, 1)->startOfMonth()->toDateString();
                        $end = Carbon::create((int) $p->period_year, (int) $p->period_month, 1)->endOfMonth()->toDateString();
                        DB::table('target_market_programs')->where('id', $p->id)->update([
                            'start_date' => $start,
                            'end_date' => $end,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan jika tabel masih kosong
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('target_market_programs')) {
            Schema::table('target_market_programs', function (Blueprint $table) {
                if (Schema::hasColumn('target_market_programs', 'end_date')) {
                    $table->dropColumn('end_date');
                }
                if (Schema::hasColumn('target_market_programs', 'start_date')) {
                    $table->dropColumn('start_date');
                }
            });
        }
    }
};

