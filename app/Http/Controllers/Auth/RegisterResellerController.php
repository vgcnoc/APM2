<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResellerSettingController;
use App\Models\User;
use App\Models\Customer;
use App\Models\Reseller;
use App\Models\Area;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RegisterResellerController extends Controller
{
    /**
     * Tampilkan halaman pendaftaran mandiri calon Mitra Reseller & Biller.
     */
    public function create(Request $request)
    {
        // Jika sudah login, redirect sesuai role
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'reseller' || (method_exists($user, 'hasRole') && $user->hasRole('reseller'))) {
                return redirect()->route('client-area.dashboard');
            }
            return redirect()->route('dashboard');
        }

        $areas = Area::orderBy('name')->get(['id', 'name']);
        $refCode = $request->query('ref', $request->query('sponsor', ''));

        $resellerEnabled = (bool) ((int) Setting::get('reseller_registration_enabled', '1'));
        $billerEnabled = (bool) ((int) Setting::get('biller_registration_enabled', '1'));
        $minDepositRegular = (int) Setting::get('reseller_min_deposit', 20000);
        $minDepositMaster = (int) Setting::get('biller_min_deposit', Setting::get('master_reseller_min_deposit', 2000000));
        $requireApproval = (bool) ((int) Setting::get('reseller_registration_require_approval', '1'));
        $termsText = Setting::get('reseller_registration_terms', null) ?: ResellerSettingController::getDefaultTerms();
        $companyPhone = Setting::get('company_phone', '088976397034');

        return Inertia::render('Auth/RegisterReseller', [
            'areas' => $areas,
            'defaultRefCode' => trim((string)$refCode),
            'resellerRegistrationEnabled' => $resellerEnabled,
            'billerRegistrationEnabled' => $billerEnabled,
            'minDepositRegular' => $minDepositRegular,
            'minDepositMaster' => $minDepositMaster,
            'requireApproval' => $requireApproval,
            'termsText' => $termsText,
            'companyPhone' => $companyPhone,
        ]);
    }

    /**
     * Proses pendaftaran mandiri Mitra Reseller & Biller.
     */
    public function store(Request $request)
    {
        $resellerEnabled = (bool) ((int) Setting::get('reseller_registration_enabled', '1'));
        $billerEnabled = (bool) ((int) Setting::get('biller_registration_enabled', '1'));

        // Cek jika seluruh pendaftaran dinonaktifkan
        if (!$resellerEnabled && !$billerEnabled) {
            throw ValidationException::withMessages([
                'tier' => 'Pendaftaran mandiri Mitra Reseller & Biller saat ini sedang ditutup sementara oleh pihak manajemen.'
            ]);
        }

        $validated = $request->validate([
            'tier' => 'required|in:regular,master',
            'master_referral_code' => 'nullable|string|max:30',
            'name' => 'required|string|max:255',
            'store_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:25',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'required|string|max:500',
            'area_id' => 'nullable|exists:areas,id',
            'terms' => 'accepted',
        ], [
            'tier.required' => 'Pilih jenis kemitraan (Mitra Reseller atau Master Biller).',
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung masuk ke login.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'address.required' => 'Alamat atau lokasi usaha/konter wajib diisi.',
            'terms.accepted' => 'Anda wajib menyetujui syarat & ketentuan kemitraan untuk melanjutkan pendaftaran.',
        ]);

        // Validasi tier aktif
        if ($validated['tier'] === 'regular' && !$resellerEnabled) {
            throw ValidationException::withMessages([
                'tier' => 'Pendaftaran mandiri Mitra Reseller (Voucher & Eceran) saat ini sedang dinonaktifkan oleh Admin.'
            ]);
        }

        if ($validated['tier'] === 'master' && !$billerEnabled) {
            throw ValidationException::withMessages([
                'tier' => 'Pendaftaran mandiri Master Biller & Loket Pembayaran saat ini sedang dinonaktifkan oleh Admin.'
            ]);
        }

        $requireApproval = (bool) ((int) Setting::get('reseller_registration_require_approval', '1'));

        // Cek Master Referral Code jika diisi
        $parentId = null;
        if ($validated['tier'] === 'regular' && !empty($validated['master_referral_code'])) {
            $cleanedCode = trim(strtoupper($validated['master_referral_code']));
            $masterReseller = Reseller::where('tier', 'master')
                ->where('referral_code', $cleanedCode)
                ->where('is_active', true)
                ->first();

            if ($masterReseller) {
                $parentId = $masterReseller->id;
            }
        }

        $user = null;
        $reseller = null;

        DB::transaction(function () use ($validated, $parentId, $requireApproval, &$user, &$reseller) {
            $displayName = !empty($validated['store_name']) 
                ? "{$validated['name']} ({$validated['store_name']})" 
                : $validated['name'];

            // 1. Buat User Reseller
            $user = User::create([
                'name' => $displayName,
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'reseller',
                'is_active' => !$requireApproval, // Jika require_approval true, user nonaktif hingga disetujui admin
            ]);

            // Assign Spatie Role jika ada
            try {
                $resellerRole = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
                $user->assignRole($resellerRole);
            } catch (\Exception $e) {}

            // 2. Ambil Nama Area
            $areaName = null;
            if (!empty($validated['area_id'])) {
                $area = Area::find($validated['area_id']);
                $areaName = $area?->name;
            }

            // 3. Buat Customer record khusus Mitra Murni (Bebas Tagihan / Non-PSB)
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $displayName,
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'area_id' => $validated['area_id'] ?? null,
                'area' => $areaName,
                'status' => $requireApproval ? 'booking' : 'active',
                'service_status' => 'gratis',
                'package_id' => null,
                'base_amount' => 0,
                'is_reseller' => true,
                'registration_date' => now()->toDateString(),
                'activation_date' => $requireApproval ? null : now()->toDateString(),
                'billing_type' => Setting::get('billing_type', 'prabayar'),
                'notes' => $requireApproval 
                    ? ($validated['tier'] === 'master' ? 'Pendaftaran Mandiri Master Biller (Menunggu Approve Admin)' : 'Pendaftaran Mandiri Mitra Reseller (Menunggu Approve Admin)')
                    : ($validated['tier'] === 'master' ? 'Pendaftaran Mandiri Master Biller & Distributor Wilayah' : 'Pendaftaran Mandiri Mitra Reseller Voucher & Loket'),
            ]);

            // 4. Buat Dompet Saldo Reseller (dengan Tier dan Referral)
            $reseller = Reseller::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'balance' => 0,
                'is_active' => !$requireApproval,
                'tier' => $validated['tier'],
                'parent_id' => $parentId,
                'referral_code' => $validated['tier'] === 'master' ? Reseller::generateReferralCode() : null,
                'status' => $requireApproval ? 'pending' : 'active',
            ]);

            // 5. Catat Audit Log
            try {
                AuditLog::createLog(
                    'Register Reseller Self',
                    $reseller,
                    null,
                    $requireApproval ? 'pending' : 'active',
                    "Pendaftaran mandiri Mitra (" . strtoupper($validated['tier']) . "): {$displayName} ({$user->email})" 
                        . ($requireApproval ? " [Status: Menunggu Approve Admin]" : " [Status: Aktif]")
                        . ($parentId ? " [Downline Master ID #{$parentId}]" : "")
                );
            } catch (\Exception $e) {}
        });

        // 6. Alur respon berdasarkan kebijakan Persetujuan Admin
        if ($requireApproval) {
            $minDeposit = $validated['tier'] === 'master'
                ? (int) Setting::get('biller_min_deposit', Setting::get('master_reseller_min_deposit', 2000000))
                : (int) Setting::get('reseller_min_deposit', 20000);

            $cleanAdminPhone = preg_replace('/[^0-9]/', '', (string) Setting::get('company_phone', '088976397034'));
            if (str_starts_with($cleanAdminPhone, '0')) {
                $cleanAdminPhone = '62' . substr($cleanAdminPhone, 1);
            } elseif (str_starts_with($cleanAdminPhone, '8')) {
                $cleanAdminPhone = '62' . $cleanAdminPhone;
            }

            $displayName = !empty($validated['store_name']) 
                ? "{$validated['name']} ({$validated['store_name']})" 
                : $validated['name'];

            session()->flash('registered_mitra', [
                'name' => $validated['name'],
                'store_name' => $validated['store_name'] ?? '',
                'display_name' => $displayName,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'tier' => $validated['tier'],
                'tier_label' => $validated['tier'] === 'master' ? 'Master Biller & Loket Pembayaran' : 'Mitra Reseller (Voucher & Eceran)',
                'min_deposit' => $minDeposit,
                'admin_phone' => $cleanAdminPhone,
            ]);

            return redirect()->route('register-reseller.success');
        }

        // Jika approval dinonaktifkan, langsung login otomatis
        Auth::login($user);
        $request->session()->regenerate();

        if ($reseller && $reseller->tier === 'master') {
            $msg = "Selamat bergabung sebagai Master Biller & Distributor Wilayah! Kode Referral Anda: {$reseller->referral_code}. Silakan isi saldo modal (min. Rp 2.000.000) untuk mulai mendistribusikan saldo dan melayani loket.";
        } else {
            $msg = "Selamat bergabung sebagai Mitra Reseller Voucher & Loket Biller! Silakan isi saldo modal (min. Rp 20.000) untuk mulai melayani transaksi.";
        }

        return redirect()->route('client-area.dashboard')->with('success', $msg);
    }

    /**
     * Halaman status sukses pendaftaran mandiri (menunggu approval admin)
     */
    public function success(Request $request)
    {
        $data = session('registered_mitra');

        $cleanAdminPhone = preg_replace('/[^0-9]/', '', (string) Setting::get('company_phone', '088976397034'));
        if (str_starts_with($cleanAdminPhone, '0')) {
            $cleanAdminPhone = '62' . substr($cleanAdminPhone, 1);
        } elseif (str_starts_with($cleanAdminPhone, '8')) {
            $cleanAdminPhone = '62' . $cleanAdminPhone;
        }

        return Inertia::render('Auth/RegisterResellerSuccess', [
            'registeredMitra' => $data,
            'companyPhone' => Setting::get('company_phone', '088976397034'),
            'adminWhatsapp' => $cleanAdminPhone,
            'resellerMinDeposit' => (int) Setting::get('reseller_min_deposit', 20000),
            'billerMinDeposit' => (int) Setting::get('biller_min_deposit', Setting::get('master_reseller_min_deposit', 2000000)),
        ]);
    }
}
