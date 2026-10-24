<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
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

        // Pelanggan yang diajak
        $customers = $profile->customers()
            ->with(['package', 'areaModel'])
            ->latest('registration_date')
            ->get()
            ->map(function ($cust) use ($customerCommissions) {
                $totalCommissions = $customerCommissions->get($cust->id, 0);

                return [
                    'id' => $cust->id,
                    'customer_code' => $cust->customer_code,
                    'name' => $cust->name,
                    'phone' => $cust->phone,
                    'package_name' => $cust->package?->name ?? 'Internet Rumah',
                    'status' => $cust->status,
                    'registration_date' => $cust->registration_date ? $cust->registration_date->format('d M Y') : '-',
                    'total_commission' => (float) $totalCommissions,
                ];
            });

        // Downlines Level 1 (Diajak Langsung)
        $downlinesL1 = AffiliateProfile::where('upline_id', $user->id)
            ->where('user_id', '!=', $user->id)
            ->whereHas('user')
            ->with('user')
            ->withCount('customers')
            ->get()
            ->map(function ($dl) {
                $custCount = $dl->customers_count;
                return [
                    'id' => $dl->id,
                    'user_id' => $dl->user_id,
                    'name' => $dl->user?->name ?? 'Mitra',
                    'whatsapp' => $dl->whatsapp ?? $dl->user?->phone,
                    'referral_code' => $dl->referral_code,
                    'total_customers' => $custCount,
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
                ->withCount('customers')
                ->get()
                ->map(function ($dl) {
                    return [
                        'id' => $dl->id,
                        'name' => $dl->user?->name ?? 'Mitra',
                        'whatsapp' => $dl->whatsapp ?? $dl->user?->phone,
                        'upline_name' => $dl->upline?->name ?? '-',
                        'referral_code' => $dl->referral_code,
                        'total_customers' => $dl->customers_count,
                        'joined_at' => $dl->created_at->format('d M Y'),
                    ];
                });
        }

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

        $settings = AffiliateService::getSettings();
        $isEmployee = AffiliateService::isInternalEmployee($user);
        $pendingPayrollIncentives = $isEmployee
            ? (float) \App\Models\Incentive::where('user_id', $user->id)
                ->where('status', 'pending')
                ->where(function($q) {
                    $q->where('reference_id', 'like', 'booking_cust_%')
                      ->orWhere('reference_id', 'like', 'aff_%');
                })->sum('amount')
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
            'tab' => in_array($request->query('tab'), ['customers', 'downlines', 'commissions', 'withdrawals', 'bank'], true)
                ? $request->query('tab') : 'customers',
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
                'active_customers' => $customers->where('status', 'active')->count(),
                'total_customers' => $customers->count(),
                'total_downlines' => count($downlinesL1) + count($downlinesL2),
                'downlines_l1_count' => count($downlinesL1),
                'downlines_l2_count' => count($downlinesL2),
                'pending_payroll_incentives' => $pendingPayrollIncentives,
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
