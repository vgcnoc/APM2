<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        return Inertia::render('Auth/RegisterReseller', [
            'areas' => $areas,
            'defaultRefCode' => trim((string)$refCode),
            'minDepositRegular' => (int) Setting::get('reseller_min_deposit', 50000),
            'minDepositMaster' => (int) Setting::get('master_reseller_min_deposit', 2000000),
        ]);
    }

    /**
     * Proses pendaftaran mandiri Mitra Reseller & Biller.
     */
    public function store(Request $request)
    {
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
        ], [
            'tier.required' => 'Pilih jenis kemitraan (Mitra Loket Biasa atau Master Biller).',
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung masuk ke login.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'address.required' => 'Alamat atau lokasi usaha/konter wajib diisi.',
        ]);

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

        DB::transaction(function () use ($validated, $parentId, &$user, &$reseller) {
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
            ]);

            // Assign Spatie Role jika ada
            try {
                $resellerRole = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
                $user->assignRole($resellerRole);
            } catch (\Exception $e) {
                // Role handling fallback
            }

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
                'status' => 'active',
                'service_status' => 'gratis',
                'package_id' => null,
                'base_amount' => 0,
                'is_reseller' => true,
                'registration_date' => now()->toDateString(),
                'activation_date' => now()->toDateString(),
                'billing_type' => Setting::get('billing_type', 'prabayar'),
                'notes' => $validated['tier'] === 'master' 
                    ? 'Pendaftaran Mandiri Master Biller & Distributor Wilayah' 
                    : 'Pendaftaran Mandiri Mitra Reseller Voucher & Loket Pembayaran (Biller)',
            ]);

            // 4. Buat Dompet Saldo Reseller (dengan Tier dan Referral)
            $reseller = Reseller::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'balance' => 0,
                'is_active' => true,
                'tier' => $validated['tier'],
                'parent_id' => $parentId,
                'referral_code' => $validated['tier'] === 'master' ? Reseller::generateReferralCode() : null,
                'status' => 'active',
            ]);

            // 5. Catat Audit Log
            try {
                AuditLog::createLog(
                    'Register Reseller Self',
                    $reseller,
                    null,
                    'active',
                    "Pendaftaran mandiri Mitra (" . strtoupper($validated['tier']) . "): {$displayName} ({$user->email})" . ($parentId ? " [Downline Master ID #{$parentId}]" : "")
                );
            } catch (\Exception $e) {}
        });

        // 6. Auto-login dan arahkan langsung ke Portal Reseller
        Auth::login($user);
        $request->session()->regenerate();

        if ($reseller && $reseller->tier === 'master') {
            $msg = "Selamat bergabung sebagai Master Biller & Distributor Wilayah! Kode Referral Anda: {$reseller->referral_code}. Bagikan kode ini ke calon loket binaan Anda.";
        } else {
            $msg = "Selamat bergabung sebagai Mitra Reseller Voucher & Loket Biller! Silakan isi saldo dompet untuk mulai melayani transaksi pelanggan.";
        }

        return redirect()->route('client-area.dashboard')->with('success', $msg);
    }
}
