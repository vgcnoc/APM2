<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Models\Customer;
use App\Models\Invoice;
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
        // - Pelanggan hasil migrasi: status active DAN aktivasi mulai bulan Oktober 2026
        $activeEligibleCustomerFilter = function ($q) {
            $q->where('status', 'active')
              ->where(function ($sq) {
                  $sq->where(function ($sub) {
                      $sub->where('is_migrated', false)
                          ->orWhereNull('is_migrated');
                  })->orWhere(function ($sub) {
                      $sub->where('is_migrated', true)
                          ->where(function ($dq) {
                              $dq->whereDate('activation_date', '>=', '2026-10-01')
                                 ->orWhere(function ($fq) {
                                     $fq->whereNull('activation_date')
                                        ->whereDate('registration_date', '>=', '2026-10-01');
                                 });
                          });
                  });
              });
        };

        // Pelanggan yang diajak (terhitung dari aktivasi bulan Oktober 2026)
        $rawCustomers = $profile->customers()
            ->where(function ($q) {
                $q->where('is_migrated', false)
                  ->orWhere(function ($sub) {
                      $sub->whereDate('activation_date', '>=', '2026-10-01')
                          ->orWhere(function ($sub2) {
                              $sub2->whereNull('activation_date')
                                   ->whereDate('registration_date', '>=', '2026-10-01');
                          });
                  });
            })
            ->with(['package', 'areaModel'])
            ->latest('registration_date')
            ->get();

        $customerIds = $rawCustomers->pluck('id')->toArray();
        $unpaidInvoicesByCust = Invoice::whereIn('customer_id', $customerIds)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->groupBy('customer_id');

        $today = now();
        $todayEnd = now()->endOfDay();

        $customers = $rawCustomers->map(function ($cust) use ($customerCommissions, $directMonthlyRate, $unpaidInvoicesByCust, $user, $today, $todayEnd) {
            $totalCommissions = $customerCommissions->get($cust->id, 0);

            $isActiveAndEligible = false;
            if ($cust->status === 'active') {
                if (!$cust->is_migrated) {
                    $isActiveAndEligible = true;
                } else {
                    $actDate = $cust->activation_date ? $cust->activation_date->format('Y-m-d') : ($cust->registration_date ? $cust->registration_date->format('Y-m-d') : null);
                    if ($actDate && $actDate >= '2026-10-01') {
                        $isActiveAndEligible = true;
                    }
                }
            }

            $custUnpaidInvoices = $unpaidInvoicesByCust->get($cust->id, collect());
            $hasUnpaid = $custUnpaidInvoices->isNotEmpty();
            $totalUnpaid = (float) $custUnpaidInvoices->sum(function ($inv) {
                return $inv->remaining ?: $inv->amount;
            });
            $earliestInv = $custUnpaidInvoices->first();

            // Deteksi Status Prorata & Waktu Jatuh Tempo
            $hasProrataInvoice = $custUnpaidInvoices->contains(function ($inv) {
                return (bool) $inv->is_prorata 
                    || strtolower($inv->billing_type ?? '') === 'prorata' 
                    || str_contains(strtolower($inv->notes ?? ''), 'prorata');
            });
            $isProrata = $hasProrataInvoice || strtolower($cust->billing_type ?? '') === 'prorata';

            // Evaluasi apakah tagihan sudah terbit dan apakah sudah melewati batas jatuh tempo (Overdue)
            $isIssued = $earliestInv && $earliestInv->issued_date && $earliestInv->issued_date->startOfDay()->lte($today);
            $isBeforeIssueDate = $earliestInv && $earliestInv->issued_date && $earliestInv->issued_date->startOfDay()->isFuture();

            $overdueInvoices = $custUnpaidInvoices->filter(function ($inv) use ($todayEnd) {
                return $inv->due_date && $inv->due_date->endOfDay()->isPast();
            });
            $hasOverdue = $overdueInvoices->isNotEmpty();
            $isNotDueYet = $hasUnpaid && !$hasOverdue; // Belum waktunya bayar

            $custPhone = preg_replace('/[^0-9]/', '', (string) ($cust->phone ?? ''));
            if (str_starts_with($custPhone, '0')) {
                $custPhone = '62' . substr($custPhone, 1);
            } elseif (str_starts_with($custPhone, '8')) {
                $custPhone = '62' . $custPhone;
            }
            $periodLabel = $earliestInv?->period_label ?: ($earliestInv?->month ? "Bulan {$earliestInv->month}/{$earliestInv->year}" : 'berjalan');
            $issuedDateLabel = $earliestInv?->issued_date ? $earliestInv->issued_date->format('d M Y') : '1 ' . now()->format('M Y');
            $dueDateLabel = $earliestInv?->due_date ? $earliestInv->due_date->format('d M Y') : '12 ' . now()->format('M Y');

            // Pesan WA Bantu Tagih untuk semua pelanggan dengan tagihan belum lunas
            $waUrl = null;
            if ($hasUnpaid && !empty($custPhone)) {
                if ($hasOverdue) {
                    $waMsg = "Halo Kak {$cust->name}, saya {$user->name} dari tim layanan internet WiFi. "
                        . "Sekadar menginfokan bahwa tagihan internet periode {$periodLabel} sebesar Rp " . number_format($totalUnpaid, 0, ',', '.') . " saat ini telah melewati batas jatuh tempo ({$dueDateLabel}). "
                        . "Mohon dibantu pelunasannya ya Kak agar koneksi internet tetap aktif dan lancar tanpa kendala. Terima kasih banyak 🙏";
                } else {
                    $waMsg = "Halo Kak {$cust->name}, saya {$user->name} dari tim layanan internet WiFi. "
                        . "Sekadar mengingatkan untuk tagihan internet periode {$periodLabel} sebesar Rp " . number_format($totalUnpaid, 0, ',', '.') . ", batas jatuh tempo pembayarannya adalah tanggal {$dueDateLabel}. "
                        . "Mohon dapat dibantu penyelesaiannya ya Kak agar koneksi internet tetap aktif dan lancar. Terima kasih banyak 🙏";
                }
                $waUrl = "https://wa.me/{$custPhone}?text=" . urlencode($waMsg);
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
                'registration_date' => $cust->registration_date ? \Carbon\Carbon::parse($cust->registration_date)->format('d M Y') : '-',
                'activation_date' => $cust->activation_date ? \Carbon\Carbon::parse($cust->activation_date)->format('d M Y') : '-',
                'total_commission' => (float) $totalCommissions,
                'has_unpaid_invoice' => $hasUnpaid,
                'has_overdue_invoice' => $hasOverdue,
                'is_not_due_yet' => $isNotDueYet,
                'is_prorata' => $isProrata,
                'is_issued' => $isIssued,
                'is_before_issue_date' => $isBeforeIssueDate,
                'can_bantu_tagih' => $hasUnpaid && !empty($custPhone),
                'unpaid_invoices_count' => $custUnpaidInvoices->count(),
                'unpaid_amount' => $totalUnpaid,
                'period_label' => $periodLabel,
                'unpaid_period' => $periodLabel,
                'issued_date' => $issuedDateLabel,
                'unpaid_due_date' => $dueDateLabel,
                'due_date' => $dueDateLabel,
                'wa_reminder_url' => $waUrl,
            ];
        });

        // Downlines Level 1 (Diajak Langsung)
        $downlinesL1 = AffiliateProfile::where('upline_id', $user->id)
            ->where('user_id', '!=', $user->id)
            ->whereHas('user')
            ->with('user')
            ->withCount([
                'eligibleCustomers as eligible_customers_count',
                'customers',
                'customers as active_customers_count' => $activeEligibleCustomerFilter,
                'customers as all_active_customers_count' => function ($q) {
                    $q->where('status', 'active');
                }
            ])
            ->get()
            ->map(function ($dl) use ($upline1CommissionRate) {
                $custCount = (int) ($dl->eligible_customers_count ?? $dl->eligibleCustomers()->count());
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
                    'eligibleCustomers as eligible_customers_count',
                    'customers',
                    'customers as active_customers_count' => $activeEligibleCustomerFilter,
                    'customers as all_active_customers_count' => function ($q) {
                        $q->where('status', 'active');
                    }
                ])
                ->get()
                ->map(function ($dl) use ($upline2CommissionRate) {
                    $custCount = (int) ($dl->eligible_customers_count ?? $dl->eligibleCustomers()->count());
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

        // Pelanggan yang tagihannya sudah benar-benar melewati batas jatuh tempo (Overdue)
        $overdueCustomers = AffiliateService::getUnpaidCustomersForAffiliate($user, true);
        $allUnpaidCustomers = AffiliateService::getUnpaidCustomersForAffiliate($user, false);
        $issueDay = (int) ($settings['invoice_issue_date'] ?? 1);
        $isolateDay = (int) ($settings['isolate_day'] ?? 12);
        $payoutDay = (int) ($settings['affiliate_payout_day'] ?? 15);
        $currentDay = (int) now()->format('j');
        $isPayoutOpen = $currentDay >= $payoutDay;
        $nextPayoutDate = $currentDay < $payoutDay
            ? now()->day($payoutDay)->format('d M Y')
            : now()->addMonth()->day($payoutDay)->format('d M Y');

        $prorataCount = $customers->filter(fn($c) => !empty($c['is_prorata']))->count();
        $prorataRunningCount = $customers->filter(fn($c) => !empty($c['is_prorata']) && !empty($c['is_not_due_yet']))->count();
        $allUnpaidCount = $customers->filter(fn($c) => !empty($c['has_unpaid_invoice']))->count();

        $payoutInfo = [
            'payout_day' => $payoutDay,
            'invoice_issue_day' => $issueDay,
            'isolate_day' => $isolateDay,
            'is_payout_open' => $isPayoutOpen,
            'current_day' => $currentDay,
            'next_payout_date' => $nextPayoutDate,
            'has_unpaid_customers' => $overdueCustomers->isNotEmpty(), // HANYA tagihan lewat jatuh tempo yang menahan pencairan dana
            'has_overdue_customers' => $overdueCustomers->isNotEmpty(),
            'unpaid_customers_count' => $allUnpaidCount,
            'overdue_customers_count' => $overdueCustomers->count(),
            'total_unpaid_amount' => (float) $allUnpaidCustomers->sum('total_unpaid_amount'),
            'unpaid_customers' => $allUnpaidCustomers,
            'overdue_customers' => $overdueCustomers,
            'prorata_customers_count' => $prorataCount,
            'prorata_running_count' => $prorataRunningCount,
            'can_withdraw' => !$isEmployee && $isPayoutOpen && $overdueCustomers->isEmpty() && ((float) $profile->balance >= (float) $settings['affiliate_min_withdraw']),
        ];

        // ── Data Pelanggan Berhenti < 4 Bulan (Clawback) ──
        $clawbackCommissions = AffiliateCommission::where('affiliate_id', $user->id)
            ->whereIn('type', ['clawback_booking', 'clawback_survey'])
            ->with(['customer.package'])
            ->latest('id')
            ->get();

        $stoppedCustomers = Customer::where('sales_id', $user->id)
            ->whereIn('status', ['terminated', 'canceled'])
            ->with(['package', 'areaModel'])
            ->latest('cancel_date')
            ->get();

        $minContractMonths = (int) ($settings['affiliate_contract_min_months'] ?? 4);
        $clawbackList = collect();
        $commByCust = $clawbackCommissions->groupBy('customer_id');

        foreach ($stoppedCustomers as $cust) {
            $startDate = $cust->activation_date 
                ? \Carbon\Carbon::parse($cust->activation_date) 
                : ($cust->registration_date ? \Carbon\Carbon::parse($cust->registration_date) : ($cust->created_at ? \Carbon\Carbon::parse($cust->created_at) : null));
            $endDate = $cust->cancel_date ? \Carbon\Carbon::parse($cust->cancel_date) : $cust->updated_at;
            
            $diffInMonths = $startDate ? (int) $startDate->diffInMonths($endDate) : 0;
            $diffInDays = $startDate ? (int) $startDate->diffInDays($endDate) : 0;
            $remainingDays = $diffInDays % 30;
            $durationStr = $diffInMonths > 0 
                ? "{$diffInMonths} bulan {$remainingDays} hari" 
                : "{$diffInDays} hari";

            if ($diffInMonths < $minContractMonths) {
                $relatedComms = $commByCust->get($cust->id, collect());
                $bookingClawback = $relatedComms->firstWhere('type', 'clawback_booking');
                $surveyClawback = $relatedComms->firstWhere('type', 'clawback_survey');

                $clawbackAmount = 0;
                if ($bookingClawback) {
                    $clawbackAmount += abs((float) $bookingClawback->amount);
                } else {
                    $clawbackAmount += (float) ($settings['affiliate_commission_booking'] ?? 35000);
                }
                if ($surveyClawback) {
                    $clawbackAmount += abs((float) $surveyClawback->amount);
                }

                $clawbackList->push([
                    'id' => 'cust_' . $cust->id,
                    'customer_id' => $cust->id,
                    'customer_code' => $cust->customer_code,
                    'name' => $cust->name,
                    'phone' => $cust->phone,
                    'package_name' => $cust->package?->name ?? 'Internet Rumah',
                    'activation_date' => $cust->activation_date ? $cust->activation_date->format('d M Y') : ($cust->registration_date ? $cust->registration_date->format('d M Y') : '-'),
                    'cancel_date' => $cust->cancel_date ? $cust->cancel_date->format('d M Y') : ($cust->updated_at ? $cust->updated_at->format('d M Y') : '-'),
                    'active_duration' => $durationStr,
                    'diff_months' => $diffInMonths,
                    'cancel_reason' => $cust->cancel_reason ?: 'Berhenti berlangganan sebelum masa aktif 4 bulan',
                    'clawback_amount' => $clawbackAmount,
                    'is_employee' => $isEmployee,
                    'channel' => $isEmployee ? 'Slip Gaji (Potongan Payroll)' : 'Saldo Dompet Afiliasi',
                    'status' => 'Ditarik Kembali',
                    'has_commission_record' => $relatedComms->isNotEmpty(),
                    'notes' => "Pelanggan berhenti pada masa aktif {$durationStr} (Kurang dari batas minimal {$minContractMonths} bulan)",
                ]);
            }
        }

        foreach ($clawbackCommissions as $comm) {
            if (!$clawbackList->contains('customer_id', $comm->customer_id)) {
                $cust = $comm->customer;
                $clawbackList->push([
                    'id' => 'comm_' . $comm->id,
                    'customer_id' => $comm->customer_id,
                    'customer_code' => $cust?->customer_code ?? '-',
                    'name' => $cust?->name ?? ($comm->description ?: 'Pelanggan'),
                    'phone' => $cust?->phone ?? '-',
                    'package_name' => $cust?->package?->name ?? '-',
                    'activation_date' => $cust?->activation_date ? $cust->activation_date->format('d M Y') : '-',
                    'cancel_date' => $cust?->cancel_date ? $cust->cancel_date->format('d M Y') : $comm->created_at->format('d M Y'),
                    'active_duration' => '< 4 bulan',
                    'diff_months' => 0,
                    'cancel_reason' => $comm->description,
                    'clawback_amount' => abs((float) $comm->amount),
                    'is_employee' => $isEmployee,
                    'channel' => $comm->status === 'payroll' ? 'Slip Gaji (Potongan Payroll)' : 'Saldo Dompet Afiliasi',
                    'status' => 'Ditarik Kembali',
                    'has_commission_record' => true,
                    'notes' => $comm->description,
                ]);
            }
        }

        $appUrl = config('app.url', url('/'));

        return Inertia::render('Affiliate/Dashboard', [
            'tab' => in_array($request->query('tab'), ['overview', 'customers', 'downlines', 'clawback', 'commissions', 'withdrawals', 'bank', 'guide'], true)
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
            'clawbacks' => $clawbackList->values(),
            'total_clawback_amount' => (float) $clawbackList->sum('clawback_amount'),
            'total_clawback_count' => $clawbackList->count(),
            'settings' => $settings,
            'area_coverage' => \App\Models\Area::orderBy('name')->get(['id', 'name', 'description', 'address', 'coverage_villages'])->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'description' => $a->description,
                'address' => $a->address,
                'coverage_villages' => $a->coverage_villages,
                'villages' => $a->coverage_villages_list,
            ]),
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
