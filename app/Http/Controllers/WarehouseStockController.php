<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('warehouse_stocks_view_all') || $user->can('materials_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        $selectedAreaId = $request->filled('area_id') ? $request->area_id : null;

        // Tampilkan barang yang aktif dan memenuhi kriteria:
        // - Barang migrasi (ID <= 260): Tetap tampil karena merupakan data material yang sudah terpasang di pelanggan
        // - Barang katalog baru (ID > 260): HANYA tampil jika sudah pernah di-order toko (restock) ke area cabang
        $query = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })
        ->visibleInMaterialsTable()
        ->with(['stocks' => function ($sq) use ($accessibleAreas, $isGlobal) {
            if (!$isGlobal && !empty($accessibleAreas)) {
                $sq->whereIn('area_id', $accessibleAreas);
            }
            $sq->with('area');
        }]);

        // 1. Scope restriction & Area filter
        if (!$isGlobal && !empty($accessibleAreas)) {
            $query->whereHas('stocks', function ($sq) use ($accessibleAreas, $selectedAreaId) {
                if ($selectedAreaId) {
                    $sq->where('area_id', $selectedAreaId);
                } else {
                    $sq->whereIn('area_id', $accessibleAreas);
                }
            });
            $areas = Area::whereIn('id', $accessibleAreas)->orderBy('name')->get(['id', 'name']);
        } else {
            // Filter Area Gudang jika dipilih, atau pastikan barang memiliki alokasi stok di area
            if ($selectedAreaId) {
                $query->whereHas('stocks', function ($q) use ($selectedAreaId) {
                    $q->where('area_id', $selectedAreaId);
                });
            } else {
                $query->whereHas('stocks');
            }
            $areas = Area::orderBy('name')->get(['id', 'name']);
        }

        // 2. Pencarian Cerdas (Nama, Kategori, Supplier, Satuan, Keterangan)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 3. Filter Kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // 4. Filter Satuan (Unit)
        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        $scopedAreaIds = $selectedAreaId ? [$selectedAreaId] : (!$isGlobal && !empty($accessibleAreas) ? $accessibleAreas : null);

        // 5. Filter Status Stok
        if ($request->filled('stock_status')) {
            if ($scopedAreaIds) {
                if ($request->stock_status === 'out_of_stock') {
                    $query->whereDoesntHave('stocks', function ($sq) use ($scopedAreaIds) {
                        $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0);
                    });
                } elseif ($request->stock_status === 'available') {
                    $query->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                        $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0);
                    });
                } elseif ($request->stock_status === 'low_stock') {
                    $query->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                        $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0)->where('stock', '<=', 20);
                    });
                }
            } else {
                if ($request->stock_status === 'out_of_stock') {
                    $query->whereDoesntHave('stocks', function ($sq) {
                        $sq->where('stock', '>', 0);
                    });
                } elseif ($request->stock_status === 'available') {
                    $query->whereHas('stocks', function ($sq) {
                        $sq->where('stock', '>', 0);
                    });
                } elseif ($request->stock_status === 'low_stock') {
                    $query->whereHas('stocks', function ($sq) {
                        $sq->where('stock', '>', 0)->where('stock', '<=', 20);
                    });
                }
            }
        }

        // 6. Pengurutan (Sorting)
        $sortBy = $request->get('sort_by', 'name_asc');
        switch ($sortBy) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'category':
                $query->orderBy('category')->orderBy('name');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $materials = $query->paginate(15)->withQueryString();

        $baseSummaryQuery = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->visibleInMaterialsTable();

        $categories = (clone $baseSummaryQuery)->whereHas('stocks')->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->sort()->values();
        $units = (clone $baseSummaryQuery)->whereHas('stocks')->whereNotNull('unit')->where('unit', '!=', '')->distinct()->pluck('unit')->sort()->values();

        // Summary counts (Hanya hitung material yang masuk kriteria area warehouse)
        if ($scopedAreaIds) {
            $totalMaterials = (clone $baseSummaryQuery)->whereHas('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds))->count();
            $outOfStockCount = (clone $baseSummaryQuery)->whereHas('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds))
                ->whereDoesntHave('stocks', function ($sq) use ($scopedAreaIds) {
                    $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0);
                })->count();
            $availableCount = (clone $baseSummaryQuery)->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0);
            })->count();
        } else {
            $totalMaterials = (clone $baseSummaryQuery)->whereHas('stocks')->count();
            $outOfStockCount = (clone $baseSummaryQuery)->whereHas('stocks')
                ->whereDoesntHave('stocks', function ($sq) {
                    $sq->where('stock', '>', 0);
                })->count();
            $availableCount = (clone $baseSummaryQuery)->whereHas('stocks', function ($sq) {
                $sq->where('stock', '>', 0);
            })->count();
        }

        return Inertia::render('Inventory/Warehouse/Index', [
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'areas' => $areas,
            'filters' => $request->only(['search', 'category', 'unit', 'area_id', 'stock_status', 'sort_by']),
            'summary' => [
                'total_materials' => $totalMaterials,
                'out_of_stock' => $outOfStockCount,
                'available' => $availableCount,
                'filtered_count' => $materials->total(),
            ]
        ]);
    }

    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $material = Material::findOrFail($id);

                // Unlink ONTs from material transaction items if any
                $itemIds = \App\Models\MaterialTransactionItem::where('material_id', $id)->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    \App\Models\Ont::whereIn('material_transaction_item_id', $itemIds)->update(['material_transaction_item_id' => null]);
                    \App\Models\MaterialTransactionItem::whereIn('id', $itemIds)->delete();
                }

                // Hapus stok per area
                $material->stocks()->delete();

                // Hapus material
                $material->delete();
            });

            return redirect()->back()->with('success', 'Barang berhasil dihapus dari inventory.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
    }
}
