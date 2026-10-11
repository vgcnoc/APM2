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
        $isCustomerFree = $request->boolean('is_customer_free');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_admin' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_reseller' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_partner' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'duration' => $isCustomerFree ? 'nullable|string|max:255' : 'required|string|max:255',
            'limit_rate' => 'nullable|string|max:255',
            'shared_users' => 'required|integer|min:1',
            'is_customer_free' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if ($isCustomerFree) {
            $validated['price'] = 0;
            $validated['fee_admin'] = 0;
            $validated['fee_reseller'] = 0;
            $validated['fee_partner'] = 0;
            $validated['duration'] = 'Mengikuti Pelanggan';
            $validated['shared_users'] = 1;
            $validated['is_customer_free'] = true;
        }

        VoucherProfile::create($validated);

        return redirect()->back()->with('success', 'Profil Voucher berhasil ditambahkan.');
    }

    public function update(Request $request, VoucherProfile $voucherProfile)
    {
        $isCustomerFree = $request->boolean('is_customer_free') || $voucherProfile->is_customer_free;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_admin' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_reseller' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_partner' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'duration' => $isCustomerFree ? 'nullable|string|max:255' : 'required|string|max:255',
            'limit_rate' => 'nullable|string|max:255',
            'shared_users' => 'required|integer|min:1',
            'is_customer_free' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if ($isCustomerFree) {
            $validated['price'] = 0;
            $validated['fee_admin'] = 0;
            $validated['fee_reseller'] = 0;
            $validated['fee_partner'] = 0;
            $validated['duration'] = 'Mengikuti Pelanggan';
            $validated['shared_users'] = 1;
            $validated['is_customer_free'] = true;
        }

        $voucherProfile->update($validated);

        return redirect()->back()->with('success', 'Profil Voucher berhasil diperbarui.');
    }

    public function destroy(VoucherProfile $voucherProfile)
    {
        if ($voucherProfile->is_customer_free) {
            return redirect()->back()->with('error', 'Profil khusus voucher gratis pelanggan tidak dapat dihapus karena digunakan oleh sistem aktivasi pelanggan.');
        }

        $voucherProfile->delete();

        return redirect()->back()->with('success', 'Profil Voucher berhasil dihapus.');
    }
}
