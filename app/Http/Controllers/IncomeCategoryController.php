<?php

namespace App\Http\Controllers;

use App\Models\IncomeCategory;
use App\Models\Income;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncomeCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (IncomeCategory::count() === 0) {
            $this->seedDefaults();
        }

        $search = trim($request->query('search', ''));

        $categories = IncomeCategory::query()
            ->withCount('incomes')
            ->withSum('incomes', 'amount')
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
            'total_categories' => IncomeCategory::count(),
            'active_categories' => IncomeCategory::where('is_active', true)->count(),
            'total_income_amount' => (float) Income::sum('amount'),
        ];

        return Inertia::render('Settings/IncomeCategories', [
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

        $created = IncomeCategory::create([
            'name' => $validated['name'],
            'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? 'emerald',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Kategori pemasukan '{$created->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $incomeCategory = IncomeCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $incomeCategory->update([
            'name' => $validated['name'],
            'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? $incomeCategory->color,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $incomeCategory->is_active,
        ]);

        return back()->with('success', "Kategori pemasukan '{$incomeCategory->name}' berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $incomeCategory = IncomeCategory::findOrFail($id);
        $name = $incomeCategory->name;
        $incomesCount = $incomeCategory->incomes()->count();

        if ($incomesCount > 0) {
            $incomeCategory->incomes()->update(['income_category_id' => null]);
        }

        $incomeCategory->delete();

        $info = $incomesCount > 0 ? " ({$incomesCount} transaksi terkait kini tidak berkategori)" : "";
        return back()->with('success', "Kategori pemasukan '{$name}' berhasil dihapus{$info}.");
    }

    public function toggleStatus($id)
    {
        $incomeCategory = IncomeCategory::findOrFail($id);
        $incomeCategory->update([
            'is_active' => !$incomeCategory->is_active,
        ]);

        $statusStr = $incomeCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Kategori '{$incomeCategory->name}' berhasil {$statusStr}.");
    }

    public function getActiveCategories()
    {
        return response()->json(
            IncomeCategory::active()->orderBy('name')->get(['id', 'code', 'name', 'color'])
        );
    }

    protected function seedDefaults(): void
    {
        $defaults = [
            ['code' => 'JSA', 'name' => 'Jasa Setting & Instalasi Khusus', 'description' => 'Penarikan kabel FO khusus, setting router/mikrotik, instalasi CCTV & hotspot', 'color' => 'emerald'],
            ['code' => 'BRG', 'name' => 'Penjualan Perangkat & Material', 'description' => 'Penjualan ONT/Router bekas atau baru, kabel sisa, patchcord, adaptor', 'color' => 'blue'],
            ['code' => 'SEW', 'name' => 'Sewa Tiang & Infrastruktur FO', 'description' => 'Sewa tiang bersama, sewa core fiber optik, colocation rack', 'color' => 'indigo'],
            ['code' => 'VCH', 'name' => 'Voucher Grosir & Offline', 'description' => 'Penjualan voucher fisik titip toko / warung / mitra reseller', 'color' => 'purple'],
            ['code' => 'KOM', 'name' => 'Komisi & Fee Mitra', 'description' => 'Komisi mitra, cashback vendor/supplier, bagi hasil kemitraan', 'color' => 'amber'],
            ['code' => 'REF', 'name' => 'Pengembalian / Refund / Kasbon', 'description' => 'Pengembalian dana vendor, setoran pelunasan kasbon karyawan/teknisi', 'color' => 'cyan'],
            ['code' => 'INV', 'name' => 'Modal Masuk & Setoran Kas', 'description' => 'Suntikan modal owner, setoran kas awal, investasi', 'color' => 'teal'],
            ['code' => 'OTH', 'name' => 'Pemasukan Lain-lain', 'description' => 'Pemasukan operasional dan non-operasional lainnya di luar tagihan', 'color' => 'gray'],
        ];

        foreach ($defaults as $item) {
            IncomeCategory::create(array_merge($item, ['is_active' => true]));
        }
    }
}

