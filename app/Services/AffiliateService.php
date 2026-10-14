<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AffiliateService
{
    /**
     * Ambil pengaturan komisi sales & afiliasi bertingkat.
     */
    public static function getSettings(): array
    {
        return [
            'affiliate_commission_booking' => (float) Setting::get('affiliate_commission_booking', 35000),
            'affiliate_commission_monthly' => (float) Setting::get('affiliate_commission_monthly', 5000),
            'affiliate_commission_upline_1' => (float) Setting::get('affiliate_commission_upline_1', 2000),
            'affiliate_commission_upline_2' => (float) Setting::get('affiliate_commission_upline_2', 1000),
            'affiliate_min_withdraw' => (float) Setting::get('affiliate_min_withdraw', 50000),
            'affiliate_auto_approve' => Setting::get('affiliate_auto_approve', '1') === '1',
            'affiliate_contract_min_months' => (int) Setting::get('affiliate_contract_min_months', 4),
        ];
    }

    /**
     * Simpan pengaturan komisi sales & afiliasi.
     */
    public static function saveSettings(array $data): void
    {
        if (isset($data['affiliate_commission_booking'])) {
            Setting::set('affiliate_commission_booking', (string) $data['affiliate_commission_booking']);
        }
        if (isset($data['affiliate_commission_monthly'])) {
            Setting::set('affiliate_commission_monthly', (string) $data['affiliate_commission_monthly']);
        }
        if (isset($data['affiliate_commission_upline_1'])) {
            Setting::set('affiliate_commission_upline_1', (string) $data['affiliate_commission_upline_1']);
        }
        if (isset($data['affiliate_commission_upline_2'])) {
            Setting::set('affiliate_commission_upline_2', (string) $data['affiliate_commission_upline_2']);
        }
        if (isset($data['affiliate_min_withdraw'])) {
            Setting::set('affiliate_min_withdraw', (string) $data['affiliate_min_withdraw']);
        }
        if (isset($data['affiliate_auto_approve'])) {
            Setting::set('affiliate_auto_approve', !empty($data['affiliate_auto_approve']) ? '1' : '0');
        }
        if (isset($data['affiliate_contract_min_months'])) {
            Setting::set('affiliate_contract_min_months', (string) $data['affiliate_contract_min_months']);
        }
    }

    /**
     * Generate Kode Referral Unik.
     */
    public static function generateReferralCode(?string $name = null): string
    {
        $prefix = 'AFF';
        if ($name) {
            $cleaned = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
            if (strlen($cleaned) >= 3) {
                $prefix = substr($cleaned, 0, 4);
            }
        }

        do {
            $code = $prefix . '-' . strtoupper(Str::random(5));
        } while (AffiliateProfile::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Buat atau pastikan profil afiliasi untuk seorang user.
     */
    public static function ensureAffiliateProfile(User $user, ?int $uplineId = null, array $bankDetails = []): AffiliateProfile
    {
        $profile = AffiliateProfile::where('user_id', $user->id)->first();
        if ($profile) {
            if ($uplineId && !$profile->upline_id && $profile->user_id !== $uplineId) {
                $profile->update(['upline_id' => $uplineId]);
            }
            return $profile;
        }

        return AffiliateProfile::create([
            'user_id' => $user->id,
            'referral_code' => self::generateReferralCode($user->name),
            'upline_id' => ($uplineId && $uplineId !== $user->id) ? $uplineId : null,
            'whatsapp' => $bankDetails['whatsapp'] ?? $user->phone,
            'address' => $bankDetails['address'] ?? null,
            'bank_name' => $bankDetails['bank_name'] ?? null,
            'bank_account_number' => $bankDetails['bank_account_number'] ?? null,
            'bank_account_holder' => $bankDetails['bank_account_holder'] ?? $user->name,
            'balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Catat Komisi Booking (Sekali Bayar) saat pelanggan baru booking/registrasi/aktif.
     */
    public static function recordBookingCommission(Customer $customer): ?AffiliateCommission
    {
        if (!$customer->sales_id) {
            return null;
        }

        $affiliate = AffiliateProfile::where('user_id', $customer->sales_id)
            ->where('status', 'active')
            ->first();

        if (!$affiliate) {
            return null;
        }

        // Cek apakah komisi booking sudah pernah dicatat untuk pelanggan ini
        $alreadyRecorded = AffiliateCommission::where('affiliate_id', $customer->sales_id)
            ->where('customer_id', $customer->id)
            ->where('type', 'booking')
            ->exists();

        if ($alreadyRecorded) {
            return null;
        }

        $settings = self::getSettings();
        $amount = $settings['affiliate_commission_booking'];

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($affiliate, $customer, $amount) {
            $commission = AffiliateCommission::create([
                'affiliate_id' => $customer->sales_id,
                'customer_id' => $customer->id,
                'source_user_id' => $customer->sales_id,
                'type' => 'booking',
                'tier_level' => 0,
                'amount' => $amount,
                'description' => "Komisi Booking Pelanggan Baru: {$customer->name} ({$customer->customer_code})",
                'status' => 'approved',
                'paid_at' => now(),
            ]);

            $affiliate->increment('balance', $amount);
            $affiliate->increment('total_earned', $amount);

            return $commission;
        });
    }

    /**
     * Otomatis Potong (Clawback) Fee Booking jika pelanggan berhenti di bawah batas minimal kontrak (default: 4 bulan).
     */
    public static function handleEarlyTerminationPenalty(Customer $customer, ?string $cancelDate = null): ?array
    {
        // 1. Pelanggan wajib memiliki sales_id (mitra afiliasi / sales)
        if (!$customer->sales_id) {
            return null;
        }

        // 2. Hitung masa aktif pelanggan
        $startDate = $customer->activation_date 
            ? \Carbon\Carbon::parse($customer->activation_date) 
            : ($customer->registration_date ? \Carbon\Carbon::parse($customer->registration_date) : $customer->created_at);

        $endDate = $cancelDate ? \Carbon\Carbon::parse($cancelDate) : now();

        $diffInDays = (int) $startDate->diffInDays($endDate);
        $diffInMonths = (int) $startDate->diffInMonths($endDate);

        $settings = self::getSettings();
        $minContractMonths = (int) ($settings['affiliate_contract_min_months'] ?? 4);

        // Jika masa aktif >= batas bulan kontrak, maka bebas pinalti
        if ($diffInMonths >= $minContractMonths) {
            return null;
        }

        // 3. Cek apakah sudah pernah dipotong pinalti sebelumnya untuk pelanggan ini
        $alreadyClawedBack = AffiliateCommission::where('affiliate_id', $customer->sales_id)
            ->where('customer_id', $customer->id)
            ->where('type', 'clawback_booking')
            ->exists();

        if ($alreadyClawedBack) {
            return null;
        }

        // 4. Cari profil afiliasi
        $affiliate = AffiliateProfile::where('user_id', $customer->sales_id)->first();
        if (!$affiliate) {
            $user = User::find($customer->sales_id);
            if ($user) {
                $affiliate = self::ensureAffiliateProfile($user);
            }
        }

        if (!$affiliate) {
            return null;
        }

        // 5. Cari nominal fee booking yang pernah diberikan
        $bookingComm = AffiliateCommission::where('affiliate_id', $customer->sales_id)
            ->where('customer_id', $customer->id)
            ->where('type', 'booking')
            ->first();

        $penaltyAmount = $bookingComm ? (float) $bookingComm->amount : (float) $settings['affiliate_commission_booking'];

        if ($penaltyAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($affiliate, $customer, $penaltyAmount, $diffInDays, $diffInMonths, $minContractMonths) {
            $remainingDays = $diffInDays % 30;
            $durationStr = $diffInMonths > 0 
                ? "{$diffInMonths} bulan {$remainingDays} hari" 
                : "{$diffInDays} hari";

            $commission = AffiliateCommission::create([
                'affiliate_id' => $affiliate->user_id,
                'customer_id' => $customer->id,
                'source_user_id' => $affiliate->user_id,
                'type' => 'clawback_booking',
                'tier_level' => 0,
                'amount' => -$penaltyAmount,
                'description' => "Pinalti Otomatis: Pemotongan Fee Booking pelanggan {$customer->name} ({$customer->customer_code}) berhenti sebelum {$minContractMonths} bulan (Masa aktif: {$durationStr})",
                'status' => 'approved',
                'paid_at' => now(),
            ]);

            // Potong saldo dompet afiliasi & total earned
            $affiliate->decrement('balance', $penaltyAmount);
            $affiliate->decrement('total_earned', $penaltyAmount);

            return [
                'penalty_applied' => true,
                'amount' => $penaltyAmount,
                'affiliate_name' => $affiliate->user?->name ?? 'Sales',
                'duration' => $durationStr,
                'commission' => $commission,
            ];
        });
    }

    /**
     * Catat Komisi Bulanan (Multi-Tier) saat invoice pelanggan berhasil dibayar/lunas.
     */
    public static function recordMonthlyCommission(Invoice $invoice): array
    {
        $customer = $invoice->customer;
        if (!$customer || !$customer->sales_id) {
            return [];
        }

        // Cek apakah komisi invoice ini sudah pernah dicatat
        $alreadyRecorded = AffiliateCommission::where('invoice_id', $invoice->id)->exists();
        if ($alreadyRecorded) {
            return [];
        }

        $directAffiliate = AffiliateProfile::where('user_id', $customer->sales_id)
            ->where('status', 'active')
            ->first();

        if (!$directAffiliate) {
            return [];
        }

        $settings = self::getSettings();
        $commissions = [];

        DB::transaction(function () use ($invoice, $customer, $directAffiliate, $settings, &$commissions) {
            // ── Tier 0: Direct Sales Commission ──
            $directAmount = $settings['affiliate_commission_monthly'];
            if ($directAmount > 0) {
                $commDirect = AffiliateCommission::create([
                    'affiliate_id' => $directAffiliate->user_id,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'source_user_id' => $directAffiliate->user_id,
                    'type' => 'monthly_direct',
                    'tier_level' => 0,
                    'amount' => $directAmount,
                    'description' => "Komisi Bulanan Invoice #{$invoice->invoice_number} ({$customer->name})",
                    'status' => 'approved',
                    'paid_at' => now(),
                ]);

                $directAffiliate->increment('balance', $directAmount);
                $directAffiliate->increment('total_earned', $directAmount);
                $commissions[] = $commDirect;
            }

            // ── Tier 1: Upline Level 1 (Downline 1) ──
            if ($directAffiliate->upline_id) {
                $upline1 = AffiliateProfile::where('user_id', $directAffiliate->upline_id)
                    ->where('status', 'active')
                    ->first();

                $upline1Amount = $settings['affiliate_commission_upline_1'];
                if ($upline1 && $upline1Amount > 0) {
                    $commUpline1 = AffiliateCommission::create([
                        'affiliate_id' => $upline1->user_id,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'source_user_id' => $directAffiliate->user_id,
                        'type' => 'upline_tier_1',
                        'tier_level' => 1,
                        'amount' => $upline1Amount,
                        'description' => "Komisi Upline Level 1 dari Mitra {$directAffiliate->user->name} (Inv #{$invoice->invoice_number})",
                        'status' => 'approved',
                        'paid_at' => now(),
                    ]);

                    $upline1->increment('balance', $upline1Amount);
                    $upline1->increment('total_earned', $upline1Amount);
                    $commissions[] = $commUpline1;

                    // ── Tier 2: Upline Level 2 (Downline 2) ──
                    if ($upline1->upline_id) {
                        $upline2 = AffiliateProfile::where('user_id', $upline1->upline_id)
                            ->where('status', 'active')
                            ->first();

                        $upline2Amount = $settings['affiliate_commission_upline_2'];
                        if ($upline2 && $upline2Amount > 0) {
                            $commUpline2 = AffiliateCommission::create([
                                'affiliate_id' => $upline2->user_id,
                                'customer_id' => $customer->id,
                                'invoice_id' => $invoice->id,
                                'source_user_id' => $directAffiliate->user_id,
                                'type' => 'upline_tier_2',
                                'tier_level' => 2,
                                'amount' => $upline2Amount,
                                'description' => "Komisi Upline Level 2 dari Mitra {$directAffiliate->user->name} (Inv #{$invoice->invoice_number})",
                                'status' => 'approved',
                                'paid_at' => now(),
                            ]);

                            $upline2->increment('balance', $upline2Amount);
                            $upline2->increment('total_earned', $upline2Amount);
                            $commissions[] = $commUpline2;
                        }
                    }
                }
            }
        });

        return $commissions;
    }

    /**
     * Ajukan penarikan saldo komisi afiliasi.
     */
    public static function requestWithdrawal(User $user, float $amount, ?string $notes = null): AffiliateWithdrawal
    {
        $profile = AffiliateProfile::where('user_id', $user->id)->firstOrFail();
        $settings = self::getSettings();
        $minWithdraw = $settings['affiliate_min_withdraw'];

        if ($amount < $minWithdraw) {
            throw new \Exception("Minimal penarikan komisi adalah Rp " . number_format($minWithdraw, 0, ',', '.'));
        }

        if ($amount > (float) $profile->balance) {
            throw new \Exception("Saldo komisi Anda tidak mencukupi (Tersedia: Rp " . number_format((float) $profile->balance, 0, ',', '.') . ")");
        }

        if (empty($profile->bank_name) || empty($profile->bank_account_number)) {
            throw new \Exception("Harap lengkapi informasi rekening bank / e-wallet di profil Anda terlebih dahulu.");
        }

        return DB::transaction(function () use ($profile, $amount, $notes) {
            $withdrawal = AffiliateWithdrawal::create([
                'affiliate_id' => $profile->user_id,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'bank_name' => $profile->bank_name,
                'bank_account_number' => $profile->bank_account_number,
                'bank_account_holder' => $profile->bank_account_holder ?: $profile->user->name,
                'status' => 'pending',
                'reference_no' => 'WD-' . strtoupper(Str::random(8)),
                'notes' => $notes,
            ]);

            // Potong saldo sementara
            $profile->decrement('balance', $amount);

            return $withdrawal;
        });
    }

    /**
     * Konfirmasi pencairan dana oleh Admin.
     */
    public static function approveWithdrawal(AffiliateWithdrawal $withdrawal, User $admin, ?string $receiptProof = null, ?string $referenceNo = null): void
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception("Permintaan penarikan ini sudah diproses sebelumnya.");
        }

        DB::transaction(function () use ($withdrawal, $admin, $receiptProof, $referenceNo) {
            $withdrawal->update([
                'status' => 'approved',
                'receipt_proof' => $receiptProof ?? $withdrawal->receipt_proof,
                'reference_no' => $referenceNo ?? $withdrawal->reference_no,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $profile = AffiliateProfile::where('user_id', $withdrawal->affiliate_id)->first();
            if ($profile) {
                $profile->increment('total_withdrawn', $withdrawal->amount);
            }
        });
    }

    /**
     * Tolak pencairan dana oleh Admin (kembalikan saldo).
     */
    public static function rejectWithdrawal(AffiliateWithdrawal $withdrawal, User $admin, string $reason = ''): void
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception("Permintaan penarikan ini sudah diproses sebelumnya.");
        }

        DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $withdrawal->update([
                'status' => 'rejected',
                'notes' => $withdrawal->notes ? $withdrawal->notes . "\nAlasan Penolakan: " . $reason : "Alasan Penolakan: " . $reason,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            // Kembalikan saldo ke affiliate
            $profile = AffiliateProfile::where('user_id', $withdrawal->affiliate_id)->first();
            if ($profile) {
                $profile->increment('balance', $withdrawal->amount);
            }
        });
    }
}
