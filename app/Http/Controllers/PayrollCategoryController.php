<?php

namespace App\Http\Controllers;

use App\Models\PayrollCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $tab = $request->query('tab', 'semua');

        $query = PayrollCategory::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            });

        if ($tab !== 'semua') {
            $query->where('type', $tab);
        }

        $categories = $query->orderBy('type')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => PayrollCategory::count(),
            'total_incentive' => PayrollCategory::where('type', 'incentive')->count(),
            'total_deduction' => PayrollCategory::where('type', 'deduction')->count(),
            'active' => PayrollCategory::where('is_active', true)->count(),
            'auto' => PayrollCategory::where('mode', 'auto')->count(),
            'manual' => PayrollCategory::where('mode', 'manual')->count(),
        ];

        return Inertia::render('Settings/PayrollCategories', [
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:incentive,deduction',
            'mode' => 'required|in:auto,manual',
            'default_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['name'] = trim($validated['name']);
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
        if (!isset($validated['default_amount']) || $validated['default_amount'] === '') {
            $validated['default_amount'] = 0;
        }

        PayrollCategory::create($validated);

        $typeLabel = $validated['type'] === 'incentive' ? 'Insentif' : 'Potongan';
        return back()->with('success', "Kategori {$typeLabel} '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $payrollCategory = $id instanceof PayrollCategory ? $id : PayrollCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:incentive,deduction',
            'mode' => 'required|in:auto,manual',
            'default_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['name'] = trim($validated['name']);
        if (!isset($validated['default_amount']) || $validated['default_amount'] === '') {
            $validated['default_amount'] = 0;
        }

        $payrollCategory->update($validated);

        return back()->with('success', "Kategori '{$payrollCategory->name}' berhasil diperbarui.");
    }

    public function toggleActive($id)
    {
        $payrollCategory = $id instanceof PayrollCategory ? $id : PayrollCategory::findOrFail($id);
        $payrollCategory->update(['is_active' => !$payrollCategory->is_active]);

        $statusText = $payrollCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Kategori {$payrollCategory->name} berhasil {$statusText}.");
    }

    public function destroy($id)
    {
        $payrollCategory = $id instanceof PayrollCategory ? $id : PayrollCategory::findOrFail($id);
        $name = $payrollCategory->name;
        $payrollCategory->delete();

        return back()->with('success', "Kategori '{$name}' berhasil dihapus.");
    }

    // Endpoint for fetching active categories for the manual dropdown
    public function getActiveCategories()
    {
        return response()->json(
            PayrollCategory::where('is_active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get()
        );
    }
}
