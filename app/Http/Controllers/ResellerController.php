<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reseller::with('area');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $resellers = $query->latest()->paginate(10)->withQueryString();

        $areas = \App\Models\Area::orderBy('name')->get();

        return Inertia::render('Resellers/Index', [
            'resellers' => $resellers,
            'areas' => $areas,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'area_id' => 'nullable|exists:areas,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'balance' => 'numeric|min:0',
            'is_active' => 'boolean',
            'ktp_photo' => 'nullable|image|max:2048' // max 2MB
        ]);

        if ($request->hasFile('ktp_photo')) {
            $validated['ktp_photo'] = $request->file('ktp_photo')->store('resellers/ktp', 'public');
        }

        Reseller::create($validated);

        return redirect()->back()->with('success', 'Data Reseller berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'area_id' => 'nullable|exists:areas,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'balance' => 'numeric|min:0',
            'is_active' => 'boolean',
            'ktp_photo' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('ktp_photo')) {
            if ($reseller->ktp_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($reseller->ktp_photo);
            }
            $validated['ktp_photo'] = $request->file('ktp_photo')->store('resellers/ktp', 'public');
        }

        $reseller->update($validated);

        return redirect()->back()->with('success', 'Data Reseller berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reseller $reseller)
    {
        if ($reseller->ktp_photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($reseller->ktp_photo);
        }
        $reseller->delete();
        return redirect()->back()->with('success', 'Data Reseller berhasil dihapus.');
    }
}
