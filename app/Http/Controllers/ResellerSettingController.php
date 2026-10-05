<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VoucherProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResellerSettingController extends Controller
{
    public function index(Request $request)
    {
        // Get all users who could act as resellers.
        // For now, let's fetch users with role 'reseller', or maybe just all users that aren't admin.
        // I will fetch all users for flexibility, or filter by search
        $query = User::with('voucherProfiles');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $users = $query->paginate(20)->withQueryString();
        $voucherProfiles = VoucherProfile::all();

        return Inertia::render('Settings/Reseller/Index', [
            'users' => $users,
            'voucherProfiles' => $voucherProfiles,
            'filters' => $request->only('search')
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'voucher_profile_ids' => 'array',
            'voucher_profile_ids.*' => 'exists:voucher_profiles,id'
        ]);

        $user->voucherProfiles()->sync($validated['voucher_profile_ids'] ?? []);

        return redirect()->back()->with('success', 'Pengaturan profil voucher reseller ' . $user->name . ' berhasil disimpan.');
    }
}
