<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AffiliatePortalController extends Controller
{
    /**
     * Dashboard khusus Mitra Afiliasi & Sales.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Karyawan dari semua jabatan dapat menggunakan portal pribadi ini.
        $hasAccess = AffiliateService::canAccessPortal($user);

        if (!$hasAccess) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses ke Portal Afiliasi.');
        }

        $profile = AffiliateService::ensureAffiliateProfile($user);

        $commissionTotals = AffiliateCommission::where('affiliate_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'paid', 'payroll'])
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('clawback_booking', 'clawback_survey') THEN -ABS(amount) ELSE amount END), 0) AS total")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'booking' THEN amount ELSE 0 END), 0) AS booking")
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('monthly_direct', 'upline_tier_1', 'upline_tier_2') THEN amount ELSE 0 END), 0) AS recurring")
            ->first();
        $customerCommissions = AffiliateCommission::where('affiliate_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'paid', 'payroll'])
            ->selectRaw("customer_id, SUM(CASE WHEN type IN ('clawback_booking', 'clawback_survey') THEN -ABS(amount) ELSE amount END) AS total")
            ->groupBy('customer_id')->pluck('total', 'customer_id');

        $settings = AffiliateService::getSettings();
        $directMonthlyRate = (float) ($settings['affiliate_commission_monthly'] ?? 5000);
        $upline1CommissionRate = (float) ($settings['affiliate_commission_upline_1'] ?? 2000);
        $upline2CommissionRate = (float) ($settings['affiliate_commission_upline_2'] ?? 1000);

        // Filter Pelanggan Aktif yang berhak atas komisi:
        // - Pelanggan baru / non-migrasi: status active
        // - Pelanggan hasil migrasi: status active DAN aktivasi pada bulan Oktober 2026
        $octStart = '2026-10-01 00:00:00';
        $octEnd = '2026-10-31 23:59:59';

        $activeEligibleCustomerFilter = function ($q) use ($octStart, $octEnd) {
            $q->where('status', 'active')
              ->where(function ($sq) use ($octStart, $octEnd) {
                  $sq->where(function ($sub) {
                      $sub->where('is_migrated', false)
                          ->orWhereNull('is_migrated');
                  })->orWhere(function ($sub) use ($octStart, $octEnd) {
                      $sub->where('is_migrated', true)
                          ->where(function ($dq) use ($octStart, $octEnd) {
                              $dq->whereBetween('activation_date', [$octStart, $octEnd])
                                 ->orWhere(function ($fq) use ($octStart, $octEnd) {
                                     $fq->whereNull('activation_date')
                                        ->whereBetween('registration_date', [$octStart, $octEnd]);
                                 });
                          });
                  });
              });
        };

        // Pelanggan yang diajak
        $customers = $profile->customers()
            ->with(['package', 'areaModel'])
            ->latest('registration_date')
            ->get()
            ->map(function ($cust) use ($customerCommissions, $directMonthlyRate, $octStart, $octEnd) {
                $totalCommissions = $customerCommissions->get($cust->id, 0);

                $isActiveAndEligible = false;
                if ($cust->status === 'active') {
                    if (!$cust->is_migrated) {
                        $isActiveAndEligible = true;
                    } else {
                        $actDate = $cust->activation_date ? $cust->activation_date->format('Y-m-d') : ($cust->registration_date ? $cust->registration_date->format('Y-m-d') : null);
                        if ($actDate && $actDate >= '2026-10-01' && $actDate <= '2026-10-31') {
                            $isActiveAndEligible = true;
                        }
                    }
                }

                return [
                    'id' => $cust->id,
                    'customer_code' => $cust->customer_code,
                    'name' => $cust->name,
                    'phone' => $cust->phone,
                    'package_name' => $cust->package?->name ?? 'Internet Rumah',
                    'status' => $cust->status,
                    'is_migrated' => (bool) $cust->is_migrated,
                    'is_eligible_commission' => $isActiveAndEligible,
                    'monthly_commission_rate' => $isActiveAndEligible ? $directMonthlyRate : 0,
                    'registration_date' => $cust->registration_date ? $cust->registration_date->format('d M Y') : '-',
                    'activation_date' => $cust->activation_date ? $cust->activation_date->format('d M Y') : '-',
                    'total_commission' => (float) $totalCommissions,
                ];
            });

        // Downlines Level 1 (Diajak Langsung)
        $downlinesL1 = AffiliateProfile::where('upline_id', $user->id)
            ->where('user_id', '!=', $user->id)
            ->whereHas('user')
            ->with('user')
            ->withCount([
                'customers',
                'customers as active_customers_count' => $activeEligibleCustomerFilter,
                'customers as all_active_customers_count' => function ($q) {
                    $q->where('status', 'active');
                }
            ])
            ->get()
            ->map(function ($dl) use ($upline1CommissionRate) {
                $custCount = (int) $dl->customers_count;
                $activeCount = (int) $dl->active_customers_count;
                $allActiveCount = (int) ($dl->all_active_customers_count ?? $activeCount);
                $monthlyComm = $activeCount * $upline1CommissionRate;

                return [
                    'id' => $dl->id,
                    'user_id' => $dl->user_id,
                    'name' => $dl->user?->name ?? 'Mitra',
                    'whatsapp' => $dl->whatsapp ?? $dl->user?->phone,
                    'referral_code' => $dl->referral_code,
                    'total_customers' => $custCount,
                    'active_customers' => $activeCount,
                    'all_active_customers' => $allActiveCount,
                    'commission_monthly' => $monthlyComm,
                    'commission_rate' => $upline1CommissionRate,
                    'joined_at' => $dl->created_at->format('d M Y'),
                ];
            });

        // Downlines Level 2 (Diajak oleh Downline Level 1)
        $l1UserIds = $downlinesL1->pluck('user_id')->toArray();
        $downlinesL2 = [];
        if (!empty($l1UserIds)) {
            $downlinesL2 = AffiliateProfile::whereIn('upline_id', $l1UserIds)
                ->where('user_id', '!=', $user->id)
                ->whereNotIn('user_id', $l1UserIds)
                ->whereHas('user')
                ->with(['user', 'upline'])
                ->withCount([
                    'customers',
                    'customers as active_customers_count' => $activeEligibleCustomerFilter,
                    'customers as all_active_customers_count' => function ($q) {
                        $q->where('status', 'active');
                    }
                ])
                ->get()
                ->map(function ($dl) use ($upline2CommissionRate) {
                    $custCount = (int) $dl->customers_count;
                    $activeCount = (int) $dl->active_customers_count;
                    $allActiveCount = (int) ($dl->all_active_customers_count ?? $activeCount);
                    $monthlyComm = $activeCount * $upline2CommissionRate;

                    return [
                        'id' => $dl->id,
                        'name' => $dl->user?->name ?? 'Mitra',
                        'whatsapp' => $dl->whatsapp ?? $dl->user?->phone,
                        'upline_name' => $dl->upline?->name ?? '-',
                        'referral_code' => $dl->referral_code,
                        'total_customers' => $custCount,
                        'active_customers' => $activeCount,
                        'all_active_customers' => $allActiveCount,
                        'commission_monthly' => $monthlyComm,
                        'commission_rate' => $upline2CommissionRate,
                        'joined_at' => $dl->created_at->format('d M Y'),
                    ];
                });
        }

        $totalDownlinesL1ActiveCustomers = (int) $downlinesL1->sum('active_customers');
        $totalDownlinesL1Commission = (float) $downlinesL1->sum('commission_monthly');
        $totalDownlinesL2ActiveCustomers = (int) collect($downlinesL2)->sum('active_customers');
        $totalDownlinesL2Commission = (float) collect($downlinesL2)->sum('commission_monthly');
        $potentialMonthlyNetworkCommission = $totalDownlinesL1Commission + $totalDownlinesL2Commission;

        // Riwayat Komisi
        $commissions = AffiliateCommission::where('affiliate_id', $user->id)
            ->with(['customer', 'sourceUser'])
            ->latest('id')
            ->paginate(15, ['*'], 'commissions_page')
            ->appends(['tab' => 'commissions'])
            ->through(function ($c) {
                return [
                    'id' => $c->id,
                    'type' => $c->type,
                    'tier_level' => $c->tier_level,
                    'amount' => (float) $c->amount,
                    'description' => $c->description,
                    'customer_name' => $c->customer?->name,
                    'source_user_name' => $c->sourceUser?->name,
                    'status' => $c->status,
                    'date' => $c->created_at->format('d M Y H:i'),
                ];
            });

        // Riwayat Penarikan Dana
        $withdrawals = AffiliateWithdrawal::where('affiliate_id', $user->id)
            ->latest('id')
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'reference_no' => $w->reference_no,
                    'amount' => (float) $w->amount,
                    'bank_info' => "{$w->bank_name} - {$w->bank_account_number} a.n {$w->bank_account_holder}",
                    'status' => $w->status,
                    'receipt_proof' => $w->receipt_proof ? asset('storage/' . $w->receipt_proof) : null,
                    'notes' => $w->notes,
                    'date' => $w->created_at->format('d M Y H:i'),
                    'processed_at' => $w->processed_at ? $w->processed_at->format('d M Y H:i') : null,
                ];
            });

        $isEmployee = AffiliateService::isInternalEmployee($user);
        $pendingPayrollIncentives = $isEmployee
            ? $this->pendingAffiliatePayrollIncentives($user->id)
            : 0;

        $unpaidCustomers = AffiliateService::getUnpaidCustomersForAffiliate($user);
        $payoutDay = (int) ($settings['affiliate_payout_day'] ?? 15);
        $currentDay = (int) now()->format('j');
        $isPayoutOpen = $currentDay >= $payoutDay;
        $nextPayoutDate = $currentDay < $payoutDay
            ? now()->day($payoutDay)->format('d M Y')
            : now()->addMonth()->day($payoutDay)->format('d M Y');

        $payoutInfo = [
            'payout_day' => $payoutDay,
            'is_payout_open' => $isPayoutOpen,
            'current_day' => $currentDay,
            'next_payout_date' => $nextPayoutDate,
            'has_unpaid_customers' => $unpaidCustomers->isNotEmpty(),
            'unpaid_customers_count' => $unpaidCustomers->count(),
            'total_unpaid_amount' => (float) $unpaidCustomers->sum('total_unpaid_amount'),
            'unpaid_customers' => $unpaidCustomers,
            'can_withdraw' => !$isEmployee && $isPayoutOpen && $unpaidCustomers->isEmpty() && ((float) $profile->balance >= (float) $settings['affiliate_min_withdraw']),
        ];

        $appUrl = config('app.url', url('/'));

        return Inertia::render('Affiliate/Dashboard', [
            'tab' => in_array($request->query('tab'), ['overview', 'customers', 'downlines', 'commissions', 'withdrawals', 'bank'], true)
                ? $request->query('tab') : 'overview',
            'is_employee' => $isEmployee,
            'pending_payroll_incentives' => $pendingPayrollIncentives,
            'payout_info' => $payoutInfo,
            'profile' => [
                'id' => $profile->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'referral_code' => $profile->referral_code,
                'referral_link_affiliate' => $appUrl . '/register-affiliate?ref=' . $profile->referral_code,
                'balance' => (float) $profile->balance,
                'total_earned' => (float) $commissionTotals->total,
                'total_withdrawn' => (float) $profile->total_withdrawn,
                'bank_name' => $profile->bank_name,
                'bank_account_number' => $profile->bank_account_number,
                'bank_account_holder' => $profile->bank_account_holder,
                'whatsapp' => $profile->whatsapp,
                'address' => $profile->address,
                'upline_name' => $profile->upline?->name,
            ],
            'kpi' => [
                'balance' => (float) $profile->balance,
                'total_earned' => (float) $commissionTotals->total,
                'booking_commissions' => (float) $commissionTotals->booking,
                'recurring_commissions' => (float) $commissionTotals->recurring,
                'pending_bookings' => $customers->where('status', 'booking')->count(),
                'active_customers' => $customers->where('is_eligible_commission', true)->count(),
                'total_active_all' => $customers->where('status', 'active')->count(),
                'total_customers' => $customers->count(),
                'total_downlines' => count($downlinesL1) + count($downlinesL2),
                'downlines_l1_count' => count($downlinesL1),
                'downlines_l2_count' => count($downlinesL2),
                'downlines_l1_active_customers' => $totalDownlinesL1ActiveCustomers,
                'downlines_l2_active_customers' => $totalDownlinesL2ActiveCustomers,
                'total_downlines_active_customers' => $totalDownlinesL1ActiveCustomers + $totalDownlinesL2ActiveCustomers,
                'downlines_l1_commission' => $totalDownlinesL1Commission,
                'downlines_l2_commission' => $totalDownlinesL2Commission,
                'potential_monthly_network_commission' => $potentialMonthlyNetworkCommission,
                'pending_payroll_incentives' => $pendingPayrollIncentives,
            ],
            'downlines_l1_summary' => [
                'total_mitra' => count($downlinesL1),
                'total_active_customers' => $totalDownlinesL1ActiveCustomers,
                'total_commission' => $totalDownlinesL1Commission,
                'rate' => $upline1CommissionRate,
            ],
            'downlines_l2_summary' => [
                'total_mitra' => count($downlinesL2),
                'total_active_customers' => $totalDownlinesL2ActiveCustomers,
                'total_commission' => $totalDownlinesL2Commission,
                'rate' => $upline2CommissionRate,
            ],
            'customers' => $customers,
            'downlinesL1' => $downlinesL1,
            'downlinesL2' => $downlinesL2,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'settings' => $settings,
        ]);
    }

    /**
     * Jumlahkan insentif afiliasi yang masih memiliki sumber transaksi.
     *
     * Data booking lama dapat menjadi orphan ketika pelanggan dihapus. Jangan
     * tampilkan lagi sebagai hak bayar di portal sales karena tidak ada booking
     * yang dapat diverifikasi. Komisi berbasis invoice (aff_*) tetap dihitung
     * karena dapat mencakup komisi upline yang tidak memiliki customer langsung.
     */
    private function pendingAffiliatePayrollIncentives(int $userId): float
    {
        return (float) \App\Models\Incentive::query()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where(function ($references) {
                $references
                    ->where(function ($booking) {
                        $booking->where('reference_id', 'like', 'booking_cust_%')
                            ->whereExists(function ($customers) {
                                $customers->select(DB::raw(1))
                                    ->from('customers')
                                    ->whereRaw('customers.id = substr(incentives.reference_id, 14)')
                                    ->whereColumn('customers.sales_id', 'incentives.user_id');
                            });
                    })
                    ->orWhere('reference_id', 'like', 'aff_%');
            })
            ->sum('amount');
    }

    /**
     * Update data rekening bank / e-wallet.
     */
     public function updateBank(Request $request)
     {
         $user = Auth::user();
         $hasAccess = AffiliateService::canAccessPortal($user);

         if (!$hasAccess) {
             return back()->with('error', 'Anda tidak memiliki hak akses.');
         }

         $validated = $request->validate([
             'bank_name' => 'required|string|max:50',
             'bank_account_number' => 'required|string|max:50',
             'bank_account_holder' => 'required|string|max:100',
             'whatsapp' => 'nullable|string|max:25',
         ]);

         $profile = AffiliateService::ensureAffiliateProfile($user);
         $profile->update($validated);

         return back()->with('success', 'Rekening bank / E-Wallet berhasil diperbarui.');
     }

    /**
     * Ajukan penarikan saldo komisi.
     */
     public function withdraw(Request $request)
     {
         $user = Auth::user();
         $hasAccess = AffiliateService::canAccessPortal($user);

         if (!$hasAccess) {
             return back()->with('error', 'Anda tidak memiliki hak akses.');
         }

         $validated = $request->validate([
             'amount' => 'required|numeric|min:1',
             'notes' => 'nullable|string|max:255',
         ]);

         try {
             AffiliateService::requestWithdrawal(Auth::user(), (float) $validated['amount'], $validated['notes'] ?? null);
             return back()->with('success', 'Pengajuan penarikan dana berhasil dikirim! Admin akan segera memproses pencairan ke rekening Anda.');
         } catch (\Exception $e) {
             return back()->with('error', $e->getMessage());
         }
     }
}
