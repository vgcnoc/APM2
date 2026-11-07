<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VoucherProfile;
use App\Models\Reseller;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\ResellerTransaction;
use App\Models\Area;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
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

        $query = User::with([
            'voucherProfiles',
            'reseller.customer.areaModel',
            'customer.areaModel',
            'customer.package',
            'roles'
        ]);

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
            'reseller_feature_vouchers_enabled' => (bool) ((int) Setting::get('reseller_feature_vouchers_enabled', '0')),
            'reseller_feature_billing_enabled' => (bool) ((int) Setting::get('reseller_feature_billing_enabled', '1')),
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
            'areas' => Area::orderBy('name')->get(['id', 'name']),
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
            'reseller_feature_vouchers_enabled' => 'nullable|boolean',
            'reseller_feature_billing_enabled' => 'nullable|boolean',
            'reseller_min_deposit' => 'required|numeric|min:0',
            'biller_min_deposit' => 'required|numeric|min:0',
            'reseller_registration_require_approval' => 'required|boolean',
            'reseller_registration_terms' => 'nullable|string|max:5000',
        ]);

        Setting::set('reseller_registration_enabled', $validated['reseller_registration_enabled'] ? '1' : '0');
        Setting::set('biller_registration_enabled', $validated['biller_registration_enabled'] ? '1' : '0');
        Setting::set('reseller_feature_vouchers_enabled', $request->boolean('reseller_feature_vouchers_enabled', false) ? '1' : '0');
        Setting::set('reseller_feature_billing_enabled', $request->boolean('reseller_feature_billing_enabled', true) ? '1' : '0');
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
            'can_sell_vouchers' => 'nullable|boolean',
            'can_pay_bills' => 'nullable|boolean',
            'voucher_profile_ids' => 'nullable|array',
            'voucher_profile_ids.*' => 'exists:voucher_profiles,id'
        ]);

        $reseller = $user->reseller ?: ($user->customer?->reseller);
        if ($reseller) {
            $canSellVouchers = $request->has('can_sell_vouchers') ? $request->boolean('can_sell_vouchers') : $reseller->can_sell_vouchers;
            $canPayBills = $request->has('can_pay_bills') ? $request->boolean('can_pay_bills') : $reseller->can_pay_bills;
            $reseller->update([
                'can_sell_vouchers' => $canSellVouchers,
                'can_pay_bills' => $canPayBills,
            ]);
        }

        $profileIds = $validated['voucher_profile_ids'] ?? [];
        $user->voucherProfiles()->sync($profileIds);

        $msgServices = [];
        if ($reseller) {
            if ($reseller->can_sell_vouchers && $reseller->can_pay_bills) {
                $msgServices[] = "Bisa Jual Voucher & Bayar Tagihan";
            } elseif ($reseller->can_pay_bills) {
                $msgServices[] = "Hanya Pembayaran Tagihan Internet";
            } elseif ($reseller->can_sell_vouchers) {
                $msgServices[] = "Hanya Jual Voucher";
            } else {
                $msgServices[] = "Layanan Ditutup Sementara";
            }
        }
        $serviceText = !empty($msgServices) ? " [" . implode(', ', $msgServices) . "]" : "";

        $msg = empty($profileIds)
            ? "Akses kemitraan {$user->name}{$serviceText} diset ke Semua Profil Voucher (Default)."
            : "Akses kemitraan {$user->name}{$serviceText} berhasil diperbarui dengan " . count($profileIds) . " profil voucher terpilih.";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Buat akun Mitra Reseller baru secara langsung oleh Admin.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6',
            'tier' => 'required|in:regular,master',
            'can_sell_vouchers' => 'nullable|boolean',
            'can_pay_bills' => 'nullable|boolean',
            'balance' => 'nullable|numeric|min:0',
            'area_id' => 'nullable|exists:areas,id',
            'voucher_profile_ids' => 'nullable|array',
            'voucher_profile_ids.*' => 'exists:voucher_profiles,id',
        ]);

        $initialBalance = (float) ($validated['balance'] ?? 0);
        $areaId = $validated['area_id'] ?? null;
        $area = $areaId ? Area::find($areaId) : null;

        DB::transaction(function () use ($validated, $initialBalance, $area, $areaId) {
            // 1. Buat User Login
            $user = User::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'reseller',
                'is_active' => true,
            ]);

            try {
                $role = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
                $user->syncRoles([$role]);
            } catch (\Throwable $e) {}

            // 2. Buat Customer record untuk Reseller (Bebas Tagihan)
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => 'Loket / Konter Mitra',
                'area_id' => $areaId,
                'area' => $area?->name,
                'status' => 'active',
                'service_status' => 'gratis',
                'package_id' => null,
                'base_amount' => 0,
                'is_reseller' => true,
                'registration_date' => now()->toDateString(),
                'activation_date' => now()->toDateString(),
                'billing_type' => Setting::get('billing_type', 'prabayar'),
                'notes' => $validated['tier'] === 'master' ? 'Akun Master Biller dibuat via Pengaturan Reseller' : 'Akun Mitra Reseller dibuat via Pengaturan Reseller',
            ]);

            // 3. Buat Entitas Reseller
            $canSellVouchers = array_key_exists('can_sell_vouchers', $validated)
                ? (bool) $validated['can_sell_vouchers']
                : (bool) ((int) Setting::get('reseller_feature_vouchers_enabled', '0'));
            $canPayBills = array_key_exists('can_pay_bills', $validated)
                ? (bool) $validated['can_pay_bills']
                : (bool) ((int) Setting::get('reseller_feature_billing_enabled', '1'));

            $reseller = Reseller::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'balance' => $initialBalance,
                'is_active' => true,
                'tier' => $validated['tier'],
                'can_sell_vouchers' => $canSellVouchers,
                'can_pay_bills' => $canPayBills,
                'referral_code' => $validated['tier'] === 'master' ? Reseller::generateReferralCode() : null,
                'status' => 'active',
            ]);

            // 4. Catat Transaksi Saldo Awal jika ada
            if ($initialBalance > 0) {
                ResellerTransaction::create([
                    'reseller_id' => $reseller->id,
                    'type' => 'credit',
                    'amount' => $initialBalance,
                    'description' => 'Saldo Modal Awal Registrasi Kemitraan',
                    'reference_id' => 'INIT-' . time(),
                ]);
            }

            // 5. Hubungkan Profil Voucher jika dipilih
            if (!empty($validated['voucher_profile_ids'])) {
                $user->voucherProfiles()->sync($validated['voucher_profile_ids']);
            }

            // 6. Audit Log
            try {
                AuditLog::createLog(
                    'Create Reseller Account',
                    $reseller,
                    null,
                    'active',
                    "Admin membuat akun mitra (" . strtoupper($validated['tier']) . "): {$user->name} ({$user->email})" . ($initialBalance > 0 ? " dengan saldo awal Rp " . number_format($initialBalance, 0, ',', '.') : '')
                );
            } catch (\Throwable $e) {}
        });

        return redirect()->back()->with('success', "Akun mitra Reseller '{$validated['name']}' berhasil dibuat dan langsung aktif.");
    }

    /**
     * Update data profil pengguna dan kemitraan reseller.
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|max:50',
            'is_active' => 'boolean',
            'tier' => 'nullable|in:regular,master',
            'can_sell_vouchers' => 'nullable|boolean',
            'can_pay_bills' => 'nullable|boolean',
            'balance' => 'nullable|numeric|min:0',
            'reseller_status' => 'nullable|in:active,pending,suspended',
        ]);

        DB::transaction(function () use ($user, $validated, $request) {
            $user->name = trim($validated['name']);
            $user->email = strtolower(trim($validated['email']));
            if (array_key_exists('phone', $validated)) {
                $user->phone = $validated['phone'];
            }
            $user->role = strtolower(trim($validated['role']));
            $user->is_active = $request->boolean('is_active', true);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            // Sync Spatie Role
            try {
                Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
                $user->syncRoles([$user->role]);
            } catch (\Throwable $e) {}

            // Handle Reseller Record
            $reseller = $user->reseller ?: ($user->customer?->reseller);

            if ($reseller) {
                $oldBalance = (float) $reseller->balance;
                $newBalance = isset($validated['balance']) ? (float) $validated['balance'] : $oldBalance;
                $diff = $newBalance - $oldBalance;

                $tier = $validated['tier'] ?? $reseller->tier;
                if ($tier === 'master' && empty($reseller->referral_code)) {
                    $reseller->referral_code = Reseller::generateReferralCode();
                }

                $reseller->tier = $tier;
                $reseller->balance = $newBalance;
                $reseller->status = $validated['reseller_status'] ?? ($reseller->status ?: 'active');
                $reseller->is_active = $user->is_active;
                if ($request->has('can_sell_vouchers')) {
                    $reseller->can_sell_vouchers = $request->boolean('can_sell_vouchers');
                }
                if ($request->has('can_pay_bills')) {
                    $reseller->can_pay_bills = $request->boolean('can_pay_bills');
                }
                $reseller->save();

                if (abs($diff) > 0) {
                    ResellerTransaction::create([
                        'reseller_id' => $reseller->id,
                        'type' => $diff > 0 ? 'credit' : 'debit',
                        'amount' => abs($diff),
                        'description' => 'Penyesuaian Saldo via Pengaturan Reseller (' . ($diff > 0 ? '+' : '-') . 'Rp ' . number_format(abs($diff), 0, ',', '.') . ')',
                        'reference_id' => 'ADJ-' . time(),
                    ]);
                }
            } elseif ($user->role === 'reseller' || $request->boolean('create_reseller')) {
                // Buat data kemitraan reseller jika role diset ke reseller
                $tier = $validated['tier'] ?? 'regular';
                $balance = isset($validated['balance']) ? (float) $validated['balance'] : 0;

                $newReseller = Reseller::create([
                    'user_id' => $user->id,
                    'customer_id' => $user->customer?->id,
                    'tier' => $tier,
                    'balance' => $balance,
                    'is_active' => $user->is_active,
                    'status' => $validated['reseller_status'] ?? 'active',
                    'referral_code' => $tier === 'master' ? Reseller::generateReferralCode() : null,
                ]);

                if ($balance > 0) {
                    ResellerTransaction::create([
                        'reseller_id' => $newReseller->id,
                        'type' => 'credit',
                        'amount' => $balance,
                        'description' => 'Saldo Awal Mitra Reseller',
                        'reference_id' => 'INIT-' . time(),
                    ]);
                }
            }

            // Sync Customer name & phone jika akun terhubung ke data pelanggan
            if ($user->customer) {
                $user->customer->name = $user->name;
                $user->customer->email = $user->email;
                if (!empty($user->phone)) {
                    $user->customer->phone = $user->phone;
                }
                $user->customer->save();
            }

            // Audit Log
            try {
                AuditLog::createLog(
                    'Update User / Reseller Account',
                    $user,
                    null,
                    'updated',
                    "Admin memperbarui data pengguna: {$user->name} ({$user->email}, Role: {$user->role})"
                );
            } catch (\Throwable $e) {}
        });

        return redirect()->back()->with('success', "Data pengguna dan kemitraan '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Hapus akun pengguna dan data kemitraan terkait secara bersih.
     */
    public function destroyUser(User $user)
    {
        // 1. Guard akun sendiri
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // 2. Guard akun super admin utama sistem
        if ($user->id === 1 || $user->email === 'admin@isp.local') {
            return redirect()->back()->with('error', 'Akun Super Administrator utama sistem dilindungi dan tidak boleh dihapus.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        DB::transaction(function () use ($user, $userName, $userEmail) {
            // Hapus data reseller jika ada
            if ($user->reseller) {
                $user->reseller->purgeConnectedData();
                $user->reseller->delete();
            }

            if ($user->customer && $user->customer->reseller) {
                $user->customer->reseller->purgeConnectedData();
                $user->customer->reseller->delete();
            }

            // Detach voucher profiles
            $user->voucherProfiles()->detach();

            // Unlink customer agar pelanggan internet tidak terhapus
            if ($user->customer) {
                $user->customer->user_id = null;
                $user->customer->save();
            }

            // Audit log
            try {
                AuditLog::createLog(
                    'Delete User / Reseller Account',
                    null,
                    null,
                    'deleted',
                    "Admin menghapus akun pengguna {$userName} (ID: {$user->id}, Email: {$userEmail})"
                );
            } catch (\Throwable $e) {}

            $user->delete();
        });

        return redirect()->back()->with('success', "Akun '{$userName}' dan seluruh data kemitraan terkait berhasil dihapus bersih.");
    }
}
