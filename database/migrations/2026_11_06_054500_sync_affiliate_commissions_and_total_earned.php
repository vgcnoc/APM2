<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\Customer;
use App\Models\Incentive;
use App\Models\User;
use App\Services\AffiliateService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sinkronisasi Fee Booking dari tabel incentives ke affiliate_commissions
        $bookingIncs = Incentive::where('type', 'auto')
            ->where(function ($q) {
                $q->where('reference_id', 'like', 'booking_cust_%')
                  ->orWhere('description', 'like', 'Fee Booking Pelanggan:%');
            })
            ->get();

        foreach ($bookingIncs as $inc) {
            $custId = null;
            if (preg_match('/booking_cust_(\d+)/', $inc->reference_id, $m)) {
                $custId = (int) $m[1];
            } elseif (preg_match('/\(CUS-(\d+)\)/', $inc->description, $m)) {
                $custCode = 'CUS-' . $m[1];
                $custId = Customer::where('customer_code', $custCode)->value('id');
            }

            if (!$custId) {
                continue;
            }

            $customer = Customer::find($custId);
            if (!$customer) {
                continue;
            }

            // Pastikan agen memiliki AffiliateProfile
            $user = User::find($inc->user_id);
            if ($user) {
                AffiliateService::ensureAffiliateProfile($user);
            }

            $exists = AffiliateCommission::where('affiliate_id', $inc->user_id)
                ->where('customer_id', $customer->id)
                ->where('type', 'booking')
                ->exists();

            if (!$exists) {
                $codeLabel = $customer->customer_code ? " ({$customer->customer_code})" : '';
                AffiliateCommission::create([
                    'affiliate_id' => $inc->user_id,
                    'customer_id' => $customer->id,
                    'source_user_id' => $inc->user_id,
                    'type' => 'booking',
                    'tier_level' => 0,
                    'amount' => (float) $inc->amount,
                    'description' => "Fee Booking Pelanggan: {$customer->name}{$codeLabel} [Masuk Insentif Payroll]",
                    'status' => 'payroll',
                    'paid_at' => null,
                    'created_at' => $inc->created_at ?: now(),
                    'updated_at' => $inc->updated_at ?: now(),
                ]);
            }
        }

        // 2. Sinkronkan total_earned di tabel affiliate_profiles dengan total komisi yang diperoleh
        $profiles = AffiliateProfile::all();
        foreach ($profiles as $p) {
            $commSum = (float) AffiliateCommission::where('affiliate_id', $p->user_id)
                ->whereIn('status', ['approved', 'paid', 'payroll'])
                ->sum('amount');

            // Untuk mitra luar yang sudah ada saldo, pastikan total_earned minimal sama dengan komisi atau saldo
            $earned = max($commSum, (float) $p->balance);

            $p->update([
                'total_earned' => (int) $earned,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data synchronization
    }
};

