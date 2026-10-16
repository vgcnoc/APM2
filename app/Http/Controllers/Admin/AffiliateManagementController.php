<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawal;
use App\Models\Customer;
use App\Models\User;
use App\Services\AffiliateService;
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
        $allProfiles = AffiliateProfile::with(['user:id,name,email,phone,role,is_active', 'customers:id,sales_id,name,status'])->get();
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

        $kpi = [
            'total_affiliates' => AffiliateProfile::count(),
            'total_customers' => Customer::whereNotNull('sales_id')->count(),
            'total_commissions_paid' => (float) AffiliateCommission::where('status', 'paid')->sum('amount'),
            'pending_withdrawals_count' => AffiliateWithdrawal::where('status', 'pending')->count(),
            'pending_withdrawals_amount' => (float) AffiliateWithdrawal::where('status', 'pending')->sum('amount'),
        ];

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

        return Inertia::render('Marketing/Affiliates/Index', [
            'tab' => $tab,
            'settings' => $settings,
            'affiliates' => $affiliates,
            'available_uplines' => $availableUplines,
            'candidate_users' => $candidateUsers,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'kpi' => $kpi,
            'tree_data' => $networkTrees,
            'tree_kpi' => $treeKpi,
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
     * Daftarkan agen afiliasi / downline baru secara manual dari Admin.
     */
    public function storeAffiliate(Request $request)
    {
        $mode = $request->input('mode', 'new_user'); // 'new_user' or 'existing_user'

        if ($mode === 'existing_user') {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'upline_id' => 'nullable|exists:users,id|different:user_id',
                'whatsapp' => 'nullable|string|max:25',
                'bank_name' => 'nullable|string|max:50',
                'bank_account_number' => 'nullable|string|max:50',
                'bank_account_holder' => 'nullable|string|max:100',
            ], [
                'user_id.required' => 'Pilih pengguna/karyawan yang ingin dijadikan agen.',
                'upline_id.different' => 'Agen tidak boleh menjadi upline bagi dirinya sendiri.',
            ]);

            $targetUser = User::findOrFail($validated['user_id']);
            if (AffiliateProfile::where('user_id', $targetUser->id)->exists()) {
                return back()->with('error', "Pengguna {$targetUser->name} sudah terdaftar sebagai agen afiliasi.");
            }

            AffiliateService::ensureAffiliateProfile(
                $targetUser,
                !empty($validated['upline_id']) ? (int) $validated['upline_id'] : null,
                [
                    'whatsapp' => $validated['whatsapp'] ?? $targetUser->phone,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'bank_account_number' => $validated['bank_account_number'] ?? null,
                    'bank_account_holder' => $validated['bank_account_holder'] ?? $targetUser->name,
                ]
            );

            return back()->with('success', "Pengguna {$targetUser->name} berhasil didaftarkan sebagai Agen Afiliasi!");
        }

        // Mode: Akun Baru (New User)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:25',
            'password' => 'required|string|min:6',
            'upline_id' => 'nullable|exists:users,id',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama lengkap agen wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'password.required' => 'Password wajib diisi (minimal 6 karakter).',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'sales',
                'is_active' => true,
            ]);

            try {
                $role = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
                $user->assignRole($role);
            } catch (\Exception $e) {
                // Ignore fallback
            }

            AffiliateService::ensureAffiliateProfile(
                $user,
                !empty($validated['upline_id']) ? (int) $validated['upline_id'] : null,
                [
                    'whatsapp' => $validated['phone'],
                    'address' => $validated['address'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'bank_account_number' => $validated['bank_account_number'] ?? null,
                    'bank_account_holder' => $validated['bank_account_holder'] ?? $validated['name'],
                ]
            );
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
}
