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
        // 1. Ubah default psb_payment_status di schema menjadi 'unpaid' atau 'free'
        // Jika pelanggan memiliki biaya pasang 0 atau null, status pembayaran PSB wajib 'free' (Bebas Biaya)
        if (Schema::hasColumn('customers', 'psb_payment_status')) {
            DB::table('customers')
                ->where(function ($q) {
                    $q->whereNull('installation_fee')
                      ->orWhere('installation_fee', '<=', 0);
                })
                ->update([
                    'psb_payment_status' => 'free',
                    'installation_fee' => 0,
                    'psb_paid_at' => null,
                    'psb_payment_method' => null,
                    'psb_received_by' => null,
                    'psb_income_id' => null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
