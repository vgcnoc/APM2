<?php

namespace App\Http\Controllers;

use App\Models\VoucherProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherProfileController extends Controller
{
    public function index()
    {
        $profiles = VoucherProfile::latest()->get();
        return Inertia::render('Vouchers/Profiles/Index', [
            'profiles' => $profiles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'fee_admin' => 'required|numeric|min:0',
            'fee_reseller' => 'required|numeric|min:0',
            'fee_partner' => 'required|numeric|min:0',
            'duration' => 'required|string|max:255',
            'limit_rate' => 'nullable|string|max:255',
            'shared_users' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        VoucherProfile::create($validated);

        return redirect()->back()->with('success', 'Profil Voucher berhasil ditambahkan.');
    }

    public function update(Request $request, VoucherProfile $voucherProfile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'fee_admin' => 'required|numeric|min:0',
            'fee_reseller' => 'required|numeric|min:0',
            'fee_partner' => 'required|numeric|min:0',
            'duration' => 'required|string|max:255',
            'limit_rate' => 'nullable|string|max:255',
            'shared_users' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $voucherProfile->update($validated);

        return redirect()->back()->with('success', 'Profil Voucher berhasil diperbarui.');
    }

    public function destroy(VoucherProfile $voucherProfile)
    {
        $voucherProfile->delete();

        return redirect()->back()->with('success', 'Profil Voucher berhasil dihapus.');
    }
}
