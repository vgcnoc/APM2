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
            'affiliate_payout_day' => (int) Setting::get('affiliate_payout_day', 15),
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
        if (isset($data['affiliate_payout_day'])) {
            Setting::set('affiliate_payout_day', (string) $data['affiliate_payout_day']);
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
     * Cek apakah user adalah Karyawan Internal (Internal Employee).
     * Jika ya, komisi masuk ke Insentif Slip Gaji (Payroll), bukan ke dompet afiliasi.
     */
    public static function isInternalEmployee(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = strtolower((string) $user->role);
        // Role khusus pihak luar
        if (in_array($role, ['affiliate', 'reseller', 'customer', 'mitra'])) {
            return false;
        }

        // Terdaftar di master employee (tabel employees)
        if (\App\Models\Employee::where('email', $user->email)->exists()) {
            return true;
        }

        // Memiliki gaji pokok / riwayat payroll
        if ((float) $user->base_salary > 0 || $user->payrolls()->exists()) {
            return true;
        }

        // Role internal operasional / kantor
        $internalRoles = ['admin', 'teknisi', 'noc', 'cs', 'admin cs', 'sales', 'dispatcher', 'auditor', 'aktivator', 'staff', 'karyawan', 'superadmin', 'maintenance', 'kepala teknisi', 'manager', 'finance', 'hrd'];
        if (in_array($role, $internalRoles)) {
            return true;
        }

        return false;
    }

    /**
     * Catat Komisi Booking (Sekali Bayar) saat pelanggan baru booking/registrasi/aktif.
     * Khusus karyawan: Masuk ke Insentif Payroll (Status Pending).
     * Mitra Luar: Masuk ke Saldo Dompet Afiliasi (Balance bertambah).
     */
    public static function recordBookingCommission(Customer $customer): ?AffiliateCommission
    {
        if (!$customer->sales_id) {
            return null;
        }

        $user = User::find($customer->sales_id);
        if (!$user) {
            return null;
        }

        // Cek apakah komisi booking sudah pernah dicatat di tabel AffiliateCommission
        $alreadyRecorded = AffiliateCommission::where('affiliate_id', $customer->sales_id)
            ->where('customer_id', $customer->id)
            ->where('type', 'booking')
            ->exists();

        if ($alreadyRecorded) {
            return null;
        }

        $isEmployee = self::isInternalEmployee($user);

        // Jika karyawan, cek juga apakah sudah ada di tabel incentives
        if ($isEmployee) {
            $alreadyInIncentive = \App\Models\Incentive::where('reference_id', 'booking_cust_' . $customer->id)->exists();
            if ($alreadyInIncentive) {
                return null;
            }
        }

        $settings = self::getSettings();
        $amount = (float) $settings['affiliate_commission_booking'];

        // Jika karyawan memiliki custom fee booking di user atau MasterFee
        if ($isEmployee) {
            if ($user->booking_fee > 0) {
                $amount = (float) $user->booking_fee;
            } else {
                $masterBooking = \App\Models\MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal');
                if ($masterBooking > 0) {
                    $amount = (float) $masterBooking;
                }
            }
        }

        if ($amount <= 0) {
            return null;
        }

        $affiliate = self::ensureAffiliateProfile($user);

        return DB::transaction(function () use ($affiliate, $user, $customer, $amount, $isEmployee) {
            $codeLabel = ($customer->customer_code && !preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/i', $customer->customer_code))
                ? " ({$customer->customer_code})"
                : '';

            if ($isEmployee) {
                // ── KARYAWAN: Masuk ke Insentif Payroll (Status Pending) ──
                \App\Models\Incentive::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'type' => 'auto',
                    'description' => "Fee Booking Pelanggan: {$customer->name}{$codeLabel}",
                    'reference_id' => 'booking_cust_' . $customer->id,
                    'incentive_date' => now(),
                    'status' => 'pending',
                ]);

                // Catat di AffiliateCommission untuk riwayat audit log (status 'payroll', dompet TIDAK ditambah)
                $commission = AffiliateCommission::create([
                    'affiliate_id' => $user->id,
                    'customer_id' => $customer->id,
                    'source_user_id' => $user->id,
                    'type' => 'booking',
                    'tier_level' => 0,
                    'amount' => $amount,
                    'description' => "Fee Booking Pelanggan: {$customer->name}{$codeLabel} [Masuk Insentif Payroll]",
                    'status' => 'payroll',
                    'paid_at' => null,
                ]);

                return $commission;
            } else {
                // ── MITRA LUAR: Masuk ke Dompet Afiliasi (Balance bertambah) ──
                $commission = AffiliateCommission::create([
                    'affiliate_id' => $customer->sales_id,
                    'customer_id' => $customer->id,
                    'source_user_id' => $customer->sales_id,
                    'type' => 'booking',
                    'tier_level' => 0,
                    'amount' => $amount,
                    'description' => "Komisi Booking Pelanggan Baru: {$customer->name}{$codeLabel}",
                    'status' => 'approved',
                    'paid_at' => now(),
                ]);

                $affiliate->increment('balance', $amount);
                $affiliate->increment('total_earned', $amount);

                return $commission;
            }
        });
    }

    /**
     * Otomatis Potong (Clawback) Fee Booking jika pelanggan berhenti di bawah batas minimal kontrak (default: 4 bulan).
     * Khusus karyawan: Otomatis masuk ke Deductions (Potongan Slip Gaji Payroll).
     * Mitra Luar: Otomatis dipotong dari Saldo Dompet Afiliasi.
     */
    public static function handleEarlyTerminationPenalty(Customer $customer, ?string $cancelDate = null): ?array
    {
        // 1. Pelanggan wajib memiliki sales_id (mitra afiliasi / sales)
        if (!$customer->sales_id) {
            return null;
        }

        $user = User::find($customer->sales_id);
        if (!$user) {
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

        $isEmployee = self::isInternalEmployee($user);

        // 3. Cek apakah sudah pernah dipotong pinalti sebelumnya untuk pelanggan ini
        $alreadyClawedBack = AffiliateCommission::where('affiliate_id', $customer->sales_id)
            ->where('customer_id', $customer->id)
            ->where('type', 'clawback_booking')
            ->exists();

        if ($alreadyClawedBack) {
            return null;
        }

        if ($isEmployee) {
            $alreadyDeducted = \App\Models\Deduction::where('reference_id', 'clawback_booking_cust_' . $customer->id)->exists();
            if ($alreadyDeducted) {
                return null;
            }
        }

        // 4. Cari profil afiliasi
        $affiliate = self::ensureAffiliateProfile($user);

        // 5. Cari nominal fee booking yang pernah diberikan
        $bookingComm = AffiliateCommission::where('affiliate_id', $customer->sales_id)
            ->where('customer_id', $customer->id)
            ->where('type', 'booking')
            ->first();

        $penaltyAmount = $bookingComm ? (float) $bookingComm->amount : (float) $settings['affiliate_commission_booking'];

        if ($isEmployee && (!$bookingComm || $penaltyAmount <= 0)) {
            if ($user->booking_fee > 0) {
                $penaltyAmount = (float) $user->booking_fee;
            } else {
                $masterBooking = \App\Models\MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal');
                if ($masterBooking > 0) {
                    $penaltyAmount = (float) $masterBooking;
                }
            }
        }

        if ($penaltyAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($affiliate, $user, $customer, $penaltyAmount, $diffInDays, $diffInMonths, $minContractMonths, $isEmployee) {
            $remainingDays = $diffInDays % 30;
            $durationStr = $diffInMonths > 0 
                ? "{$diffInMonths} bulan {$remainingDays} hari" 
                : "{$diffInDays} hari";

            $codeLabel = ($customer->customer_code && !preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/i', $customer->customer_code))
                ? " ({$customer->customer_code})"
                : '';

            if ($isEmployee) {
                // ── KARYAWAN: Otomatis masuk ke Tabel Deductions (Potongan Slip Gaji Payroll) ──
                \App\Models\Deduction::create([
                    'user_id' => $user->id,
                    'amount' => $penaltyAmount,
                    'type' => 'auto',
                    'description' => "Pinalti Stop Dini < {$minContractMonths} Bln: Pemotongan Fee Booking {$customer->name}{$codeLabel} - Masa aktif: {$durationStr}",
                    'reference_id' => 'clawback_booking_cust_' . $customer->id,
                    'deduction_date' => now(),
                    'status' => 'pending',
                ]);

                // Catat di AffiliateCommission untuk audit log
                $commission = AffiliateCommission::create([
                    'affiliate_id' => $user->id,
                    'customer_id' => $customer->id,
                    'source_user_id' => $user->id,
                    'type' => 'clawback_booking',
                    'tier_level' => 0,
                    'amount' => -$penaltyAmount,
                    'description' => "Pinalti Stop Dini < {$minContractMonths} Bln: Pemotongan Fee Booking [Dipotong dari Slip Gaji Payroll] {$customer->name}{$codeLabel} - Masa aktif: {$durationStr}",
                    'status' => 'payroll',
                    'paid_at' => null,
                ]);

                return [
                    'penalty_applied' => true,
                    'is_employee' => true,
                    'target' => 'payroll',
                    'amount' => $penaltyAmount,
                    'affiliate_name' => $user->name,
                    'duration' => $durationStr,
                    'commission' => $commission,
                ];
            } else {
                // ── MITRA LUAR: Potong Saldo Dompet Afiliasi Langsung ──
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
                    'is_employee' => false,
                    'target' => 'wallet',
                    'amount' => $penaltyAmount,
                    'affiliate_name' => $affiliate->user?->name ?? 'Sales',
                    'duration' => $durationStr,
                    'commission' => $commission,
                ];
            }
        });
    }

    /**
     * Catat Komisi Bulanan (Multi-Tier) saat invoice pelanggan berhasil dibayar/lunas.
     * Khusus karyawan: Otomatis masuk ke Insentif Slip Gaji (Payroll).
     * Mitra Luar: Otomatis masuk ke Dompet Afiliasi (Balance bertambah).
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

        $directUser = User::find($customer->sales_id);
        if (!$directUser) {
            return [];
        }

        $directAffiliate = self::ensureAffiliateProfile($directUser);
        if ($directAffiliate->status !== 'active') {
            return [];
        }

        $settings = self::getSettings();
        $commissions = [];

        DB::transaction(function () use ($invoice, $customer, $directUser, $directAffiliate, $settings, &$commissions) {
            // ── Tier 0: Direct Sales Commission ──
            $directAmount = (float) $settings['affiliate_commission_monthly'];
            if ($directAmount > 0) {
                $isDirectEmployee = self::isInternalEmployee($directUser);

                if ($isDirectEmployee) {
                    \App\Models\Incentive::create([
                        'user_id' => $directUser->id,
                        'amount' => $directAmount,
                        'type' => 'auto',
                        'description' => "Komisi Pasif Bulanan Invoice #{$invoice->invoice_number} ({$customer->name})",
                        'reference_id' => "aff_monthly_inv_{$invoice->id}",
                        'incentive_date' => now(),
                        'status' => 'pending',
                    ]);

                    $commDirect = AffiliateCommission::create([
                        'affiliate_id' => $directUser->id,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'source_user_id' => $directUser->id,
                        'type' => 'monthly_direct',
                        'tier_level' => 0,
                        'amount' => $directAmount,
                        'description' => "Komisi Bulanan Invoice #{$invoice->invoice_number} ({$customer->name}) [Masuk Insentif Payroll]",
                        'status' => 'payroll',
                        'paid_at' => null,
                    ]);
                } else {
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
                }

                $commissions[] = $commDirect;
            }

            // ── Tier 1: Upline Level 1 (Downline 1) ──
            if ($directAffiliate->upline_id) {
                $upline1User = User::find($directAffiliate->upline_id);
                if ($upline1User) {
                    $upline1 = self::ensureAffiliateProfile($upline1User);
                    $upline1Amount = (float) $settings['affiliate_commission_upline_1'];

                    if ($upline1->status === 'active' && $upline1Amount > 0) {
                        $isUpline1Employee = self::isInternalEmployee($upline1User);

                        if ($isUpline1Employee) {
                            \App\Models\Incentive::create([
                                'user_id' => $upline1User->id,
                                'amount' => $upline1Amount,
                                'type' => 'auto',
                                'description' => "Komisi Downline Level 1 dari Mitra {$directUser->name} (Inv #{$invoice->invoice_number})",
                                'reference_id' => "aff_upline_1_inv_{$invoice->id}",
                                'incentive_date' => now(),
                                'status' => 'pending',
                            ]);

                            $commUpline1 = AffiliateCommission::create([
                                'affiliate_id' => $upline1User->id,
                                'customer_id' => $customer->id,
                                'invoice_id' => $invoice->id,
                                'source_user_id' => $directUser->id,
                                'type' => 'upline_tier_1',
                                'tier_level' => 1,
                                'amount' => $upline1Amount,
                                'description' => "Komisi Upline Level 1 dari Mitra {$directUser->name} (Inv #{$invoice->invoice_number}) [Masuk Insentif Payroll]",
                                'status' => 'payroll',
                                'paid_at' => null,
                            ]);
                        } else {
                            $commUpline1 = AffiliateCommission::create([
                                'affiliate_id' => $upline1->user_id,
                                'customer_id' => $customer->id,
                                'invoice_id' => $invoice->id,
                                'source_user_id' => $directUser->id,
                                'type' => 'upline_tier_1',
                                'tier_level' => 1,
                                'amount' => $upline1Amount,
                                'description' => "Komisi Upline Level 1 dari Mitra {$directUser->name} (Inv #{$invoice->invoice_number})",
                                'status' => 'approved',
                                'paid_at' => now(),
                            ]);

                            $upline1->increment('balance', $upline1Amount);
                            $upline1->increment('total_earned', $upline1Amount);
                        }

                        $commissions[] = $commUpline1;

                        // ── Tier 2: Upline Level 2 (Downline 2) ──
                        if ($upline1->upline_id) {
                            $upline2User = User::find($upline1->upline_id);
                            if ($upline2User) {
                                $upline2 = self::ensureAffiliateProfile($upline2User);
                                $upline2Amount = (float) $settings['affiliate_commission_upline_2'];

                                if ($upline2->status === 'active' && $upline2Amount > 0) {
                                    $isUpline2Employee = self::isInternalEmployee($upline2User);

                                    if ($isUpline2Employee) {
                                        \App\Models\Incentive::create([
                                            'user_id' => $upline2User->id,
                                            'amount' => $upline2Amount,
                                            'type' => 'auto',
                                            'description' => "Komisi Downline Level 2 dari Mitra {$directUser->name} (Inv #{$invoice->invoice_number})",
                                            'reference_id' => "aff_upline_2_inv_{$invoice->id}",
                                            'incentive_date' => now(),
                                            'status' => 'pending',
                                        ]);

                                        $commUpline2 = AffiliateCommission::create([
                                            'affiliate_id' => $upline2User->id,
                                            'customer_id' => $customer->id,
                                            'invoice_id' => $invoice->id,
                                            'source_user_id' => $directUser->id,
                                            'type' => 'upline_tier_2',
                                            'tier_level' => 2,
                                            'amount' => $upline2Amount,
                                            'description' => "Komisi Upline Level 2 dari Mitra {$directUser->name} (Inv #{$invoice->invoice_number}) [Masuk Insentif Payroll]",
                                            'status' => 'payroll',
                                            'paid_at' => null,
                                        ]);
                                    } else {
                                        $commUpline2 = AffiliateCommission::create([
                                            'affiliate_id' => $upline2->user_id,
                                            'customer_id' => $customer->id,
                                            'invoice_id' => $invoice->id,
                                            'source_user_id' => $directUser->id,
                                            'type' => 'upline_tier_2',
                                            'tier_level' => 2,
                                            'amount' => $upline2Amount,
                                            'description' => "Komisi Upline Level 2 dari Mitra {$directUser->name} (Inv #{$invoice->invoice_number})",
                                            'status' => 'approved',
                                            'paid_at' => now(),
                                        ]);

                                        $upline2->increment('balance', $upline2Amount);
                                        $upline2->increment('total_earned', $upline2Amount);
                                    }

                                    $commissions[] = $commUpline2;
                                }
                            }
                        }
                    }
                }
            }
        });

        return $commissions;
    }

    /**
     * Ambil daftar pelanggan yang dibooking oleh akun affiliate ini yang belum melunasi tagihan (status unpaid / partial).
     */
    public static function getUnpaidCustomersForAffiliate(User $user)
    {
        return Customer::where('sales_id', $user->id)
            ->whereHas('invoices', function ($q) {
                $q->whereIn('status', ['unpaid', 'partial']);
            })
            ->with(['package', 'invoices' => function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])->orderBy('due_date', 'asc');
            }])
            ->get()
            ->map(function ($cust) use ($user) {
                $unpaidInvoices = $cust->invoices;
                $totalUnpaid = (float) $unpaidInvoices->sum(function ($inv) {
                    return $inv->remaining ?: $inv->amount;
                });
                $earliestInvoice = $unpaidInvoices->first();
                $periodLabel = $earliestInvoice ? $earliestInvoice->period_label : '-';

                $custPhone = preg_replace('/[^0-9]/', '', (string) ($cust->phone ?? ''));
                if (str_starts_with($custPhone, '0')) {
                    $custPhone = '62' . substr($custPhone, 1);
                }

                $msg = "Halo Kak {$cust->name}, saya {$user->name} dari tim layanan internet WiFi. "
                    . "Sekadar menginfokan bahwa tagihan internet periode {$periodLabel} sebesar Rp " . number_format($totalUnpaid, 0, ',', '.') . " saat ini belum terbayar. "
                    . "Mohon dibantu pelunasannya ya Kak agar koneksi internet tetap aktif dan lancar. Terima kasih banyak 🙏";
                $waUrl = $custPhone ? "https://wa.me/{$custPhone}?text=" . urlencode($msg) : null;

                return [
                    'id' => $cust->id,
                    'customer_code' => $cust->customer_code,
                    'name' => $cust->name,
                    'phone' => $cust->phone,
                    'clean_phone' => $custPhone,
                    'package_name' => $cust->package?->name ?? 'Internet Rumah',
                    'unpaid_invoices_count' => $unpaidInvoices->count(),
                    'total_unpaid_amount' => (float) $totalUnpaid,
                    'period_label' => $periodLabel,
                    'due_date' => $earliestInvoice?->due_date?->format('d M Y') ?? '-',
                    'wa_reminder_url' => $waUrl,
                ];
            });
    }

    /**
     * Ajukan penarikan saldo komisi afiliasi.
     * Khusus fee affiliate orang luar dicairkan per tanggal 15.
     * Jika pelanggan yang dibooking belum bayar, fee tidak dapat dicairkan.
     */
    public static function requestWithdrawal(User $user, float $amount, ?string $notes = null): AffiliateWithdrawal
    {
        if (self::isInternalEmployee($user)) {
            throw new \Exception("Sebagai karyawan internal, seluruh fee booking dan komisi pasif Anda otomatis dicairkan melalui Slip Gaji (Insentif Payroll) setiap bulan.");
        }

        $profile = AffiliateProfile::where('user_id', $user->id)->firstOrFail();
        $settings = self::getSettings();
        $payoutDay = (int) ($settings['affiliate_payout_day'] ?? 15);
        $minWithdraw = $settings['affiliate_min_withdraw'];

        // 1. Validasi Jadwal Pencairan (Khusus Mitra Luar per tanggal 15)
        $currentDay = (int) now()->format('j');
        if ($currentDay < $payoutDay) {
            $nextDate = now()->day($payoutDay)->format('d M Y');
            throw new \Exception("Khusus fee affiliate orang luar dicairkan per tanggal {$payoutDay} setiap bulannya. Pencairan dana berikutnya dibuka pada tanggal {$nextDate}.");
        }

        // 2. Validasi Pelanggan Belum Bayar (Unpaid Invoices)
        $unpaidCustomers = self::getUnpaidCustomersForAffiliate($user);
        if ($unpaidCustomers->isNotEmpty()) {
            $count = $unpaidCustomers->count();
            $names = $unpaidCustomers->take(3)->pluck('name')->implode(', ');
            if ($count > 3) {
                $names .= ' dan ' . ($count - 3) . ' pelanggan lainnya';
            }
            throw new \Exception("Fee komisi tidak dapat dicairkan karena terdapat {$count} pelanggan yang Anda booking belum membayar tagihan ({$names}). Tolong dibantu melakukan penagihan ke pelanggan yang di-booking akun affiliate terlebih dahulu agar komisi Anda dapat dicairkan.");
        }

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

        // Cek apakah mitra memiliki pelanggan bookingan yang belum bayar
        $affiliateUser = User::find($withdrawal->affiliate_id);
        if ($affiliateUser) {
            $unpaid = self::getUnpaidCustomersForAffiliate($affiliateUser);
            if ($unpaid->isNotEmpty() && !request()->boolean('force_approve')) {
                $count = $unpaid->count();
                $names = $unpaid->take(2)->pluck('name')->implode(', ');
                throw new \Exception("Pencairan dana tertahan karena terdapat {$count} pelanggan bookingan mitra ini yang belum melunasi tagihan ({$names}). Mitra diminta membantu penagihan ke pelanggan terlebih dahulu.");
            }
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
