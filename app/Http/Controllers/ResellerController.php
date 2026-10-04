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
        $query = Reseller::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $resellers = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Resellers/Index', [
            'resellers' => $resellers,
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
            'balance' => 'numeric|min:0',
            'is_active' => 'boolean'
        ]);

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
            'balance' => 'numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $reseller->update($validated);

        return redirect()->back()->with('success', 'Data Reseller berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reseller $reseller)
    {
        $reseller->delete();
        return redirect()->back()->with('success', 'Data Reseller berhasil dihapus.');
    }
}
