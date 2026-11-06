<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\Customer;
use App\Models\Incentive;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ambil seluruh komisi fee booking (type = 'booking') yang berasal dari pelanggan migrasi (is_migrated = 1)
        $migratedBookings = AffiliateCommission::where('type', 'booking')
            ->whereHas('customer', function ($q) {
                $q->where('is_migrated', true);
            })
            ->get();

        foreach ($migratedBookings as $comm) {
            // Jika ada saldo aktif mitra luar yang bertambah karena komisi booking migrasi ini, kurangkan kembali
            if ($comm->status === 'approved' && $comm->affiliate_id) {
                $profile = AffiliateProfile::where('user_id', $comm->affiliate_id)->first();
                if ($profile) {
                    $currBalance = (float) $profile->balance;
                    $deduct = min($currBalance, (float) $comm->amount);
                    if ($deduct > 0) {
                        $profile->decrement('balance', $deduct);
                    }
                }
            }

            // Hapus insentif slip gaji terkait jika ada
            if ($comm->customer_id) {
                Incentive::where('reference_id', 'booking_cust_' . $comm->customer_id)->delete();
                $cust = Customer::find($comm->customer_id);
                if ($cust) {
                    Incentive::where('description', 'like', "%Fee Booking Pelanggan: {$cust->name}%")->delete();
                }
            }

            // Hapus record komisi booking
            $comm->delete();
        }

        // Hapus juga insentif booking untuk pelanggan migrasi yang mungkin ada di tabel incentives
        $migratedCustIds = Customer::where('is_migrated', true)->pluck('id')->toArray();
        if (!empty($migratedCustIds)) {
            Incentive::where(function ($q) use ($migratedCustIds) {
                foreach ($migratedCustIds as $id) {
                    $q->orWhere('reference_id', 'booking_cust_' . $id);
                }
            })->delete();
        }

        // 2. Sinkronisasi ulang total_earned di seluruh profil afiliasi agar presisi dengan komisi yang tersisa (5000, 2000, 1000 & booking non-migrasi)
        $profiles = AffiliateProfile::all();
        foreach ($profiles as $p) {
            $remainingSum = (float) AffiliateCommission::where('affiliate_id', $p->user_id)
                ->whereIn('status', ['approved', 'paid', 'payroll'])
                ->sum('amount');

            $p->total_earned = $remainingSum;
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
