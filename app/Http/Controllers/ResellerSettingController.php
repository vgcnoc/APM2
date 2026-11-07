<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VoucherProfile;
use App\Models\Reseller;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\ResellerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResellerSettingController extends Controller
{
    /**
     * Teks default syarat dan ketentuan kemitraan pendaftaran mandiri
     */
    public static function getDefaultTerms(): string
    {
        return "1. Calon mitra wajib mengisi data identitas diri dan lokasi konter/toko/loket usaha secara benar dan valid.\n"
            . "2. Pendaftaran Mitra Reseller (Voucher & Eceran) memiliki ketentuan minimal pengisian saldo modal awal Rp 20.000.\n"
            . "3. Pendaftaran Master Biller (Loket Pembayaran) memiliki ketentuan minimal pengisian saldo modal awal Rp 2.000.000.\n"
            . "4. Setiap pendaftaran mandiri melalui sistem wajib melalui proses verifikasi dan persetujuan (approval) resmi dari Administrator.\n"
            . "5. Akun kemitraan bebas dari iuran bulanan dan saldo modal dapat langsung digunakan melayani transaksi setelah akun disetujui.\n"
            . "6. Mitra wajib menjaga kerahasiaan kredensial akun dan mematuhi etika operasional serta tarif resmi yang berlaku.";
    }

    public function index(Request $request)
    {
        $roleFilter = $request->input('role', 'reseller');
        $search = trim($request->input('search', ''));
        $tab = $request->input('tab', 'settings'); // 'settings', 'pending', 'vouchers'

        $query = User::with(['voucherProfiles', 'reseller.customer', 'customer']);

        // Filter berdasarkan peran pengguna
        if ($roleFilter === 'reseller') {
            $query->where(function ($q) {
                $q->where('role', 'reseller')
                  ->orWhereHas('roles', function ($rq) {
                      $rq->where('name', 'reseller');
                  })
                  ->orWhereHas('reseller')
                  ->orHas('voucherProfiles');
            });
        } elseif ($roleFilter === 'customer') {
            $query->where(function ($q) {
                $q->where('role', 'customer')
                  ->orWhereHas('customer');
            })->where('role', '!=', 'reseller');
        } elseif ($roleFilter === 'staff') {
            $query->whereIn('role', ['admin', 'superadmin', 'cs', 'teknisi', 'noc', 'karyawan', 'finance']);
        }

        // Pencarian nama, email, phone, atau customer code
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $voucherProfiles = VoucherProfile::where('is_customer_free', false)->orderBy('price', 'asc')->get();

        // Hitung statistik untuk header cards
        $totalResellers = Reseller::where('is_active', true)->where('status', 'active')->count();

        $customAccessCount = User::where(function ($q) {
            $q->where('role', 'reseller')
              ->orWhereHas('roles', function ($rq) {
                  $rq->where('name', 'reseller');
              })
              ->orWhereHas('reseller')
              ->orHas('voucherProfiles');
        })->has('voucherProfiles')->count();

        $fullAccessCount = max(0, $totalResellers - $customAccessCount);

        // Ambil data calon mitra yang mendaftar mandiri & berstatus pending approval
        $pendingResellers = Reseller::with(['customer.areaModel', 'user'])
            ->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere('is_active', false);
            })
            ->latest()
            ->get()
            ->map(function ($r) {
                $name = $r->customer?->name ?: $r->user?->name ?: ('Mitra #' . $r->id);
                $phone = $r->customer?->phone ?: $r->user?->phone ?: '';
                $cleanPhone = preg_replace('/[^0-9]/', '', (string)$phone);
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '62' . substr($cleanPhone, 1);
                } elseif (str_starts_with($cleanPhone, '8')) {
                    $cleanPhone = '62' . $cleanPhone;
                }

                $minDeposit = $r->tier === 'master'
                    ? (int) Setting::get('biller_min_deposit', Setting::get('master_reseller_min_deposit', 2000000))
                    : (int) Setting::get('reseller_min_deposit', 20000);

                return [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'customer_id' => $r->customer_id,
                    'tier' => $r->tier,
                    'tier_label' => $r->tier === 'master' ? 'Master Biller' : 'Mitra Reseller',
                    'name' => $name,
                    'email' => $r->customer?->email ?: $r->user?->email ?: '-',
                    'phone' => $phone,
                    'clean_phone' => $cleanPhone,
                    'area' => $r->customer?->area ?: $r->customer?->areaModel?->name ?: '-',
                    'address' => $r->customer?->address ?: '-',
                    'created_at' => $r->created_at ? $r->created_at->format('d M Y H:i') : '-',
                    'status' => $r->status,
                    'is_active' => (bool) $r->is_active,
                    'balance' => (float) $r->balance,
                    'min_deposit' => $minDeposit,
                ];
            });

        $resellerMinDeposit = (int) Setting::get('reseller_min_deposit', 20000);
        $billerMinDeposit = (int) Setting::get('biller_min_deposit', Setting::get('master_reseller_min_deposit', 2000000));

        $settings = [
            'reseller_registration_enabled' => (bool) ((int) Setting::get('reseller_registration_enabled', '1')),
            'biller_registration_enabled' => (bool) ((int) Setting::get('biller_registration_enabled', '1')),
            'reseller_min_deposit' => $resellerMinDeposit,
            'biller_min_deposit' => $billerMinDeposit,
            'reseller_registration_require_approval' => (bool) ((int) Setting::get('reseller_registration_require_approval', '1')),
            'reseller_registration_terms' => Setting::get('reseller_registration_terms', null) ?: self::getDefaultTerms(),
            'company_phone' => Setting::get('company_phone', '088976397034'),
        ];

        return Inertia::render('Settings/Reseller/Index', [
            'users' => $users,
            'voucherProfiles' => $voucherProfiles,
            'pendingResellers' => $pendingResellers,
            'settings' => $settings,
            'stats' => [
                'total_resellers' => $totalResellers,
                'full_access_count' => $fullAccessCount,
                'custom_access_count' => $customAccessCount,
                'total_profiles' => $voucherProfiles->count(),
                'pending_count' => $pendingResellers->count(),
                'reseller_min_deposit' => $resellerMinDeposit,
                'biller_min_deposit' => $billerMinDeposit,
            ],
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'tab' => $tab,
            ]
        ]);
    }

    /**
     * Simpan pengaturan pendaftaran mandiri Mitra Reseller & Biller.
     */
    public function saveRegistrationSettings(Request $request)
    {
        $validated = $request->validate([
            'reseller_registration_enabled' => 'required|boolean',
            'biller_registration_enabled' => 'required|boolean',
            'reseller_min_deposit' => 'required|numeric|min:0',
            'biller_min_deposit' => 'required|numeric|min:0',
            'reseller_registration_require_approval' => 'required|boolean',
            'reseller_registration_terms' => 'nullable|string|max:5000',
        ]);

        Setting::set('reseller_registration_enabled', $validated['reseller_registration_enabled'] ? '1' : '0');
        Setting::set('biller_registration_enabled', $validated['biller_registration_enabled'] ? '1' : '0');
        Setting::set('reseller_min_deposit', (string) ((int) $validated['reseller_min_deposit']));
        Setting::set('biller_min_deposit', (string) ((int) $validated['biller_min_deposit']));
        Setting::set('master_reseller_min_deposit', (string) ((int) $validated['biller_min_deposit'])); // sinkronkan master biller
        Setting::set('reseller_registration_require_approval', $validated['reseller_registration_require_approval'] ? '1' : '0');
        Setting::set('reseller_registration_terms', $validated['reseller_registration_terms'] ?: self::getDefaultTerms());

        // Catat Audit Log
        try {
            AuditLog::createLog(
                'Update Reseller Registration Settings',
                null,
                null,
                'settings',
                "Admin memperbarui konfigurasi pendaftaran mandiri: Reseller (Min: Rp " . number_format($validated['reseller_min_deposit'], 0, ',', '.') . "), Biller (Min: Rp " . number_format($validated['biller_min_deposit'], 0, ',', '.') . "), Approval: " . ($validated['reseller_registration_require_approval'] ? 'Wajib' : 'Otomatis')
            );
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', 'Pengaturan pendaftaran mandiri Reseller & Biller berhasil disimpan dengan sempurna.');
    }

    /**
     * Setujui (Approve) pendaftaran mandiri Mitra Reseller / Biller.
     */
    public function approveReseller(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'initial_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $initialBalance = (float) ($validated['initial_balance'] ?? 0);
        $partnerName = $reseller->customer?->name ?: $reseller->user?->name ?: ('Mitra #' . $reseller->id);

        DB::transaction(function () use ($reseller, $initialBalance, $validated, $partnerName) {
            $reseller->status = 'active';
            $reseller->is_active = true;

            if ($reseller->tier === 'master' && empty($reseller->referral_code)) {
                $reseller->referral_code = Reseller::generateReferralCode();
            }

            if ($initialBalance > 0) {
                $reseller->balance += $initialBalance;
            }
            $reseller->save();

            // Aktifkan User
            if ($reseller->user) {
                $reseller->user->is_active = true;
                $reseller->user->save();
            }

            // Aktifkan Customer record
            if ($reseller->customer) {
                $reseller->customer->status = 'active';
                $reseller->customer->save();
            }

            // Catat transaksi saldo jika ada saldo awal
            if ($initialBalance > 0) {
                ResellerTransaction::create([
                    'reseller_id' => $reseller->id,
                    'type' => 'credit',
                    'amount' => $initialBalance,
                    'description' => 'Saldo Modal Awal Aktivasi Kemitraan' . (!empty($validated['notes']) ? " ({$validated['notes']})" : ''),
                    'reference_id' => 'ACT-' . time(),
                ]);
            }

            // Audit Log
            try {
                AuditLog::createLog(
                    'Approve Reseller Self-Registration',
                    $reseller,
                    null,
                    'active',
                    "Admin menyetujui pendaftaran kemitraan {$reseller->tier}: {$partnerName}" . ($initialBalance > 0 ? " dengan saldo awal Rp " . number_format($initialBalance, 0, ',', '.') : '')
                );
            } catch (\Throwable $e) {}
        });

        return redirect()->back()->with('success', "Pendaftaran {$partnerName} berhasil disetujui! Akun mitra telah diaktifkan.");
    }

    /**
     * Tolak (Reject) permohonan pendaftaran mandiri Mitra.
     */
    public function rejectReseller(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $partnerName = $reseller->customer?->name ?: $reseller->user?->name ?: ('Mitra #' . $reseller->id);
        $reason = $validated['reason'] ?? 'Tidak memenuhi syarat pengisian saldo awal atau verifikasi';

        DB::transaction(function () use ($reseller, $partnerName, $reason) {
            try {
                AuditLog::createLog(
                    'Reject Reseller Self-Registration',
                    $reseller,
                    null,
                    'rejected',
                    "Admin menolak pendaftaran kemitraan {$reseller->tier}: {$partnerName}. Alasan: {$reason}"
                );
            } catch (\Throwable $e) {}

            $user = $reseller->user;
            $customer = $reseller->customer;

            $reseller->purgeConnectedData();
            $reseller->delete();

            if ($customer) {
                $customer->delete();
            }

            if ($user) {
                $user->delete();
            }
        });

        return redirect()->back()->with('success', "Pendaftaran mitra {$partnerName} telah ditolak dan data dibersihkan.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'voucher_profile_ids' => 'nullable|array',
            'voucher_profile_ids.*' => 'exists:voucher_profiles,id'
        ]);

        $profileIds = $validated['voucher_profile_ids'] ?? [];
        $user->voucherProfiles()->sync($profileIds);

        $msg = empty($profileIds)
            ? "Akses profil voucher {$user->name} diset ke Semua Profil (Default)."
            : "Akses profil voucher {$user->name} berhasil dibatasi ke " . count($profileIds) . " profil terpilih.";

        return redirect()->back()->with('success', $msg);
    }
}
