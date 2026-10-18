<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Auto-seed default categories jika tabel kosong
        if (ExpenseCategory::count() === 0) {
            $this->seedDefaults();
        }

        $search = trim($request->query('search', ''));

        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->withSum('expenses', 'amount')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_categories' => ExpenseCategory::count(),
            'active_categories' => ExpenseCategory::where('is_active', true)->count(),
            'total_expense_amount' => (float) \App\Models\Expense::sum('amount'),
        ];

        return Inertia::render('Settings/ExpenseCategories', [
            'categories' => $categories,
            'stats' => $stats,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $created = ExpenseCategory::create([
            'name' => $validated['name'],
            'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? 'rose',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Kategori pengeluaran '{$created->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $expenseCategory = ExpenseCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $expenseCategory->update([
            'name' => $validated['name'],
            'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? $expenseCategory->color,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $expenseCategory->is_active,
        ]);

        return back()->with('success', "Kategori pengeluaran '{$expenseCategory->name}' berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $expenseCategory = ExpenseCategory::findOrFail($id);
        $name = $expenseCategory->name;
        $expensesCount = $expenseCategory->expenses()->count();

        if ($expensesCount > 0) {
            // Unlink expenses agar tidak hilang riwayat keuangannya
            $expenseCategory->expenses()->update(['expense_category_id' => null]);
        }

        $expenseCategory->delete();

        $info = $expensesCount > 0 ? " ({$expensesCount} transaksi terkait kini tidak berkategori)" : "";
        return back()->with('success', "Kategori pengeluaran '{$name}' berhasil dihapus{$info}.");
    }

    public function toggleStatus($id)
    {
        $expenseCategory = ExpenseCategory::findOrFail($id);
        $expenseCategory->update([
            'is_active' => !$expenseCategory->is_active,
        ]);

        $statusStr = $expenseCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Kategori '{$expenseCategory->name}' berhasil {$statusStr}.");
    }

    public function getActiveCategories()
    {
        return response()->json(
            ExpenseCategory::active()->orderBy('name')->get(['id', 'code', 'name', 'color'])
        );
    }

    protected function seedDefaults(): void
    {
        $defaults = [
            ['code' => 'OPR', 'name' => 'Operasional Lapangan', 'description' => 'BBM, konsumsi teknisi, transportasi, parkir, tol', 'color' => 'amber'],
            ['code' => 'MNT', 'name' => 'Maintenance Jaringan', 'description' => 'Perbaikan FO cut, penggantian adaptor/dropcore/fast connector', 'color' => 'rose'],
            ['code' => 'BW',  'name' => 'Bandwidth & Upstream', 'description' => 'Biaya langganan internet upstream, IP Transit, colocation server', 'color' => 'indigo'],
            ['code' => 'PLN', 'name' => 'Listrik & Utilitas POP', 'description' => 'Token PLN OLT, genset, baterai UPS, sewa tiang/tempat POP', 'color' => 'emerald'],
            ['code' => 'TLS', 'name' => 'Peralatan & Perkakas Kerja', 'description' => 'Splicer, OTDR, tang potong, tangga, alat safety, stripper', 'color' => 'blue'],
            ['code' => 'MKT', 'name' => 'Marketing & Promosi', 'description' => 'Cetak brosur, banner, spanduk, merchandise, iklan medsos', 'color' => 'purple'],
            ['code' => 'ADM', 'name' => 'Administrasi & Kantor', 'description' => 'Kertas invoice, printer, ATK, operasional kantor', 'color' => 'cyan'],
            ['code' => 'OTH', 'name' => 'Pengeluaran Lainnya', 'description' => 'Pengeluaran umum lain yang tidak termasuk kategori di atas', 'color' => 'gray'],
        ];

        foreach ($defaults as $item) {
            ExpenseCategory::create(array_merge($item, ['is_active' => true]));
        }
    }
}
