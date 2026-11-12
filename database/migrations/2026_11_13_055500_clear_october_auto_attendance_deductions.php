<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Deduction;

return new class extends Migration
{
    /**
     * Bersihkan potongan denda absensi otomatis untuk periode Oktober 2026
     * karena sistem presensi absensi online baru mulai aktif berjalan pada November 2026.
     */
    public function up(): void
    {
        Deduction::where('status', 'pending')
            ->where(function($q) {
                $q->where('reference_id', 'like', 'ATT_%')
                  ->orWhere('reference_id', 'like', 'SPECIAL_DED_%');
            })
            ->whereMonth('deduction_date', 10)
            ->whereYear('deduction_date', 2026)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
