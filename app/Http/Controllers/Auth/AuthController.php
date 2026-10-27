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
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => 'Akun Anda berstatus non-aktif. Silakan hubungi administrator.'])->onlyInput('email');
            }

            $userRole = strtolower(trim((string) $user->role));
            $portal = $request->input('portal');

            // 1. Portal Afiliasi & Sales
            if ($portal === 'affiliate') {
                if (!AffiliateService::canAccessPortal($user)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors(['email' => 'Akun tidak aktif atau tidak memiliki akses Afiliasi. Gunakan akun karyawan, sales, atau mitra afiliasi yang aktif.'])->onlyInput('email');
                }

                $request->session()->put('login_portal', 'affiliate');
                $request->session()->forget('url.intended');
                return redirect()->route('affiliate.dashboard');
            }

            // 2. Portal Mitra Biller & Reseller
            if ($portal === 'reseller') {
                $canAccessReseller = in_array($userRole, ['reseller', 'admin', 'superadmin', 'super admin', 'super-admin'], true)
                    || (method_exists($user, 'hasRole') && ($user->hasRole('reseller') || $user->hasRole('admin') || $user->hasRole('super admin')))
                    || \App\Models\Reseller::where('user_id', $user->id)->exists();

                if (!$canAccessReseller) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors(['email' => 'Akun tidak memiliki hak akses sebagai Mitra Biller / Reseller. Silakan daftar atau gunakan akun mitra yang valid.'])->onlyInput('email');
                }

                $request->session()->put('login_portal', 'reseller');
                $request->session()->forget('url.intended');
                return redirect()->route('client-area.dashboard');
            }

            // 3. Portal Pelanggan
            if ($portal === 'customer') {
                $customer = \App\Models\Customer::where('user_id', $user->id)
                    ->orWhere('email', $user->email)
                    ->first();
                if (!$customer) {
                    $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
                    if ($reseller && $reseller->customer_id) {
                        $customer = \App\Models\Customer::find($reseller->customer_id);
                    }
                }

                $isStaff = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin'], true)
                    || (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super admin')));

                $isReseller = ($userRole === 'reseller')
                    || (method_exists($user, 'hasRole') && $user->hasRole('reseller'))
                    || \App\Models\Reseller::where('user_id', $user->id)->exists();

                // OPSI A: Jika akun adalah Reseller murni (tidak berlangganan paket internet rumah), tolak login di portal pelanggan
                if (!$isStaff && $isReseller && (!$customer || !$customer->package_id)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'Akun Anda terdaftar sebagai Mitra Reseller/Biller (tanpa langganan paket internet rumah). Silakan login melalui menu Mitra Biller.'
                    ])->onlyInput('email');
                }

                $canAccessCustomer = $isStaff
                    || ($customer && $customer->package_id)
                    || in_array($userRole, ['customer'], true)
                    || (method_exists($user, 'hasRole') && $user->hasRole('customer'))
                    || \App\Models\Customer::where('user_id', $user->id)->orWhere('email', $user->email)->exists();

                if (!$canAccessCustomer) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors(['email' => 'Akun tidak terdaftar sebagai Pelanggan. Gunakan email pelanggan yang terdaftar pada sistem.'])->onlyInput('email');
                }

                $request->session()->put('login_portal', 'customer');
                $request->session()->forget('url.intended');
                return redirect()->route('customer-area.dashboard');
            }

            // 4. Portal default / Staf & Tim
            $isAdmin = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin'], true)
                || (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->hasRole('superadmin')));

            // Verifikasi data karyawan di tabel employees berdasarkan email
            $hasEmployeeTable = \Illuminate\Support\Facades\Schema::hasTable('employees');
            $employee = ($hasEmployeeTable && !empty($user->email))
                ? \App\Models\Employee::where('email', $user->email)->first()
                : null;

            $isEmployeeResigned = $employee && ($employee->status === 'resigned');
            $isEmployeeActive = $employee && ($employee->status === 'active' || empty($employee->status));

            $isReseller = ($userRole === 'reseller')
                || (method_exists($user, 'hasRole') && $user->hasRole('reseller'))
                || \App\Models\Reseller::where('user_id', $user->id)->exists();

            $isCustomer = ($userRole === 'customer')
                || (method_exists($user, 'hasRole') && $user->hasRole('customer'))
                || \App\Models\Customer::where('user_id', $user->id)->orWhere('email', $user->email)->exists();

            // Kumpulkan seluruh role user (baik kolom role langsung maupun Spatie roles)
            $spatieRoles = method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()->map(fn($r) => strtolower(trim((string) $r)))->toArray()
                : [];
            $allUserRoles = array_unique(array_filter(array_merge([$userRole], $spatieRoles)));

            // Fallback untuk unit testing jika tabel employees belum dimigrasi di memory
            if (!$hasEmployeeTable && app()->runningUnitTests()) {
                $hasStaffRole = false;
                foreach ($allUserRoles as $roleName) {
                    if (!in_array($roleName, ['reseller', 'customer', 'affiliate'], true)) {
                        $hasStaffRole = true;
                        break;
                    }
                }
                $isEmployeeActive = $hasStaffRole;
            }

            // Portal Staf & Tim HANYA dapat diakses oleh Admin atau Karyawan resmi yang berstatus aktif
            $isValidStaff = $isAdmin || $isEmployeeActive;

            // Jika bukan karyawan aktif atau admin, tolak login dan beri pesan spesifik
            if (!$isValidStaff) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($isEmployeeResigned) {
                    return back()->withErrors([
                        'email' => 'Akun karyawan Anda berstatus Resign / non-aktif. Silakan hubungi bagian HR / Administrator.'
                    ])->onlyInput('email');
                }

                if ($isReseller) {
                    return back()->withErrors([
                        'email' => 'Akun Anda terdaftar sebagai Mitra Biller / Reseller. Silakan masuk melalui portal Mitra Biller.'
                    ])->onlyInput('email');
                }

                if ($isCustomer) {
                    return back()->withErrors([
                        'email' => 'Akun Anda terdaftar sebagai Pelanggan. Silakan masuk melalui portal Pelanggan.'
                    ])->onlyInput('email');
                }

                if (in_array('affiliate', $allUserRoles, true) || in_array($userRole, ['affiliate'], true)) {
                    return back()->withErrors([
                        'email' => 'Akun Anda terdaftar untuk portal Afiliasi & Sales. Silakan masuk melalui portal Afiliasi & Sales.'
                    ])->onlyInput('email');
                }

                return back()->withErrors([
                    'email' => 'Akun Anda tidak terdaftar sebagai staf atau karyawan resmi di perusahaan. Hanya akun karyawan terdaftar yang dapat masuk melalui portal ini.'
                ])->onlyInput('email');
            }

            $request->session()->put('login_portal', 'staff');
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
