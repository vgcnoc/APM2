<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AffiliateProfile;
use App\Models\User;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RegisterAffiliateController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran mitra marketing / afiliasi (Publik).
     */
    public function create(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->affiliateProfile) {
                return redirect()->route('affiliate.dashboard');
            }
        }

        $refCode = trim($request->query('ref', ''));
        $upline = null;
        if ($refCode) {
            $uplineProfile = AffiliateProfile::where('referral_code', $refCode)->with('user')->first();
            if ($uplineProfile && $uplineProfile->user) {
                $upline = [
                    'code' => $uplineProfile->referral_code,
                    'name' => $uplineProfile->user->name,
                ];
            }
        }

        $settings = AffiliateService::getSettings();

        return Inertia::render('Auth/RegisterAffiliate', [
            'initialRef' => $refCode,
            'upline' => $upline,
            'commissionSettings' => [
                'booking' => $settings['affiliate_commission_booking'],
                'monthly' => $settings['affiliate_commission_monthly'],
                'upline1' => $settings['affiliate_commission_upline_1'],
                'upline2' => $settings['affiliate_commission_upline_2'],
            ],
        ]);
    }

    /**
     * Proses pendaftaran mitra marketing / afiliasi baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:25',
            'password' => 'required|string|min:6|confirmed',
            'referral_code' => 'nullable|string|max:50',
            'bank_name' => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung masuk ke login.',
            'phone.required' => 'Nomor WhatsApp aktif wajib diisi untuk koordinasi & notifikasi komisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'bank_name.required' => 'Pilih bank atau e-wallet untuk pencairan komisi Anda.',
            'bank_account_number.required' => 'Nomor rekening atau nomor e-wallet wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening wajib diisi.',
        ]);

        $uplineId = null;
        if (!empty($validated['referral_code'])) {
            $uplineProfile = AffiliateProfile::where('referral_code', trim($validated['referral_code']))->first();
            if ($uplineProfile) {
                $uplineId = $uplineProfile->user_id;
            }
        }

        $user = null;

        DB::transaction(function () use ($validated, $uplineId, &$user) {
            // 1. Buat Akun User
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'affiliate',
                'is_active' => true,
            ]);

            // Assign role affiliate
            try {
                $role = Role::firstOrCreate(['name' => 'affiliate', 'guard_name' => 'web']);
                $user->assignRole($role);
            } catch (\Exception $e) {
                // Ignore fallback
            }

            // 2. Buat Profil Afiliasi & Unique Referral Code
            AffiliateService::ensureAffiliateProfile($user, $uplineId, [
                'whatsapp' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'bank_name' => $validated['bank_name'],
                'bank_account_number' => $validated['bank_account_number'],
                'bank_account_holder' => $validated['bank_account_holder'],
            ]);
        });

        // Login otomatis
        Auth::login($user);

        return redirect()->route('affiliate.dashboard')->with('success', 'Selamat bergabung sebagai Mitra Afiliasi Resmi! Bagikan link referral Anda untuk mulai menghasilkan komisi.');
    }
}
