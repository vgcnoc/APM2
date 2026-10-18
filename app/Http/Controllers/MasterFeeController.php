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
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            });

        if ($tab !== 'semua') {
            $query->where('type', $tab);
        }

        $fees = $query->orderBy('type')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'booking' => MasterFee::where('type', 'Fee Booking')->count(),
            'booking_nominal' => (float) (MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal') ?? 0),
            'survey' => MasterFee::where('type', 'Fee Survey')->count(),
            'survey_nominal' => (float) (MasterFee::where('type', 'Fee Survey')->where('is_active', true)->value('nominal') ?? 0),
            'pasang' => MasterFee::where('type', 'Fee Pasang')->count(),
            'pasang_nominal' => (float) (MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal') ?? 0),
            'freelance' => MasterFee::where('type', 'Fee Freelance per Paket')->count(),
            'freelance_nominal' => (float) (MasterFee::where('type', 'Fee Freelance per Paket')->where('is_active', true)->value('nominal') ?? 0),
            'target' => MasterFee::where('type', 'Bonus Target Booking')->count(),
            'target_nominal' => (float) (MasterFee::where('type', 'Bonus Target Booking')->where('is_active', true)->value('nominal') ?? 0),
            'total' => MasterFee::count(),
            'active' => MasterFee::where('is_active', true)->count(),
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
            'type' => 'required|string|in:Fee Booking,Fee Survey,Fee Pasang,Fee Freelance per Paket,Bonus Target Booking',
            'mode' => 'required|in:Auto,Manual',
            'nominal' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['name'] = trim($validated['name']);
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        MasterFee::create($validated);

        return back()->with('success', 'Master Fee berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $masterFee = $id instanceof MasterFee ? $id : MasterFee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Fee Booking,Fee Survey,Fee Pasang,Fee Freelance per Paket,Bonus Target Booking',
            'mode' => 'required|in:Auto,Manual',
            'nominal' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['name'] = trim($validated['name']);

        $masterFee->update($validated);

        return back()->with('success', 'Master Fee berhasil diperbarui.');
    }

    public function toggleActive($id)
    {
        $masterFee = $id instanceof MasterFee ? $id : MasterFee::findOrFail($id);
        $masterFee->update(['is_active' => !$masterFee->is_active]);

        $statusText = $masterFee->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Master Fee {$masterFee->name} berhasil {$statusText}.");
    }

    public function destroy($id)
    {
        $masterFee = $id instanceof MasterFee ? $id : MasterFee::findOrFail($id);
        $name = $masterFee->name;
        $masterFee->delete();

        return back()->with('success', "Master Fee {$name} berhasil dihapus.");
    }
}
