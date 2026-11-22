<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Deduction;
use App\Models\AffiliateCommission;
use App\Services\AffiliateService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbaiki data pinalti survey untuk pelanggan Maulana farijal mustopaa (ID 483)
        $cust483 = Customer::find(483);
        if ($cust483) {
            // Update deduction survey dari 5000 ke 15000
            $dedSurvey = Deduction::where('reference_id', 'clawback_survey_cust_483')->first();
            if ($dedSurvey) {
                $dedSurvey->update([
                    'amount' => 15000,
                    'description' => "Pinalti Stop Dini < 4 Bln: Pemotongan Fee Survey Maulana farijal mustopaa (CUS-001483) - Masa aktif: 1 bulan 12 hari",
                ]);
            }

            // Update commission clawback survey dari -5000 ke -15000
            $commSurvey = AffiliateCommission::where('customer_id', 483)->where('type', 'clawback_survey')->first();
            if ($commSurvey) {
                $commSurvey->update([
                    'amount' => -15000,
                    'description' => "Pinalti Stop Dini < 4 Bln: Pemotongan Fee Survey [Dipotong dari Slip Gaji Payroll] Maulana farijal mustopaa (CUS-001483) - Masa aktif: 1 bulan 12 hari",
                ]);
            }

            // Tambahkan potongan dan komisi clawback_monthly (Komisi Pasif Bulanan Rp 5.000) jika belum ada
            $alreadyDeductedMonthly = Deduction::where('reference_id', 'clawback_monthly_cust_483')->exists();
            if (!$alreadyDeductedMonthly && $cust483->sales_id) {
                Deduction::create([
                    'user_id' => $cust483->sales_id,
                    'amount' => 5000,
                    'type' => 'auto',
                    'description' => "Pinalti Stop Dini < 4 Bln: Pemotongan Komisi Pasif Bulanan Maulana farijal mustopaa (CUS-001483) - Masa aktif: 1 bulan 12 hari",
                    'reference_id' => 'clawback_monthly_cust_483',
                    'deduction_date' => now(),
                    'status' => 'pending',
                ]);
            }

            $alreadyCommMonthly = AffiliateCommission::where('customer_id', 483)->where('type', 'clawback_monthly')->exists();
            if (!$alreadyCommMonthly && $cust483->sales_id) {
                AffiliateCommission::create([
                    'affiliate_id' => $cust483->sales_id,
                    'customer_id' => 483,
                    'source_user_id' => $cust483->sales_id,
                    'type' => 'clawback_monthly',
                    'tier_level' => 0,
                    'amount' => -5000,
                    'description' => "Pinalti Stop Dini < 4 Bln: Pemotongan Komisi Pasif Bulanan [Dipotong dari Slip Gaji Payroll] Maulana farijal mustopaa (CUS-001483) - Masa aktif: 1 bulan 12 hari",
                    'status' => 'payroll',
                    'paid_at' => null,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert is non-destructive
    }
};

