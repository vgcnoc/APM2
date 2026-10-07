<?php

namespace App\Http\Controllers;

use App\Models\MasterFee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterFeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $tab = $request->query('tab', 'semua');

        $query = MasterFee::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            });

        if ($tab !== 'semua') {
            $query->where('type', $tab);
        }

        $fees = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'booking' => MasterFee::where('type', 'Fee Booking')->count(),
            'survey' => MasterFee::where('type', 'Fee Survey')->count(),
            'pasang' => MasterFee::where('type', 'Fee Pasang')->count(),
            'total' => MasterFee::count(),
            'freelance' => MasterFee::where('type', 'Fee Freelance per Paket')->count(),
            'target' => MasterFee::where('type', 'Bonus Target Booking')->count(),
        ];

        return Inertia::render('Settings/MasterFees', [
            'fees' => $fees,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'mode' => 'required|in:Auto,Manual',
            'nominal' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        MasterFee::create($validated);

        return back()->with('success', 'Master Fee berhasil ditambahkan.');
    }

    public function update(Request $request, MasterFee $masterFee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'mode' => 'required|in:Auto,Manual',
            'nominal' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $masterFee->update($validated);

        return back()->with('success', 'Master Fee berhasil diperbarui.');
    }

    public function destroy(MasterFee $masterFee)
    {
        $masterFee->delete();
        return back()->with('success', 'Master Fee berhasil dihapus.');
    }
}
