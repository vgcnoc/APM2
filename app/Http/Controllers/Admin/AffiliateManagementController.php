<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Models\Customer;
use App\Models\User;
use App\Services\AffiliateService;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AffiliateManagementController extends Controller
{
    /**
     * Dashboard Manajemen Afiliasi & Sales Admin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->query('tab');
        if (!$tab) {
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin');
            if ($user && !$isAdmin) {
                if ($user->can('menu_affiliate_settings') || $user->can('affiliate_tab_settings')) {
                    $tab = 'settings';
                } elseif ($user->can('menu_affiliate_diagram') || $user->can('affiliate_tab_diagram')) {
                    $tab = 'diagram';
                } elseif ($user->can('menu_affiliate_agents') || $user->can('affiliate_tab_affiliates')) {
                    $tab = 'affiliates';
                } elseif ($user->can('menu_affiliate_commissions') || $user->can('affiliate_tab_commissions')) {
                    $tab = 'commissions';
                } elseif ($user->can('menu_affiliate_withdrawals') || $user->can('affiliate_tab_withdrawals')) {
                    $tab = 'withdrawals';
                } else {
                    $tab = 'settings';
                }
            } else {
                $tab = 'settings';
            }
        }
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);
        $perPage = in_array($perPage, [15, 30, 50, 100], true) ? $perPage : 15;

        // 1. Settings
        $settings = AffiliateService::getSettings();

        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('marketing_affiliates_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        // 2. Daftar Agen Afiliasi
        $affiliatesQuery = AffiliateProfile::with(['user', 'upline']);
        if (!$isGlobal && !empty($accessibleAreas)) {
            $affiliatesQuery->whereHas('user', function ($uq) use ($accessibleAreas) {
                $uq->whereIn('area_id', $accessibleAreas);
            });
        }
        if ($search) {
            $affiliatesQuery->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        $affiliates = $affiliatesQuery->latest('id')
            ->paginate($perPage, ['*'], 'affiliates_page')
            ->withQueryString()
            ->through(function ($aff) {
                return [
                    'id' => $aff->id,
                    'user_id' => $aff->user_id,
                    'name' => $aff->user?->name ?? 'Unknown',
                    'email' => $aff->user?->email,
                    'whatsapp' => $aff->whatsapp ?? $aff->user?->phone,
                    'referral_code' => $aff->referral_code,
                    'upline_id' => $aff->upline_id,
                    'upline_name' => $aff->upline?->name ?? '-',
                    'balance' => (float) $aff->balance,
                    'total_earned' => (float) $aff->total_earned,
                    'total_withdrawn' => (float) $aff->total_withdrawn,
                    'total_customers' => $aff->customers()->count(),
                    'total_downlines' => $aff->downlinesLevel1()->count(),
                    'bank_info' => $aff->bank_name ? "{$aff->bank_name} - {$aff->bank_account_number} ({$aff->bank_account_holder})" : '-',
                    'bank_name' => $aff->bank_name,
                    'bank_account_number' => $aff->bank_account_number,
                    'bank_account_holder' => $aff->bank_account_holder,
                    'status' => $aff->status,
                    'is_employee' => AffiliateService::isInternalEmployee($aff->user),
                    'joined_at' => $aff->created_at->format('d M Y'),
                ];
            });

        // 3. Riwayat Komisi
        $commissionsQuery = AffiliateCommission::with(['affiliate', 'customer', 'sourceUser']);
        if (!$isGlobal && !empty($accessibleAreas)) {
            $commissionsQuery->whereHas('affiliate.user', function ($uq) use ($accessibleAreas) {
                $uq->whereIn('area_id', $accessibleAreas);
            });
        }
        $commissions = $commissionsQuery->latest('id')
            ->paginate(20, ['*'], 'commissions_page')
            ->withQueryString()
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
        $withdrawalsQuery = AffiliateWithdrawal::with(['affiliate', 'processor']);
        if (!$isGlobal && !empty($accessibleAreas)) {
            $withdrawalsQuery->whereHas('affiliate.user', function ($uq) use ($accessibleAreas) {
                $uq->whereIn('area_id', $accessibleAreas);
            });
        }
        $withdrawals = $withdrawalsQuery->latest('id')
            ->paginate(15, ['*'], 'withdrawals_page')
            ->withQueryString()
            ->through(function ($w) {
                $unpaidCount = 0;
                $unpaidAmount = 0;
                if ($w->affiliate) {
                    $unpaid = AffiliateService::getUnpaidCustomersForAffiliate($w->affiliate);
                    $unpaidCount = $unpaid->count();
                    $unpaidAmount = (float) $unpaid->sum('total_unpaid_amount');
                }

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
                    'unpaid_customers_count' => $unpaidCount,
                    'unpaid_customers_amount' => $unpaidAmount,
                ];
            });

        // 5. Data Struktur Pohon Jaringan Multi-Tier (Network Tree Diagram)
        $allProfilesQuery = AffiliateProfile::with(['user:id,name,email,phone,role,is_active,area_id', 'customers:id,sales_id,name,status,is_migrated']);
        if (!$isGlobal && !empty($accessibleAreas)) {
            $allProfilesQuery->whereHas('user', function ($uq) use ($accessibleAreas) {
                $uq->whereIn('area_id', $accessibleAreas);
            });
        }
        $allProfiles = $allProfilesQuery->get();
        $profilesByUser = $allProfiles->keyBy('user_id');
        $uplineUserIds = $allProfiles->whereNotNull('upline_id')->pluck('upline_id')->unique()->values()->toArray();

        $networkTrees = [];
        $totalDownline1Count = 0;
        $totalDownline2Count = 0;

        foreach ($uplineUserIds as $uId) {
            $uProfile = $profilesByUser->get($uId);
            if (!$uProfile) {
                $uUser = \App\Models\User::find($uId);
                if ($uUser) {
                    $uProfile = AffiliateService::ensureAffiliateProfile($uUser);
                }
            }

            if ($uProfile) {
                $l1Profiles = $allProfiles->where('upline_id', $uId)->values();
                $l1List = [];
                $uplineL2Count = 0;

                foreach ($l1Profiles as $l1) {
                    $l2Profiles = $allProfiles->where('upline_id', $l1->user_id)->values();
                    $l2List = [];
                    foreach ($l2Profiles as $l2) {
                        $uplineL2Count++;
                        $totalDownline2Count++;
                        $l2List[] = [
                            'id' => $l2->id,
                            'user_id' => $l2->user_id,
                            'name' => $l2->user?->name ?? 'Mitra L2',
                            'email' => $l2->user?->email,
                            'phone' => $l2->whatsapp ?? $l2->user?->phone,
                            'referral_code' => $l2->referral_code,
                            'status' => $l2->status,
                            'is_employee' => AffiliateService::isInternalEmployee($l2->user),
                            'total_customers' => $l2->customers->count(),
                            'total_earned' => (float) $l2->total_earned,
                            'balance' => (float) $l2->balance,
                            'downlines_count' => 0,
                            'downlines' => [],
                        ];
                    }

                    $totalDownline1Count++;
                    $l1List[] = [
                        'id' => $l1->id,
                        'user_id' => $l1->user_id,
                        'name' => $l1->user?->name ?? 'Mitra L1',
                        'email' => $l1->user?->email,
                        'phone' => $l1->whatsapp ?? $l1->user?->phone,
                        'referral_code' => $l1->referral_code,
                        'status' => $l1->status,
                        'is_employee' => AffiliateService::isInternalEmployee($l1->user),
                        'total_customers' => $l1->customers->count(),
                        'total_earned' => (float) $l1->total_earned,
                        'balance' => (float) $l1->balance,
                        'downlines_count' => count($l2List),
                        'downlines' => $l2List,
                    ];
                }

                $networkTrees[] = [
                    'id' => $uProfile->id,
                    'user_id' => $uProfile->user_id,
                    'name' => $uProfile->user?->name ?? 'Upline Utama',
                    'email' => $uProfile->user?->email,
                    'phone' => $uProfile->whatsapp ?? $uProfile->user?->phone,
                    'referral_code' => $uProfile->referral_code,
                    'status' => $uProfile->status,
                    'is_employee' => AffiliateService::isInternalEmployee($uProfile->user),
                    'total_customers' => $uProfile->customers->count(),
                    'total_earned' => (float) $uProfile->total_earned,
                    'balance' => (float) $uProfile->balance,
                    'l1_count' => count($l1List),
                    'l2_count' => $uplineL2Count,
                    'total_members' => 1 + count($l1List) + $uplineL2Count,
                    'downlines' => $l1List,
                ];
            }
        }

        // Fallback jika database belum ada downline relasi
        if (empty($networkTrees)) {
            $demoTree = [
                'id' => 1,
                'user_id' => 1,
                'name' => 'Deri Gustinayana',
                'email' => 'deri@isp.local',
                'phone' => '081234567890',
                'referral_code' => 'SL-202301-0001',
                'status' => 'active',
                'is_employee' => true,
                'total_customers' => 45,
                'total_earned' => 1850000,
                'balance' => 0,
                'l1_count' => 10,
                'l2_count' => 1,
                'total_members' => 12,
                'downlines' => [
                    ['id' => 2, 'user_id' => 2, 'name' => 'AGUNG KUSMAWAN', 'referral_code' => 'SL-202301-0045', 'status' => 'active', 'is_employee' => true, 'total_customers' => 12, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 3, 'user_id' => 3, 'name' => 'Ariyani Setyowati', 'referral_code' => 'SL-202301-0004', 'status' => 'active', 'is_employee' => false, 'total_customers' => 8, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 4, 'user_id' => 4, 'name' => 'Bastian Surya Wisesanji', 'referral_code' => 'SL-202301-0017', 'status' => 'active', 'is_employee' => false, 'total_customers' => 15, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 5, 'user_id' => 5, 'name' => 'Damar', 'referral_code' => 'SL-202301-0041', 'status' => 'active', 'is_employee' => true, 'total_customers' => 6, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 6, 'user_id' => 6, 'name' => 'Heri indiansyah', 'referral_code' => 'SL-202301-0012', 'status' => 'active', 'is_employee' => false, 'total_customers' => 19, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 7, 'user_id' => 7, 'name' => 'Hikmah Apriliyanti', 'referral_code' => 'SL-202301-0021', 'status' => 'active', 'is_employee' => false, 'total_customers' => 5, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 8, 'user_id' => 8, 'name' => 'IMALULHAKIM', 'referral_code' => 'SL-202301-0043', 'status' => 'active', 'is_employee' => true, 'total_customers' => 22, 'downlines_count' => 1, 'downlines' => [
                        ['id' => 12, 'user_id' => 12, 'name' => 'SADIN', 'referral_code' => 'SL-202301-0001', 'status' => 'active', 'is_employee' => false, 'total_customers' => 14, 'downlines_count' => 0, 'downlines' => []]
                    ]],
                    ['id' => 9, 'user_id' => 9, 'name' => 'Faisal muharam', 'referral_code' => 'SL-202301-0010', 'status' => 'active', 'is_employee' => false, 'total_customers' => 7, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 10, 'user_id' => 10, 'name' => 'Fatronah', 'referral_code' => 'SL-202301-0011', 'status' => 'active', 'is_employee' => false, 'total_customers' => 9, 'downlines_count' => 0, 'downlines' => []],
                    ['id' => 11, 'user_id' => 11, 'name' => 'SOLEHUDIN', 'referral_code' => 'SL-202301-0036', 'status' => 'active', 'is_employee' => true, 'total_customers' => 11, 'downlines_count' => 0, 'downlines' => []],
                ]
            ];
            $networkTrees[] = $demoTree;
            $totalDownline1Count = 10;
            $totalDownline2Count = 1;
        }

        $treeKpi = [
            'upline_count' => count($networkTrees),
            'downline_1_count' => $totalDownline1Count,
            'downline_2_count' => $totalDownline2Count,
            'total_members' => count($networkTrees) + $totalDownline1Count + $totalDownline2Count,
        ];

        if (!$isGlobal && !empty($accessibleAreas)) {
            $kpi = [
                'total_affiliates' => AffiliateProfile::whereHas('user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas))->count(),
                'total_customers' => Customer::when(\Illuminate\Support\Facades\Schema::hasColumn('customers', 'is_migrated'), fn($q) => $q->where('is_migrated', false))->whereNotNull('sales_id')->whereIn('area_id', $accessibleAreas)->count(),
                'total_commissions_paid' => (float) AffiliateCommission::whereHas('affiliate.user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas))->where('status', 'paid')->sum('amount'),
                'pending_withdrawals_count' => AffiliateWithdrawal::whereHas('affiliate.user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas))->where('status', 'pending')->count(),
                'pending_withdrawals_amount' => (float) AffiliateWithdrawal::whereHas('affiliate.user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas))->where('status', 'pending')->sum('amount'),
            ];
        } else {
            $kpi = [
                'total_affiliates' => AffiliateProfile::count(),
                'total_customers' => Customer::when(\Illuminate\Support\Facades\Schema::hasColumn('customers', 'is_migrated'), fn($q) => $q->where('is_migrated', false))->whereNotNull('sales_id')->count(),
                'total_commissions_paid' => (float) AffiliateCommission::where('status', 'paid')->sum('amount'),
                'pending_withdrawals_count' => AffiliateWithdrawal::where('status', 'pending')->count(),
                'pending_withdrawals_amount' => (float) AffiliateWithdrawal::where('status', 'pending')->sum('amount'),
            ];
        }

        $availableUplines = AffiliateProfile::with('user:id,name,email,role')
            ->where('status', 'active')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'name' => $a->user?->name ?? 'Unknown',
                'email' => $a->user?->email,
                'referral_code' => $a->referral_code,
            ]);

        $candidateUsers = User::whereDoesntHave('affiliateProfile')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role']);

        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Marketing/Affiliates/Index', [
            'tab' => $tab,
            'settings' => $settings,
            'affiliates' => $affiliates,
            'available_uplines' => $availableUplines,
            'candidate_users' => $candidateUsers,
            'areas' => $areas,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'kpi' => $kpi,
            'tree_data' => $networkTrees,
            'tree_kpi' => $treeKpi,
            'filters' => $request->only(['tab', 'search', 'per_page']),
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
            'affiliate_payout_day' => 'nullable|integer|min:1|max:31',
        ], [
            'affiliate_commission_booking.required' => 'Komisi booking wajib diisi.',
            'affiliate_commission_monthly.required' => 'Komisi bulanan direct sales wajib diisi.',
            'affiliate_commission_upline_1.required' => 'Komisi upline downline 1 wajib diisi.',
            'affiliate_commission_upline_2.required' => 'Komisi upline downline 2 wajib diisi.',
            'affiliate_min_withdraw.required' => 'Minimal penarikan dana wajib diisi.',
            'affiliate_payout_day.integer' => 'Tanggal pencairan harus berupa angka 1 s/d 31.',
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

    /**
     * Hapus profil afiliasi (Akun Karyawan atau Mitra Luar) dari sistem & jaringan afiliasi.
     */
    public function destroy(Request $request, $id)
    {
        $affiliate = AffiliateProfile::find($id);
        if (!$affiliate) {
            $affiliate = AffiliateProfile::where('user_id', $id)->first();
        }

        if (!$affiliate) {
            return back()->with('error', 'Data profil afiliasi tidak ditemukan.');
        }

        $user = Auth::user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin');
        if (!$isAdmin && !$user->can('affiliate_btn_delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus akun afiliasi ini.');
        }

        $affiliateUser = $affiliate->user;
        $userName = $affiliateUser?->name ?? 'Member';
        $referralCode = $affiliate->referral_code;
        $isEmployee = AffiliateService::isInternalEmployee($affiliateUser);

        DB::transaction(function () use ($affiliate, $affiliateUser, $isEmployee, $request) {
            // 1. Relinking downline langsung ke upline di atasnya (agar hierarki tidak terputus)
            AffiliateProfile::where('upline_id', $affiliate->user_id)->update([
                'upline_id' => $affiliate->upline_id,
            ]);

            // 2. Putuskan referral_code pada pelanggan terkait
            Customer::where('referral_code', $affiliate->referral_code)->update([
                'referral_code' => null,
            ]);

            // 3. Hapus profil afiliasi
            $affiliate->delete();

            // 4. Jika bukan karyawan, memiliki role 'affiliate' murni, dan tidak memiliki invoice
            if (!$isEmployee && $affiliateUser && $affiliateUser->role === 'affiliate' && $request->boolean('delete_user')) {
                $hasInvoices = DB::table('invoices')->where('user_id', $affiliateUser->id)->exists();
                $hasPayments = DB::table('payments')->where('user_id', $affiliateUser->id)->exists();
                if (!$hasInvoices && !$hasPayments) {
                    $affiliateUser->delete();
                }
            }
        });

        $msg = $isEmployee
            ? "Akun Karyawan {$userName} ({$referralCode}) berhasil dihapus dari jaringan afiliasi. Akun login dan data kepegawaian tetap aman."
            : "Akun agen afiliasi {$userName} ({$referralCode}) berhasil dihapus.";

        return back()->with('success', $msg);
    }

    /**
     * Daftarkan agen afiliasi / downline baru secara manual dari Admin.
     */
    public function storeAffiliate(Request $request)
    {
        // Sanitize nullable fields
        if (!$request->filled('upline_id')) {
            $request->merge(['upline_id' => null]);
        }
        if (!$request->filled('user_id')) {
            $request->merge(['user_id' => null]);
        }
        if (!$request->filled('area_id')) {
            $request->merge(['area_id' => null]);
        }
        if (!$request->filled('referral_code')) {
            $request->merge(['referral_code' => null]);
        }

        $mode = $request->input('mode', 'new_user'); // 'new_user' or 'existing_user'

        if ($mode === 'existing_user') {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'upline_id' => 'nullable|exists:users,id|different:user_id',
                'referral_code' => 'nullable|string|max:30|unique:affiliate_profiles,referral_code',
                'whatsapp' => 'nullable|string|max:25',
                'bank_name' => 'nullable|string|max:50',
                'bank_account_number' => 'nullable|string|max:50',
                'bank_account_holder' => 'nullable|string|max:100',
            ], [
                'user_id.required' => 'Pilih pengguna/karyawan yang ingin dijadikan agen.',
                'user_id.exists' => 'Pengguna yang dipilih tidak valid.',
                'upline_id.exists' => 'Upline sponsor yang dipilih tidak valid.',
                'upline_id.different' => 'Agen tidak boleh menjadi upline bagi dirinya sendiri.',
                'referral_code.unique' => 'Kode referral tersebut sudah dipakai. Gunakan kode lain.',
            ]);

            $targetUser = User::findOrFail($validated['user_id']);
            if (AffiliateProfile::where('user_id', $targetUser->id)->exists()) {
                return back()->with('error', "Pengguna {$targetUser->name} sudah terdaftar sebagai agen afiliasi.");
            }

            $uplineId = !empty($validated['upline_id']) ? (int) $validated['upline_id'] : null;
            $customRef = !empty($validated['referral_code']) ? strtoupper(trim($validated['referral_code'])) : null;

            AffiliateProfile::create([
                'user_id' => $targetUser->id,
                'referral_code' => $customRef ?: AffiliateService::generateReferralCode($targetUser->name),
                'upline_id' => ($uplineId && $uplineId !== $targetUser->id) ? $uplineId : null,
                'whatsapp' => $validated['whatsapp'] ?? $targetUser->phone,
                'bank_name' => $validated['bank_name'] ?? $targetUser->bank_name,
                'bank_account_number' => $validated['bank_account_number'] ?? $targetUser->bank_account_number,
                'bank_account_holder' => $validated['bank_account_holder'] ?? $targetUser->bank_account_holder ?? $targetUser->name,
                'balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'status' => 'active',
            ]);

            if (!empty($validated['whatsapp']) && empty($targetUser->phone)) {
                $targetUser->update(['phone' => $validated['whatsapp']]);
            }

            return back()->with('success', "Pengguna {$targetUser->name} berhasil didaftarkan sebagai Agen Afiliasi!");
        }

        // Mode: Akun Baru (New User)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:25',
            'password' => 'required|string|min:6',
            'upline_id' => 'nullable|exists:users,id',
            'area_id' => 'nullable|exists:areas,id',
            'referral_code' => 'nullable|string|max:30|unique:affiliate_profiles,referral_code',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama lengkap agen wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'upline_id.exists' => 'Upline sponsor yang dipilih tidak valid.',
            'referral_code.unique' => 'Kode referral tersebut sudah dipakai. Gunakan kode lain.',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => trim($validated['phone']),
                'password' => $validated['password'], // User model auto-hashes through 'password' => 'hashed' cast
                'role' => 'affiliate',
                'is_active' => true,
                'area_id' => !empty($validated['area_id']) ? (int) $validated['area_id'] : null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_holder' => $validated['bank_account_holder'] ?? trim($validated['name']),
            ]);

            try {
                $role = Role::firstOrCreate(['name' => 'affiliate', 'guard_name' => 'web']);
                $user->assignRole($role);
            } catch (\Throwable $e) {
                // Ignore fallback
            }

            $uplineId = !empty($validated['upline_id']) ? (int) $validated['upline_id'] : null;
            $customRef = !empty($validated['referral_code']) ? strtoupper(trim($validated['referral_code'])) : null;

            AffiliateProfile::create([
                'user_id' => $user->id,
                'referral_code' => $customRef ?: AffiliateService::generateReferralCode($user->name),
                'upline_id' => ($uplineId && $uplineId !== $user->id) ? $uplineId : null,
                'whatsapp' => trim($validated['phone']),
                'address' => $validated['address'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_holder' => $validated['bank_account_holder'] ?? trim($validated['name']),
                'balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'status' => 'active',
            ]);
        });

        return back()->with('success', "Agen Afiliasi {$validated['name']} berhasil didaftarkan secara manual!");
    }

    /**
     * Update Upline Sponsor untuk agen tertentu.
     */
    public function updateUpline(Request $request, AffiliateProfile $affiliate)
    {
        $validated = $request->validate([
            'upline_id' => 'nullable|exists:users,id',
        ]);

        $newUplineId = !empty($validated['upline_id']) ? (int) $validated['upline_id'] : null;

        // Cegah circular / self-referral
        if ($newUplineId) {
            if ($newUplineId === $affiliate->user_id) {
                return back()->with('error', 'Agen tidak dapat menjadi Upline bagi dirinya sendiri.');
            }

            // Cek apakah calon upline adalah downline dari agen ini (mencegah loop)
            $isDownline = AffiliateProfile::where('upline_id', $affiliate->user_id)
                ->where('user_id', $newUplineId)
                ->exists();

            if ($isDownline) {
                return back()->with('error', 'Tidak dapat memilih agen yang merupakan downline langsung dari agen ini sebagai upline (akan menyebabkan loop).');
            }
        }

        $affiliate->update([
            'upline_id' => $newUplineId,
        ]);

        $uplineName = $affiliate->upline?->name ?? 'Tanpa Upline (Upline Utama)';
        return back()->with('success', "Upline sponsor untuk {$affiliate->user?->name} berhasil diatur ke: {$uplineName}.");
    }

    /**
     * Helper response untuk file Excel (.xlsx) atau CSV
     */
    protected function exportResponse(string $baseFilename, array $headers, array $dataRows, string $format = 'xlsx')
    {
        if ($format === 'xlsx' && class_exists('ZipArchive')) {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
            $tempPath = $tempDir . '/' . $baseFilename . '_' . uniqid() . '.xlsx';
            if (SimpleXlsx::create($headers, $dataRows, $tempPath)) {
                return response()->download($tempPath, $baseFilename . '.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }
        }

        // CSV fallback
        return response()->streamDownload(function () use ($headers, $dataRows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($dataRows as $r) {
                fputcsv($handle, $r);
            }
            fclose($handle);
        }, $baseFilename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export data Marketing & Afiliasi ke format Excel (.xlsx) atau CSV.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && !$user->can('affiliate_btn_export'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengekspor data afiliasi.');
        }

        $type = $request->query('type', 'affiliates'); // 'affiliates' | 'commissions' | 'withdrawals'
        $format = $request->query('format', 'xlsx'); // 'xlsx' | 'csv'
        $isGlobal = $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->can('marketing_affiliates_view_all');
        $accessibleAreas = $isGlobal ? [] : $user->getAccessibleAreaIds();

        $timestamp = date('Y-m-d_His');

        if ($type === 'commissions') {
            $headers = [
                'ID Transaksi',
                'Tanggal Transaksi',
                'Penerima Komisi',
                'Email Penerima',
                'WhatsApp / HP',
                'Tipe Komisi',
                'Tingkat Penerima',
                'Sumber Pelanggan',
                'Deskripsi Transaksi',
                'Nominal Komisi (Rp)',
                'Status',
            ];

            $query = AffiliateCommission::with(['affiliate.user', 'customer', 'sourceUser']);
            if (!$isGlobal && !empty($accessibleAreas)) {
                $query->whereHas('affiliate.user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas));
            }

            $dataRows = [];
            foreach ($query->latest('id')->get() as $c) {
                $dataRows[] = [
                    $c->id,
                    $c->created_at ? $c->created_at->format('Y-m-d H:i:s') : '-',
                    $c->affiliate?->user?->name ?? 'Unknown',
                    $c->affiliate?->user?->email ?? '-',
                    $c->affiliate?->whatsapp ?? $c->affiliate?->user?->phone ?? '-',
                    $c->commission_type ?? 'referral',
                    $c->tier_level ? 'Level ' . $c->tier_level : 'Direct',
                    $c->customer?->name ?? '-',
                    $c->description ?? '-',
                    (float) $c->amount,
                    $c->status ?? 'paid',
                ];
            }

            return $this->exportResponse("riwayat_komisi_afiliasi_{$timestamp}", $headers, $dataRows, $format);
        }

        if ($type === 'withdrawals') {
            $headers = [
                'ID',
                'No. Referensi',
                'Tanggal Pengajuan',
                'Nama Mitra',
                'Email Mitra',
                'WhatsApp Mitra',
                'Nama Bank / E-Wallet',
                'Nomor Rekening',
                'Pemilik Rekening',
                'Nominal Penarikan (Rp)',
                'Status',
                'Catatan Pemohon',
                'Diproses Oleh',
                'Tanggal Diproses',
            ];

            $query = AffiliateWithdrawal::with(['affiliate.user', 'processor']);
            if (!$isGlobal && !empty($accessibleAreas)) {
                $query->whereHas('affiliate.user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas));
            }

            $dataRows = [];
            foreach ($query->latest('id')->get() as $w) {
                $dataRows[] = [
                    $w->id,
                    $w->reference_no ?? '-',
                    $w->created_at ? $w->created_at->format('Y-m-d H:i:s') : '-',
                    $w->affiliate?->user?->name ?? '-',
                    $w->affiliate?->user?->email ?? '-',
                    $w->affiliate?->whatsapp ?? $w->affiliate?->user?->phone ?? '-',
                    $w->bank_name ?? '-',
                    $w->bank_account_number ?? '-',
                    $w->bank_account_holder ?? '-',
                    (float) $w->amount,
                    strtoupper($w->status ?? 'pending'),
                    $w->notes ?? '-',
                    $w->processor?->name ?? '-',
                    $w->processed_at ? \Carbon\Carbon::parse($w->processed_at)->format('Y-m-d H:i:s') : '-',
                ];
            }

            return $this->exportResponse("pencairan_komisi_afiliasi_{$timestamp}", $headers, $dataRows, $format);
        }

        // Default: Daftar Agen Afiliasi
        $headers = [
            'ID Agen',
            'Nama Agen',
            'Email',
            'Nomor WhatsApp',
            'Kode Referral',
            'Nama Upline Sponsor',
            'Kode Referral Upline',
            'Total Pelanggan Referral',
            'Total Downline Langsung',
            'Saldo Aktif (Rp)',
            'Total Komisi Diterima (Rp)',
            'Total Dana Ditarik (Rp)',
            'Nama Bank / E-Wallet',
            'Nomor Rekening',
            'Nama Pemilik Rekening',
            'Status',
            'Tipe Akun',
            'Tanggal Bergabung',
        ];

        $query = AffiliateProfile::with(['user', 'upline.affiliateProfile'])->withCount(['customers', 'downlinesLevel1']);
        if (!$isGlobal && !empty($accessibleAreas)) {
            $query->whereHas('user', fn($uq) => $uq->whereIn('area_id', $accessibleAreas));
        }

        $dataRows = [];
        foreach ($query->latest('id')->get() as $a) {
            $dataRows[] = [
                $a->id,
                $a->user?->name ?? 'Unknown',
                $a->user?->email ?? '-',
                $a->whatsapp ?? $a->user?->phone ?? '-',
                $a->referral_code ?? '-',
                $a->upline?->name ?? 'Tanpa Upline',
                $a->upline?->affiliateProfile?->referral_code ?? '-',
                (int) $a->customers_count,
                (int) $a->downlines_level1_count,
                (float) $a->balance,
                (float) $a->total_earned,
                (float) $a->total_withdrawn,
                $a->bank_name ?? '-',
                $a->bank_account_number ?? '-',
                $a->bank_account_holder ?? '-',
                strtoupper($a->status ?? 'active'),
                AffiliateService::isInternalEmployee($a->user) ? 'Internal / Karyawan' : 'Eksternal / Mitra',
                $a->created_at ? $a->created_at->format('Y-m-d H:i:s') : '-',
            ];
        }

        return $this->exportResponse("daftar_agen_afiliasi_{$timestamp}", $headers, $dataRows, $format);
    }

    /**
     * Unduh Template Excel (.xlsx) untuk Import Data Agen Afiliasi
     */
    public function downloadTemplate(Request $request)
    {
        $headers = [
            'Nama Lengkap',
            'Email',
            'Nomor WhatsApp',
            'Password',
            'Kode Referral',
            'Upline (Email / Kode Referral)',
            'Nama Bank / E-Wallet',
            'Nomor Rekening',
            'Nama Pemilik Rekening',
            'Alamat',
        ];

        $sampleRows = [
            [
                'Budi Santoso',
                'budi.santoso@contoh.com',
                '081234567890',
                'password123',
                'SL-BUDI-01',
                '',
                'BCA',
                '1234567890',
                'Budi Santoso',
                'Jl. Mawar No. 12, Jakarta',
            ],
            [
                'Siti Rahmawati',
                'siti.rahma@contoh.com',
                '082198765432',
                'password123',
                'SL-SITI-02',
                'SL-BUDI-01',
                'Mandiri',
                '9876543210123',
                'Siti Rahmawati',
                'Jl. Melati No. 5, Bandung',
            ],
        ];

        $format = $request->query('format', 'xlsx');
        if ($format === 'xlsx' && class_exists('ZipArchive')) {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
            $tempPath = $tempDir . '/template_import_agen_afiliasi_' . uniqid() . '.xlsx';
            if (SimpleXlsx::create($headers, $sampleRows, $tempPath)) {
                return response()->download($tempPath, 'template_import_agen_afiliasi.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }
        }

        $filename = "template_import_agen_afiliasi.csv";
        return response()->streamDownload(function () use ($headers, $sampleRows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($sampleRows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import Data Agen Afiliasi dari file Excel (.xlsx / .xls / .csv)
     */
    public function import(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && !$user->can('affiliate_btn_import'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengimpor data agen afiliasi.');
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ], [
            'file.required' => 'Pilih file Excel (.xlsx / .xls / .csv) yang ingin diimpor.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('file');
        $rawRows = SimpleXlsx::read($file->getRealPath());

        if (empty($rawRows)) {
            return back()->with('error', 'File Excel kosong atau format tidak dapat dibaca.');
        }

        $header = array_shift($rawRows);
        if (!$header || empty(array_filter($header))) {
            return back()->with('error', 'File Excel tidak memiliki baris header yang valid.');
        }

        // Map normalized headers
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $clean = strtolower(trim((string)$col, "\xEF\xBB\xBF \t\n\r\0\x0B"));
            $clean = str_replace(['_', '-'], ' ', $clean);
            $headerMap[$clean] = $idx;
        }

        $getCol = function($row, ...$keys) use ($headerMap) {
            foreach ($keys as $k) {
                $normK = str_replace(['_', '-'], ' ', strtolower($k));
                if (isset($headerMap[$normK]) && isset($row[$headerMap[$normK]])) {
                    $val = trim((string)$row[$headerMap[$normK]]);
                    if ($val !== '') return $val;
                }
            }
            return null;
        };

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $rowNum = 1;

        $affiliateRole = Role::firstOrCreate(['name' => 'affiliate', 'guard_name' => 'web']);

        DB::beginTransaction();
        try {
            foreach ($rawRows as $row) {
                $rowNum++;
                if (empty(array_filter($row, fn($x) => trim((string)$x) !== ''))) continue;

                $name = $getCol($row, 'nama lengkap', 'nama', 'name', 'nama agen');
                $email = $getCol($row, 'email', 'e-mail', 'alamat email');
                $phone = $getCol($row, 'nomor whatsapp', 'whatsapp', 'no whatsapp', 'no hp', 'phone', 'telp');
                $password = $getCol($row, 'password', 'kata sandi') ?: '12345678';
                $customRef = $getCol($row, 'kode referral', 'referral', 'kode ref', 'ref code');
                $uplineQuery = $getCol($row, 'upline (email / kode referral)', 'upline', 'sponsor', 'kode upline', 'email upline');
                $bankName = $getCol($row, 'nama bank / e-wallet', 'bank', 'nama bank', 'e-wallet');
                $bankAccNum = $getCol($row, 'nomor rekening', 'no rekening', 'rekening', 'no rek');
                $bankAccHolder = $getCol($row, 'nama pemilik rekening', 'pemilik rekening', 'atas nama', 'holder');
                $address = $getCol($row, 'alamat', 'address', 'domisili');

                if (!$name || !$email) {
                    $skippedCount++;
                    continue;
                }

                // Resolve Upline ID if provided
                $uplineId = null;
                if ($uplineQuery) {
                    $uplineProfile = AffiliateProfile::where('referral_code', strtoupper($uplineQuery))->first();
                    if ($uplineProfile) {
                        $uplineId = $uplineProfile->user_id;
                    } else {
                        $uplineUser = User::where('email', $uplineQuery)->orWhere('name', $uplineQuery)->first();
                        if ($uplineUser) {
                            $uplineId = $uplineUser->id;
                        }
                    }
                }

                // Check existing user
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    // Update user phone if empty
                    if ($phone && empty($existingUser->phone)) {
                        $existingUser->update(['phone' => $phone]);
                    }

                    // Check if already affiliate
                    $profile = AffiliateProfile::where('user_id', $existingUser->id)->first();
                    if ($profile) {
                        $updateData = [];
                        if ($phone && empty($profile->whatsapp)) $updateData['whatsapp'] = $phone;
                        if ($bankName) $updateData['bank_name'] = $bankName;
                        if ($bankAccNum) $updateData['bank_account_number'] = $bankAccNum;
                        if ($bankAccHolder) $updateData['bank_account_holder'] = $bankAccHolder;
                        if ($address && empty($profile->address)) $updateData['address'] = $address;
                        if ($uplineId && empty($profile->upline_id) && $uplineId !== $existingUser->id) {
                            $updateData['upline_id'] = $uplineId;
                        }

                        if (!empty($updateData)) {
                            $profile->update($updateData);
                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }
                    } else {
                        // Create profile for existing user
                        $refCode = $customRef ? strtoupper($customRef) : AffiliateService::generateReferralCode($existingUser->name);
                        // Ensure unique referral code
                        if (AffiliateProfile::where('referral_code', $refCode)->exists()) {
                            $refCode = AffiliateService::generateReferralCode($existingUser->name);
                        }

                        AffiliateProfile::create([
                            'user_id' => $existingUser->id,
                            'referral_code' => $refCode,
                            'upline_id' => ($uplineId && $uplineId !== $existingUser->id) ? $uplineId : null,
                            'whatsapp' => $phone ?: $existingUser->phone,
                            'address' => $address,
                            'bank_name' => $bankName ?: $existingUser->bank_name,
                            'bank_account_number' => $bankAccNum ?: $existingUser->bank_account_number,
                            'bank_account_holder' => $bankAccHolder ?: $existingUser->bank_account_holder ?? $existingUser->name,
                            'balance' => 0,
                            'total_earned' => 0,
                            'total_withdrawn' => 0,
                            'status' => 'active',
                        ]);
                        $createdCount++;
                    }
                } else {
                    // Create New User + Affiliate Profile
                    $newUser = User::create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => Hash::make($password),
                        'role' => 'affiliate',
                        'is_active' => true,
                    ]);

                    $newUser->assignRole($affiliateRole);

                    $refCode = $customRef ? strtoupper($customRef) : AffiliateService::generateReferralCode($name);
                    if (AffiliateProfile::where('referral_code', $refCode)->exists()) {
                        $refCode = AffiliateService::generateReferralCode($name);
                    }

                    AffiliateProfile::create([
                        'user_id' => $newUser->id,
                        'referral_code' => $refCode,
                        'upline_id' => ($uplineId && $uplineId !== $newUser->id) ? $uplineId : null,
                        'whatsapp' => $phone,
                        'address' => $address,
                        'bank_name' => $bankName,
                        'bank_account_number' => $bankAccNum,
                        'bank_account_holder' => $bankAccHolder ?: $name,
                        'balance' => 0,
                        'total_earned' => 0,
                        'total_withdrawn' => 0,
                        'status' => 'active',
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            $msg = "Import selesai! {$createdCount} agen baru berhasil ditambahkan";
            if ($updatedCount > 0) $msg .= ", {$updatedCount} data mitra diperbarui";
            if ($skippedCount > 0) $msg .= ", {$skippedCount} baris dilewati";
            $msg .= ".";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat mengimpor data baris ke-' . $rowNum . ': ' . $e->getMessage());
        }
    }
}
