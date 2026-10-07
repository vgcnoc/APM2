<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InternetPackageController extends Controller
{
    public function index(Request $request)
    {
        $query = InternetPackage::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $packages = $query->orderBy('price', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('InternetPackages/Index', [
            'packages' => $packages,
            'filters' => $request->only(['search'])
        ]);
    }

    private function getValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'speed_mbps' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'access_mode' => 'required|in:pppoe,hotspot,voucher,static_ip,lainnya',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'color' => 'required|string|max:50',
            'is_promo' => 'boolean',
            'promo_price' => 'nullable|numeric|min:0',
            'is_mikrotik_group_custom' => 'boolean',
            'mikrotik_group' => 'nullable|string|max:255',
            'is_mikrotik_address_list_custom' => 'boolean',
            'mikrotik_address_list' => 'nullable|string|max:255',
            'shared_device' => 'required|integer|min:1',
            'rate_limit' => 'nullable|string|max:255',
            'active_period' => 'required|integer|min:1',
            'active_period_unit' => 'required|in:Hari,Minggu,Bulan,Tahun',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());
        
        InternetPackage::create($validated);

        return redirect()->back()->with('success', 'Paket internet berhasil ditambahkan.');
    }

    public function update(Request $request, InternetPackage $internetPackage)
    {
        $validated = $request->validate($this->getValidationRules());
        
        $internetPackage->update($validated);

        return redirect()->back()->with('success', 'Paket internet berhasil diperbarui.');
    }

    public function destroy(InternetPackage $internetPackage)
    {
        if ($internetPackage->customers()->count() > 0) {
            return redirect()->back()->with('error', 'Paket internet tidak dapat dihapus karena masih digunakan oleh pelanggan.');
        }

        $internetPackage->delete();

        return redirect()->back()->with('success', 'Paket internet berhasil dihapus.');
    }
}
