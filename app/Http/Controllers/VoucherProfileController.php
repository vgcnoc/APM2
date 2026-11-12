<?php

namespace App\Http\Controllers;

use App\Models\VoucherProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherProfileController extends Controller
{
    public function index()
    {
        $profiles = VoucherProfile::withCount('vouchers')->latest()->get();
        return Inertia::render('Vouchers/Profiles/Index', [
            'profiles' => $profiles
        ]);
    }

    public function store(Request $request)
    {
        $isCustomerFree = $request->boolean('is_customer_free');

        if ($isCustomerFree && VoucherProfile::where('is_customer_free', true)->exists()) {
            return redirect()->back()->withErrors([
                'is_customer_free' => 'Profil khusus voucher gratis pelanggan sudah ada di sistem (hanya boleh ada 1 master profil gratis).'
            ])->withInput();
        }

        $rules = [
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
        ];

        $validated = $request->validate($rules);

        if (!$isCustomerFree) {
            $price = (float) ($validated['price'] ?? 0);
            $feeReseller = (float) ($validated['fee_reseller'] ?? 0);

            if ($feeReseller > $price) {
                return redirect()->back()->withErrors([
                    'fee_reseller' => 'Fee reseller (Rp ' . number_format($feeReseller, 0, ',', '.') . ') tidak boleh melebihi harga jual voucher (Rp ' . number_format($price, 0, ',', '.') . ').'
                ])->withInput();
            }

            $durationSec = \App\Services\RadiusService::parseDuration($validated['duration']);
            if (!$durationSec || $durationSec <= 0) {
                return redirect()->back()->withErrors([
                    'duration' => 'Format durasi tidak valid. Gunakan format MikroTik standar seperti: 30m, 1h, 3h, 1d, 7d, 30d.'
                ])->withInput();
            }
        }

        if ($isCustomerFree) {
            $validated['price'] = 0;
            $validated['fee_admin'] = 0;
            $validated['fee_reseller'] = 0;
            $validated['fee_partner'] = 0;
            $validated['duration'] = 'Mengikuti Pelanggan';
            $validated['shared_users'] = 1;
            $validated['is_customer_free'] = true;
        }

        $profile = VoucherProfile::create($validated);

        try {
            app(\App\Services\RadiusService::class)->syncVoucherProfile($profile);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[RADIUS] Sync Voucher Profile {$profile->id} error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Profil Voucher berhasil ditambahkan dan disinkronkan ke RADIUS.');
    }

    public function update(Request $request, VoucherProfile $voucherProfile)
    {
        $isCustomerFree = $request->boolean('is_customer_free') || $voucherProfile->is_customer_free;

        if ($request->boolean('is_customer_free') && !$voucherProfile->is_customer_free) {
            if (VoucherProfile::where('is_customer_free', true)->where('id', '!=', $voucherProfile->id)->exists()) {
                return redirect()->back()->withErrors([
                    'is_customer_free' => 'Profil khusus voucher gratis pelanggan sudah ada di sistem.'
                ])->withInput();
            }
        }

        $rules = [
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
        ];

        $validated = $request->validate($rules);

        if (!$isCustomerFree) {
            $price = (float) ($validated['price'] ?? 0);
            $feeReseller = (float) ($validated['fee_reseller'] ?? 0);

            if ($feeReseller > $price) {
                return redirect()->back()->withErrors([
                    'fee_reseller' => 'Fee reseller (Rp ' . number_format($feeReseller, 0, ',', '.') . ') tidak boleh melebihi harga jual voucher (Rp ' . number_format($price, 0, ',', '.') . ').'
                ])->withInput();
            }

            $durationSec = \App\Services\RadiusService::parseDuration($validated['duration']);
            if (!$durationSec || $durationSec <= 0) {
                return redirect()->back()->withErrors([
                    'duration' => 'Format durasi tidak valid. Gunakan format MikroTik standar seperti: 30m, 1h, 3h, 1d, 7d, 30d.'
                ])->withInput();
            }
        }

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

        try {
            app(\App\Services\RadiusService::class)->syncVoucherProfile($voucherProfile);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[RADIUS] Sync Voucher Profile {$voucherProfile->id} error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Profil Voucher berhasil diperbarui dan disinkronkan ke RADIUS.');
    }

    public function destroy(VoucherProfile $voucherProfile)
    {
        if ($voucherProfile->is_customer_free) {
            return redirect()->back()->with('error', 'Profil khusus voucher gratis pelanggan tidak dapat dihapus karena digunakan oleh sistem aktivasi pelanggan.');
        }

        if ($voucherProfile->vouchers()->exists()) {
            return redirect()->back()->with('error', 'Profil Voucher ini tidak dapat dihapus karena masih digunakan oleh ' . $voucherProfile->vouchers()->count() . ' data voucher.');
        }

        $profileId = $voucherProfile->id;
        $voucherProfile->delete();

        try {
            app(\App\Services\RadiusService::class)->removeGroup("voucher_{$profileId}");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[RADIUS] Remove Voucher Profile Group voucher_{$profileId} error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Profil Voucher berhasil dihapus dan dibersihkan dari RADIUS.');
    }
}
