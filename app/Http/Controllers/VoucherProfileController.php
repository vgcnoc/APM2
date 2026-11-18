<?php

namespace App\Http\Controllers;

use App\Models\VoucherProfile;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherProfileController extends Controller
{
    public function index()
    {
        $profiles = VoucherProfile::withCount([
            'vouchers',
            'vouchers as available_vouchers_count' => function ($q) {
                $q->where('status', 'available');
            },
            'vouchers as used_vouchers_count' => function ($q) {
                $q->where('status', 'used');
            },
            'vouchers as expired_vouchers_count' => function ($q) {
                $q->where('status', 'expired');
            },
        ])->latest()->get();

        $stats = [
            'total_profiles' => $profiles->count(),
            'paid_profiles' => $profiles->where('is_customer_free', false)->count(),
            'free_profiles' => $profiles->where('is_customer_free', true)->count(),
            'total_vouchers' => (int) $profiles->sum('vouchers_count'),
            'total_available' => (int) $profiles->sum('available_vouchers_count'),
            'total_used' => (int) $profiles->sum('used_vouchers_count'),
            'total_expired' => (int) $profiles->sum('expired_vouchers_count'),
            'total_nominal_potential' => (float) $profiles->reduce(function ($carry, $p) {
                return $carry + ($p->available_vouchers_count * (float) $p->price);
            }, 0),
        ];

        return Inertia::render('Vouchers/Profiles/Index', [
            'profiles' => $profiles,
            'stats' => $stats
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
            'fee_admin' => 'nullable|numeric|min:0',
            'fee_reseller' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_partner' => 'nullable|numeric|min:0',
            'duration' => $isCustomerFree ? 'nullable|string|max:255' : 'required|string|max:255',
            'limit_rate' => 'nullable|string|max:255',
            'shared_users' => 'required|integer|min:1',
            'is_customer_free' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];

        $validated = $request->validate($rules);
        $validated['fee_admin'] = (float) ($validated['fee_admin'] ?? 0);
        $validated['fee_partner'] = (float) ($validated['fee_partner'] ?? 0);
        $validated['fee_reseller'] = (float) ($validated['fee_reseller'] ?? 0);
        $validated['price'] = (float) ($validated['price'] ?? 0);

        if (!$isCustomerFree) {
            $price = $validated['price'];
            $feeReseller = $validated['fee_reseller'];

            if ($feeReseller > $price) {
                return redirect()->back()->withErrors([
                    'fee_reseller' => 'Fee reseller (Rp ' . number_format($feeReseller, 0, ',', '.') . ') tidak boleh melebihi harga jual voucher (Rp ' . number_format($price, 0, ',', '.') . ').'
                ])->withInput();
            }

            $durationSec = RadiusService::parseDuration($validated['duration']);
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
            app(RadiusService::class)->syncVoucherProfile($profile);
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
            'fee_admin' => 'nullable|numeric|min:0',
            'fee_reseller' => $isCustomerFree ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'fee_partner' => 'nullable|numeric|min:0',
            'duration' => $isCustomerFree ? 'nullable|string|max:255' : 'required|string|max:255',
            'limit_rate' => 'nullable|string|max:255',
            'shared_users' => 'required|integer|min:1',
            'is_customer_free' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];

        $validated = $request->validate($rules);
        $validated['fee_admin'] = (float) ($validated['fee_admin'] ?? 0);
        $validated['fee_partner'] = (float) ($validated['fee_partner'] ?? 0);
        $validated['fee_reseller'] = (float) ($validated['fee_reseller'] ?? 0);
        $validated['price'] = (float) ($validated['price'] ?? 0);

        if (!$isCustomerFree) {
            $price = $validated['price'];
            $feeReseller = $validated['fee_reseller'];

            if ($feeReseller > $price) {
                return redirect()->back()->withErrors([
                    'fee_reseller' => 'Fee reseller (Rp ' . number_format($feeReseller, 0, ',', '.') . ') tidak boleh melebihi harga jual voucher (Rp ' . number_format($price, 0, ',', '.') . ').'
                ])->withInput();
            }

            $durationSec = RadiusService::parseDuration($validated['duration']);
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
            app(RadiusService::class)->syncVoucherProfile($voucherProfile);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[RADIUS] Sync Voucher Profile {$voucherProfile->id} error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Profil Voucher berhasil diperbarui dan disinkronkan ke RADIUS.');
    }

    public function syncSingle(VoucherProfile $voucherProfile)
    {
        try {
            app(RadiusService::class)->syncVoucherProfile($voucherProfile);
            return redirect()->back()->with('success', "Profil \"{$voucherProfile->name}\" berhasil disinkronkan ke FreeRADIUS & MikroTik.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[RADIUS] Sync Voucher Profile {$voucherProfile->id} error: " . $e->getMessage());
            return redirect()->back()->with('error', "Gagal sinkron RADIUS: " . $e->getMessage());
        }
    }

    public function syncAll()
    {
        try {
            $radius = app(RadiusService::class);
            $profiles = VoucherProfile::all();
            $count = 0;
            foreach ($profiles as $profile) {
                $radius->syncVoucherProfile($profile);
                $count++;
            }
            return redirect()->back()->with('success', "{$count} Profil Voucher berhasil disinkronkan ke FreeRADIUS & MikroTik.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[RADIUS] Sync All Voucher Profiles error: " . $e->getMessage());
            return redirect()->back()->with('error', "Gagal sinkron RADIUS: " . $e->getMessage());
        }
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
        $profileName = $voucherProfile->name;
        $voucherProfile->delete();

        try {
            $radius = app(RadiusService::class);
            $groupName = $radius->voucherGroup($profileId);
            $radius->removeGroup($groupName);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[RADIUS] Remove Voucher Profile Group error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', "Profil Voucher \"{$profileName}\" berhasil dihapus dan dibersihkan dari RADIUS.");
    }
}
