<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VoucherProfile;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResellerSettingController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->input('role', 'reseller');
        $search = trim($request->input('search', ''));

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
        // if 'all', tampilkan seluruh user tanpa pembatasan role

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
        $totalResellers = User::where(function ($q) {
            $q->where('role', 'reseller')
              ->orWhereHas('roles', function ($rq) {
                  $rq->where('name', 'reseller');
              })
              ->orWhereHas('reseller')
              ->orHas('voucherProfiles');
        })->count();

        $customAccessCount = User::where(function ($q) {
            $q->where('role', 'reseller')
              ->orWhereHas('roles', function ($rq) {
                  $rq->where('name', 'reseller');
              })
              ->orWhereHas('reseller')
              ->orHas('voucherProfiles');
        })->has('voucherProfiles')->count();

        $fullAccessCount = max(0, $totalResellers - $customAccessCount);

        return Inertia::render('Settings/Reseller/Index', [
            'users' => $users,
            'voucherProfiles' => $voucherProfiles,
            'stats' => [
                'total_resellers' => $totalResellers,
                'full_access_count' => $fullAccessCount,
                'custom_access_count' => $customAccessCount,
                'total_profiles' => $voucherProfiles->count(),
            ],
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
            ]
        ]);
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
