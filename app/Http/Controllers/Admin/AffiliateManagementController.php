<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Models\Customer;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AffiliateManagementController extends Controller
{
    /**
     * Dashboard Manajemen Afiliasi & Sales Admin.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'settings');
        $search = $request->query('search', '');

        // 1. Settings
        $settings = AffiliateService::getSettings();

        // 2. Daftar Agen Afiliasi
        $affiliatesQuery = AffiliateProfile::with(['user', 'upline']);
        if ($search) {
            $affiliatesQuery->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        $affiliates = $affiliatesQuery->latest('id')
            ->paginate(15)
            ->through(function ($aff) {
                return [
                    'id' => $aff->id,
                    'user_id' => $aff->user_id,
                    'name' => $aff->user?->name ?? 'Unknown',
                    'email' => $aff->user?->email,
                    'whatsapp' => $aff->whatsapp ?? $aff->user?->phone,
                    'referral_code' => $aff->referral_code,
                    'upline_name' => $aff->upline?->name ?? '-',
                    'balance' => (float) $aff->balance,
                    'total_earned' => (float) $aff->total_earned,
                    'total_withdrawn' => (float) $aff->total_withdrawn,
                    'total_customers' => $aff->customers()->count(),
                    'total_downlines' => $aff->downlinesLevel1()->count(),
                    'bank_info' => $aff->bank_name ? "{$aff->bank_name} - {$aff->bank_account_number} ({$aff->bank_account_holder})" : '-',
                    'status' => $aff->status,
                    'is_employee' => AffiliateService::isInternalEmployee($aff->user),
                    'joined_at' => $aff->created_at->format('d M Y'),
                ];
            });

        // 3. Riwayat Komisi
        $commissions = AffiliateCommission::with(['affiliate', 'customer', 'sourceUser'])
            ->latest('id')
            ->paginate(20)
            ->through(function ($c) {
                return [
                    'id' => $c->id,
                    'affiliate_name' => $c->affiliate?->name ?? 'Unknown',
                    'customer_name' => $c->customer?->name ?? '-',
                    'source_user_name' => $c->sourceUser?->name ?? '-',
                    'type' => $c->type,
                    'tier_level' => $c->tier_level,
                    'amount' => (float) $c->amount,
                    'description' => $c->description,
                    'status' => $c->status,
                    'date' => $c->created_at->format('d M Y H:i'),
                ];
            });

        // 4. Permintaan Penarikan (Withdrawals)
        $withdrawals = AffiliateWithdrawal::with(['affiliate', 'processor'])
            ->latest('id')
            ->paginate(15)
            ->through(function ($w) {
                return [
                    'id' => $w->id,
                    'reference_no' => $w->reference_no,
                    'affiliate_name' => $w->affiliate?->name ?? 'Unknown',
                    'affiliate_phone' => $w->affiliate?->phone,
                    'amount' => (float) $w->amount,
                    'net_amount' => (float) $w->net_amount,
                    'bank_name' => $w->bank_name,
                    'bank_account_number' => $w->bank_account_number,
                    'bank_account_holder' => $w->bank_account_holder,
                    'status' => $w->status,
                    'receipt_proof' => $w->receipt_proof ? asset('storage/' . $w->receipt_proof) : null,
                    'notes' => $w->notes,
                    'processor_name' => $w->processor?->name,
                    'date' => $w->created_at->format('d M Y H:i'),
                    'processed_at' => $w->processed_at ? $w->processed_at->format('d M Y H:i') : null,
                ];
            });

        // KPI Ringkasan
        $kpi = [
            'total_affiliates' => AffiliateProfile::count(),
            'total_customers' => Customer::whereNotNull('sales_id')->count(),
            'total_commissions_paid' => (float) AffiliateCommission::where('status', 'approved')->sum('amount'),
            'pending_withdrawals_count' => AffiliateWithdrawal::where('status', 'pending')->count(),
            'pending_withdrawals_amount' => (float) AffiliateWithdrawal::where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('Marketing/Affiliates/Index', [
            'tab' => $tab,
            'settings' => $settings,
            'affiliates' => $affiliates,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'kpi' => $kpi,
            'filters' => $request->only(['tab', 'search']),
        ]);
    }

    /**
     * Simpan pengaturan nilai komisi sales & afiliasi bertingkat.
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'affiliate_commission_booking' => 'required|numeric|min:0',
            'affiliate_commission_monthly' => 'required|numeric|min:0',
            'affiliate_commission_upline_1' => 'required|numeric|min:0',
            'affiliate_commission_upline_2' => 'required|numeric|min:0',
            'affiliate_min_withdraw' => 'required|numeric|min:10000',
            'affiliate_contract_min_months' => 'nullable|integer|min:1|max:60',
        ], [
            'affiliate_commission_booking.required' => 'Komisi booking wajib diisi.',
            'affiliate_commission_monthly.required' => 'Komisi bulanan direct sales wajib diisi.',
            'affiliate_commission_upline_1.required' => 'Komisi upline downline 1 wajib diisi.',
            'affiliate_commission_upline_2.required' => 'Komisi upline downline 2 wajib diisi.',
            'affiliate_min_withdraw.required' => 'Minimal penarikan dana wajib diisi.',
        ]);

        AffiliateService::saveSettings($validated);

        return back()->with('success', 'Pengaturan Komisi Sales & Afiliasi berhasil disimpan!');
    }

    /**
     * Setujui pencairan dana komisi.
     */
    public function approveWithdrawal(Request $request, AffiliateWithdrawal $withdrawal)
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|image|max:3072',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('affiliate_receipts', 'public');
        }

        try {
            AffiliateService::approveWithdrawal($withdrawal, Auth::user(), $receiptPath, $validated['reference_no'] ?? null);
            return back()->with('success', "Penarikan dana {$withdrawal->reference_no} sejumlah Rp " . number_format($withdrawal->amount, 0, ',', '.') . " berhasil disetujui!");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak pencairan dana komisi.
     */
    public function rejectWithdrawal(Request $request, AffiliateWithdrawal $withdrawal)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            AffiliateService::rejectWithdrawal($withdrawal, Auth::user(), $validated['reason']);
            return back()->with('success', "Penarikan dana {$withdrawal->reference_no} telah ditolak dan saldo dikembalikan ke mitra.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle status aktif/suspend akun agen.
     */
    public function toggleStatus(AffiliateProfile $affiliate)
    {
        $newStatus = $affiliate->status === 'active' ? 'suspended' : 'active';
        $affiliate->update(['status' => $newStatus]);

        return back()->with('success', "Status akun afiliasi {$affiliate->user?->name} diubah menjadi {$newStatus}.");
    }
}
