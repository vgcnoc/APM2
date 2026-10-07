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

        $categories = PayrollCategory::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_incentive' => PayrollCategory::where('type', 'incentive')->count(),
            'total_deduction' => PayrollCategory::where('type', 'deduction')->count(),
        ];

        return Inertia::render('Settings/PayrollCategories', [
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:incentive,deduction',
            'mode' => 'required|in:auto,manual',
            'default_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        PayrollCategory::create($validated);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, PayrollCategory $payrollCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:incentive,deduction',
            'mode' => 'required|in:auto,manual',
            'default_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $payrollCategory->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(PayrollCategory $payrollCategory)
    {
        $payrollCategory->delete();
        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    // Endpoint for fetching active categories for the manual dropdown
    public function getActiveCategories()
    {
        return response()->json(PayrollCategory::where('is_active', true)->get());
    }
}
