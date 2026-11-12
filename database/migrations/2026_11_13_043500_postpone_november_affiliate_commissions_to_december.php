<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tunda komisi bulanan & upline affiliate karyawan (5.000, 2.000, 1.000)
     * dari bulan November ke bulan Desember agar ditotalkan bersama di Desember.
     */
    public function up(): void
    {
        DB::table('incentives')
            ->where('status', 'pending')
            ->where(function($q) {
                $q->where('reference_id', 'like', 'aff_monthly_%')
                  ->orWhere('reference_id', 'like', 'aff_upline_1_%')
                  ->orWhere('reference_id', 'like', 'aff_upline_2_%')
                  ->orWhere(function($sub) {
                      $sub->whereIn('amount', [5000, 2000, 1000])
                          ->where('description', 'like', '%Komisi%');
                  });
            })
            ->update([
                'incentive_date' => '2026-12-01'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap dipertahankan
    }
};
