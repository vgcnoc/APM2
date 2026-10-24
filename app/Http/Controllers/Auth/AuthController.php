<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'portal' => request()->query('portal', 'staff'),
        ]);
    }

    /**
     * Proses request login.
     */
    public function store(Request $request)
    {
        $request->validate(['portal' => ['nullable', 'in:staff,reseller,affiliate,customer']]);
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = $request->user();
            if (!$user->is_active || ($request->input('portal') === 'affiliate' && !AffiliateService::canAccessPortal($user))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => 'Akun tidak aktif atau tidak memiliki akses Afiliasi. Gunakan akun karyawan, sales, atau mitra afiliasi yang aktif.'])->onlyInput('email');
            }

            $isAffiliate = $request->input('portal') === 'affiliate'
                || in_array(strtolower(trim((string) $user->role)), ['sales', 'affiliate'], true);
            $request->session()->put('login_portal', $isAffiliate ? 'affiliate' : 'staff');

            if ($isAffiliate) {
                $request->session()->forget('url.intended');
                return redirect()->route('affiliate.dashboard');
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Proses request logout.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
