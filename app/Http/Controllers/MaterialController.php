<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\DataScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('materials_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        $query = Material::query();

        // Scope relationship & filtering
        if (!$isGlobal && !empty($accessibleAreas)) {
            $query->with(['stocks' => function ($sq) use ($accessibleAreas, $request) {
                if ($request->filled('area_id')) {
                    $sq->where('area_id', $request->area_id);
                } else {
                    $sq->whereIn('area_id', $accessibleAreas);
                }
                $sq->with('area');
            }]);

            if ($request->filled('area_id')) {
                $query->whereHas('stocks', function ($sq) use ($request) {
                    $sq->where('area_id', $request->area_id);
                });
            }
        } else {
            $query->with(['stocks' => function ($sq) use ($request) {
                if ($request->filled('area_id')) {
                    $sq->where('area_id', $request->area_id);
                }
                $sq->with('area');
            }]);

            if ($request->filled('area_id')) {
                $query->whereHas('stocks', function ($sq) use ($request) {
                    $sq->where('area_id', $request->area_id);
                });
            }
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%")
                  ->orWhere('supplier', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $scopedAreaIds = $request->filled('area_id') ? [$request->area_id] : (!$isGlobal && !empty($accessibleAreas) ? $accessibleAreas : null);

        if ($request->stock_status) {
            if ($scopedAreaIds) {
                if ($request->stock_status === 'empty') {
                    $query->where(function ($q) use ($scopedAreaIds) {
                        $q->whereDoesntHave('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0));
                    });
                } elseif ($request->stock_status === 'low') {
                    $query->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                        $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0)->where('stock', '<=', 10);
                    });
                } elseif ($request->stock_status === 'available') {
                    $query->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                        $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 10);
                    });
                }
            } else {
                if ($request->stock_status === 'empty') {
                    $query->where('stock', '<=', 0);
                } elseif ($request->stock_status === 'low') {
                    $query->where('stock', '>', 0)->where('stock', '<=', 10);
                } elseif ($request->stock_status === 'available') {
                    $query->where('stock', '>', 10);
                }
            }
        }

        $materials = $query->latest('id')->paginate(10)->withQueryString();

        if ($scopedAreaIds) {
            $materials->getCollection()->transform(function ($material) {
                $material->scoped_stock = (float) $material->stocks->sum('stock');
                $material->scoped_initial_stock = (float) $material->stocks->sum('initial_stock');
                return $material;
            });
        }

        $areas = $isGlobal 
            ? \App\Models\Area::orderBy('name')->get(['id', 'name'])
            : \App\Models\Area::whereIn('id', $accessibleAreas)->orderBy('name')->get(['id', 'name']);
        
        // Summary Stats (Scoped to area if restricted or selected)
        if ($scopedAreaIds) {
            $stockQuery = \App\Models\MaterialStock::whereIn('area_id', $scopedAreaIds);
            
            $totalItems = (clone $stockQuery)->where('stock', '>', 0)->distinct('material_id')->count('material_id');
            $outOfStock = Material::whereDoesntHave('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0))->count();
            $lowStock = (clone $stockQuery)->where('stock', '>', 0)->where('stock', '<=', 10)->distinct('material_id')->count('material_id');
            
            $totalValue = (float) \Illuminate\Support\Facades\DB::table('material_stocks')
                ->join('materials', 'material_stocks.material_id', '=', 'materials.id')
                ->whereIn('material_stocks.area_id', $scopedAreaIds)
                ->sum(\Illuminate\Support\Facades\DB::raw('material_stocks.stock * COALESCE(materials.price_per_unit, 0)'));

            $summary = [
                'total_items' => $totalItems,
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
                'total_value' => $totalValue,
            ];
        } else {
            $summary = [
                'total_items' => Material::count(),
                'out_of_stock' => Material::where('stock', '<=', 0)->count(),
                'low_stock' => Material::where('stock', '>', 0)->where('stock', '<=', 10)->count(),
                'total_value' => (float) Material::sum(\Illuminate\Support\Facades\DB::raw('stock * COALESCE(price_per_unit, 0)')),
            ];
        }
        
        $categories = Material::select('category')->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'areas' => $areas,
            'categories' => $categories,
            'summary' => $summary,
            'filters' => $request->only(['search', 'category', 'stock_status', 'area_id'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'total_rolls' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'total_packs' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['initial_stock'] = $validated['stock'];

        Material::create($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'total_rolls' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'total_packs' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        try {
            $material->delete();
            return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == "23000") {
                return redirect()->route('materials.index')->with('error', 'Data material tidak dapat dihapus karena sudah digunakan dalam Riwayat Order/Pengambilan.');
            }
            return redirect()->route('materials.index')->with('error', 'Terjadi kesalahan saat menghapus data material.');
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:materials,id'
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            Material::whereIn('id', $request->ids)->delete();
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('materials.index')->with('success', count($request->ids) . ' data material berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            if ($e->getCode() == "23000") {
                return redirect()->route('materials.index')->with('error', 'Beberapa material tidak dapat dihapus karena sudah digunakan dalam Riwayat Order/Pengambilan.');
            }
            return redirect()->route('materials.index')->with('error', 'Terjadi kesalahan saat menghapus data material.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('materials.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function addStock(Request $request, Material $material)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'added_stock' => 'required|numeric|min:0.01',
            'added_rolls' => 'nullable|numeric|min:0',
            'added_packs' => 'nullable|numeric|min:0',
            'added_pieces' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('materials_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && !in_array((int)$validated['area_id'], array_map('intval', $accessibleAreas))) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menambah stok pada area ini.');
        }

        // Update global stock
        $material->stock += $validated['added_stock'];
        $material->initial_stock += $validated['added_stock'];

        // Update Area stock
        $materialStock = \App\Models\MaterialStock::firstOrCreate(
            ['material_id' => $material->id, 'area_id' => $validated['area_id']],
            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
        );
        $materialStock->stock += $validated['added_stock'];
        $materialStock->initial_stock += $validated['added_stock'];

        if (str_contains(strtolower($material->category), 'kabel') && !empty($validated['added_rolls'])) {
            $material->total_rolls += $validated['added_rolls'];
            $materialStock->total_rolls += $validated['added_rolls'];
        }

        if ($material->category === 'Paku Klem' && !empty($validated['added_packs'])) {
            $material->total_packs += $validated['added_packs'];
            $materialStock->total_packs += $validated['added_packs'];
        }

        if ($material->category === 'Isolasi' && !empty($validated['added_pieces'])) {
            $material->total_pieces += $validated['added_pieces'];
            $materialStock->total_pieces += $validated['added_pieces'];
        }
        
        $materialStock->save();

        if (isset($validated['price_per_unit'])) {
            $material->price_per_unit = $validated['price_per_unit'];
        }
        
        if (isset($validated['selling_price'])) {
            $material->selling_price = $validated['selling_price'];
        }

        $material->save();

        // Rekam ke Log Mutasi (MaterialTransaction & MaterialTransactionItem)
        $transactionIn = \App\Models\MaterialTransaction::create([
            'transaction_number' => 'IN-RESTOCK-' . date('YmdHis'),
            'type' => 'in',
            'status' => 'approved',
            'date' => now(),
            'technician_name' => auth()->user()->name,
            'purpose' => 'Stok Masuk / Restock Gudang',
            'user_id' => auth()->id(),
            'area_id' => $validated['area_id'],
            'notes' => 'Pemasukan stok baru via menu Material'
        ]);

        \App\Models\MaterialTransactionItem::create([
            'material_transaction_id' => $transactionIn->id,
            'material_id' => $material->id,
            'quantity' => $validated['added_stock'],
            'unit' => $material->unit,
            'price_per_unit' => $material->price_per_unit ?? 0,
            'total_price' => ($material->price_per_unit ?? 0) * $validated['added_stock'],
            'stock_before' => $materialStock->stock - $validated['added_stock'],
            'stock_after' => $materialStock->stock,
            'condition' => 'Layak Pakai'
        ]);

        return redirect()->route('materials.index')->with('success', "Berhasil menambahkan stok masuk untuk {$material->name}.");
    }
}
