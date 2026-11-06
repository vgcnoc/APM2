<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\Customer;
use App\Models\Incentive;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hapus komisi booking untuk pelanggan yang tidak aktif (terminated / cancelled / failed survey)
        $invalidBookings = AffiliateCommission::where('type', 'booking')
            ->whereHas('customer', function ($q) {
                $q->where('status', '!=', 'active');
            })
            ->get();

        foreach ($invalidBookings as $comm) {
            // Jika ada insentif di tabel incentives, hapus
            if ($comm->customer_id) {
                Incentive::where('reference_id', 'booking_cust_' . $comm->customer_id)->delete();
                $cust = Customer::find($comm->customer_id);
                if ($cust) {
                    Incentive::where('description', 'like', "%Fee Booking Pelanggan: {$cust->name}%")->delete();
                }
            }

            // Jika ada saldo aktif mitra luar yang bertambah, kurangkan
            if ($comm->status === 'approved' && $comm->affiliate_id) {
                $profile = AffiliateProfile::where('user_id', $comm->affiliate_id)->first();
                if ($profile) {
                    $profile->decrement('balance', min((float) $profile->balance, (float) $comm->amount));
                }
            }

            // Hapus komisi invalid
            $comm->delete();
        }

        // 2. Sinkronkan ulang total_earned di semua profil afiliasi
        $profiles = AffiliateProfile::all();
        foreach ($profiles as $p) {
            $commSum = (float) AffiliateCommission::where('affiliate_id', $p->user_id)
                ->whereIn('status', ['approved', 'paid', 'payroll'])
                ->sum('amount');

            $p->total_earned = $commSum;
            if ($p->balance < 0) {
                $p->balance = 0;
            }
            $p->saveQuietly();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
