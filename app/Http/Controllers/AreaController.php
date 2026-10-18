<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Odp;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $query = Area::withCount(['customers', 'odps']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $areas = $query->orderBy('name')->paginate(15)->withQueryString();

        $totalAreas = Area::count();
        $totalCustomers = Customer::whereNotNull('area_id')->count();
        $totalOdps = Odp::whereNotNull('area_id')->count();

        return inertia('Settings/Areas', [
            'areas' => $areas,
            'stats' => [
                'total_areas' => $totalAreas,
                'total_customers' => $totalCustomers,
                'total_odps' => $totalOdps,
            ],
            'filters' => [
                'search' => $search,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|numeric|min:0',
        ]);

        Area::create($validated);

        return redirect()->back()->with('success', "Area '{$request->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|numeric|min:0',
        ]);

        $area->update($validated);

        return redirect()->back()->with('success', "Area '{$area->name}' berhasil diperbarui.");
    }

    public function destroy(Area $area)
    {
        $customersCount = $area->customers()->count();
        $odpsCount = $area->odps()->count();
        $usersCount = $area->users()->count();

        if ($customersCount > 0 || $odpsCount > 0 || $usersCount > 0) {
            $details = [];
            if ($customersCount > 0) $details[] = "{$customersCount} pelanggan";
            if ($odpsCount > 0) $details[] = "{$odpsCount} ODP";
            if ($usersCount > 0) $details[] = "{$usersCount} user/teknisi";

            return redirect()->back()->with('error', "Area '{$area->name}' tidak dapat dihapus karena masih digunakan oleh " . implode(', ', $details) . '. Pindahkan data terkait terlebih dahulu sebelum menghapus.');
        }

        $areaName = $area->name;
        $area->delete();
        return redirect()->back()->with('success', "Area '{$areaName}' berhasil dihapus.");
    }
}
