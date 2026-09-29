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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed_mbps' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'access_mode' => 'required|in:pppoe,hotspot',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        InternetPackage::create($validated);

        return redirect()->back()->with('success', 'Paket internet berhasil ditambahkan.');
    }

    public function update(Request $request, InternetPackage $internetPackage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed_mbps' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'access_mode' => 'required|in:pppoe,hotspot',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

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
