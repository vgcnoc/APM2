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
        $scopedAreaIds = $selectedAreaId ? [$selectedAreaId] : (!$isGlobal && !empty($accessibleAreas) ? $accessibleAreas : null);

        // Filter material gudang yang relevan:
        // - Barang katalog (ID > 260 dan bukan migrasi): Tampil jika ada alokasi di area
        // - Barang migrasi (ID <= 260 atau is_migration = true): HANYA tampil jika BENAR-BENAR memiliki sisa stok fisik di gudang (> 0)
        $warehouseFilter = function ($q) use ($scopedAreaIds) {
            $q->where(function ($sub) use ($scopedAreaIds) {
                $sub->where(function ($catQ) {
                    $catQ->where('id', '>', 260)
                         ->where(function ($mq) {
                             $mq->where('is_migration', false)->orWhereNull('is_migration');
                         });
                })
                ->orWhere(function ($migQ) use ($scopedAreaIds) {
                    $migQ->where(function ($mq) {
                        $mq->where('id', '<=', 260)->orWhere('is_migration', true);
                    })
                    ->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                        if ($scopedAreaIds) {
                            $sq->whereIn('area_id', $scopedAreaIds);
                        }
                        $sq->where('stock', '>', 0);
                    });
                });
            });
        };

        // Tampilkan barang yang aktif dan memenuhi kriteria gudang:
        $query = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })
        ->visibleInMaterialsTable()
        ->where($warehouseFilter)
        ->with(['transactionItems:id,material_id,condition', 'stocks' => function ($sq) use ($accessibleAreas, $isGlobal) {
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

        // 6. Filter Kondisi Barang (Layak, Tidak Layak, Rusak)
        if ($request->filled('condition')) {
            $cond = $request->condition;
            if ($cond === 'layak') {
                $query->where(function ($kq) {
                    $kq->where('name', 'not like', '%rusak%')
                       ->where('name', 'not like', '%afkir%')
                       ->where('name', 'not like', '%tidak layak%')
                       ->where(function ($dq) {
                           $dq->whereNull('description')
                              ->orWhere(function ($ddq) {
                                  $ddq->where('description', 'not like', '%rusak%')
                                      ->where('description', 'not like', '%tidak layak%');
                              });
                       });
                });
            } elseif ($cond === 'tidak_layak') {
                $query->where(function ($sq) {
                    $sq->where('name', 'like', '%tidak layak%')
                       ->orWhere('description', 'like', '%tidak layak%');
                });
            } elseif ($cond === 'rusak') {
                $query->where(function ($sq) {
                    $sq->where('name', 'like', '%rusak%')
                       ->orWhere('name', 'like', '%afkir%')
                       ->orWhere('name', 'like', '%scrap%')
                       ->orWhere('description', 'like', '%rusak%')
                       ->orWhere('description', 'like', '%afkir%')
                       ->orWhere('description', 'like', '%scrap%');
                });
            }
        }

        // 7. Pengurutan (Sorting)
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

        $materials->getCollection()->transform(function ($material) {
            $nameLower = strtolower($material->name . ' ' . ($material->description ?? ''));
            if (str_contains($nameLower, 'rusak') || str_contains($nameLower, 'afkir') || str_contains($nameLower, 'scrap')) {
                $material->condition_label = 'Rusak';
            } elseif (str_contains($nameLower, 'tidak layak')) {
                $material->condition_label = 'Tidak Layak';
            } else {
                $material->condition_label = 'Layak Pakai';
            }
            return $material;
        });

        $baseSummaryQuery = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->visibleInMaterialsTable()
        ->where($warehouseFilter);

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

        $baseCondQuery = (clone $baseSummaryQuery)->when($scopedAreaIds, fn($q) => $q->whereHas('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds)), fn($q) => $q->whereHas('stocks'));
        
        $conditionStats = [
            'all' => (clone $baseCondQuery)->count(),
            'layak' => (clone $baseCondQuery)->where(function ($kq) {
                $kq->where('name', 'not like', '%rusak%')
                   ->where('name', 'not like', '%afkir%')
                   ->where('name', 'not like', '%tidak layak%')
                   ->where(function ($dq) {
                       $dq->whereNull('description')
                          ->orWhere(function ($ddq) {
                              $ddq->where('description', 'not like', '%rusak%')
                                  ->where('description', 'not like', '%tidak layak%');
                          });
                   });
            })->count(),
            'tidak_layak' => (clone $baseCondQuery)->where(function ($sq) {
                $sq->where('name', 'like', '%tidak layak%')
                   ->orWhere('description', 'like', '%tidak layak%');
            })->count(),
            'rusak' => (clone $baseCondQuery)->where(function ($sq) {
                $sq->where('name', 'like', '%rusak%')
                   ->orWhere('name', 'like', '%afkir%')
                   ->orWhere('name', 'like', '%scrap%')
                   ->orWhere('description', 'like', '%rusak%')
                   ->orWhere('description', 'like', '%afkir%')
                   ->orWhere('description', 'like', '%scrap%');
            })->count(),
        ];

        return Inertia::render('Inventory/Warehouse/Index', [
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'areas' => $areas,
            'filters' => $request->only(['search', 'category', 'unit', 'area_id', 'stock_status', 'sort_by', 'condition']),
            'summary' => [
                'total_materials' => $totalMaterials,
                'out_of_stock' => $outOfStockCount,
                'available' => $availableCount,
                'filtered_count' => $materials->total(),
                'condition_stats' => $conditionStats,
            ]
        ]);
    }

    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $material = Material::findOrFail($id);

                $itemIds = \App\Models\MaterialTransactionItem::where('material_id', $id)->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    // Cari apakah ada material aktif standar lain dengan nama serupa / kanonikal
                    $replacement = \App\Services\MaterialService::findByName($material->name, $material->id);
                    if (!$replacement) {
                        $normalizedName = trim(preg_replace('/^test\s+/i', '', trim($material->name)));
                        $replacement = Material::where('id', '!=', $material->id)
                            ->where('id', '>', 260)
                            ->where(function ($q) {
                                $q->where('is_active', true)->orWhereNull('is_active');
                            })
                            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalizedName)])
                            ->first();
                    }

                    if ($replacement) {
                        // Gabungkan secara menyeluruh ke material pengganti (foreign keys, stok area, ledger transaksi)
                        \App\Services\MaterialService::mergeMaterials($replacement, $material);
                    } else {
                        // JANGAN hapus MaterialTransactionItem agar riwayat pemakaian instalasi pelanggan tidak hilang!
                        // Cukup nonaktifkan dan kosongkan stok.
                        $material->update([
                            'is_active' => false,
                            'stock' => 0,
                            'total_rolls' => 0,
                            'total_packs' => 0,
                            'total_pieces' => 0,
                        ]);
                        $material->stocks()->update([
                            'stock' => 0,
                            'total_rolls' => 0,
                            'total_packs' => 0,
                            'total_pieces' => 0,
                        ]);
                    }
                } else {
                    // Tidak ada histori transaksi, aman dihapus permanen
                    $material->stocks()->delete();
                    $material->delete();
                }
            });

            return redirect()->back()->with('success', 'Barang berhasil dihapus dari inventory.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
    }
}
