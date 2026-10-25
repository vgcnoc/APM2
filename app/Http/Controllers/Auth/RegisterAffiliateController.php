<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AffiliateProfile;
use App\Models\User;
use App\Services\AffiliateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RegisterAffiliateController extends Controller
{
    private function eligibleSponsors(): Builder
    {
        return AffiliateProfile::where('status', 'active')
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true));
    }

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

        $refCode = strtoupper(trim((string) $request->query('ref', '')));
        $upline = null;
        if ($refCode) {
            $uplineProfile = $this->eligibleSponsors()->where('referral_code', $refCode)->with('user')->first();
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
        if (is_string($request->input('referral_code'))) {
            $request->merge(['referral_code' => strtoupper(trim($request->input('referral_code')))]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:25',
            'password' => 'required|string|min:6|confirmed',
            'referral_code' => [
                'bail', 'required', 'string', 'max:50',
                function ($attribute, $value, $fail) {
                    if (!$this->eligibleSponsors()->where('referral_code', $value)->exists()) {
                        $fail('Kode referral tidak terdaftar atau pengajak sudah tidak aktif. Periksa kembali kode dari pengajak Anda.');
                    }
                },
            ],
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
            'referral_code.required' => 'Kode referral pengajak wajib diisi. Minta kode yang terdaftar kepada pengajak Anda.',
            'bank_name.required' => 'Pilih bank atau e-wallet untuk pencairan komisi Anda.',
            'bank_account_number.required' => 'Nomor rekening atau nomor e-wallet wajib diisi.',
            'bank_account_holder.required' => 'Nama pemilik rekening wajib diisi.',
        ]);

        $user = null;

        DB::transaction(function () use ($validated, &$user) {
            $uplineProfile = $this->eligibleSponsors()->where('referral_code', $validated['referral_code'])
                ->lockForUpdate()->first();

            if (!$uplineProfile) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Kode referral pengajak sudah tidak tersedia. Minta kode referral yang aktif.',
                ]);
            }

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
            AffiliateService::ensureAffiliateProfile($user, $uplineProfile->user_id, [
                'whatsapp' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'bank_name' => $validated['bank_name'],
                'bank_account_number' => $validated['bank_account_number'],
                'bank_account_holder' => $validated['bank_account_holder'],
            ]);
        });

        // Login otomatis
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('login_portal', 'affiliate');

        return redirect()->route('affiliate.dashboard')->with('success', 'Selamat bergabung sebagai Mitra Afiliasi Resmi! Bagikan link referral Anda untuk mulai menghasilkan komisi.');
    }
}
