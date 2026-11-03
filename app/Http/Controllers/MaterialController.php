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

        $query = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->visibleInMaterialsTable();

        // Scope relationship & filtering
        if (!$isGlobal && !empty($accessibleAreas)) {
            $query->with(['transactionItems:id,material_id,condition', 'stocks' => function ($sq) use ($accessibleAreas, $request) {
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
            $query->with(['transactionItems:id,material_id,condition', 'stocks' => function ($sq) use ($request) {
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
                $effectiveStockSql = 'COALESCE((SELECT SUM(stock) FROM material_stocks WHERE material_stocks.material_id = materials.id), materials.stock)';
                if ($request->stock_status === 'empty') {
                    $query->whereRaw("{$effectiveStockSql} <= 0");
                } elseif ($request->stock_status === 'low') {
                    $query->whereRaw("{$effectiveStockSql} > 0 AND {$effectiveStockSql} <= 10");
                } elseif ($request->stock_status === 'available') {
                    $query->whereRaw("{$effectiveStockSql} > 10");
                }
            }
        }

        // Filter Kondisi Barang (Layak, Tidak Layak, Rusak)
        if ($request->filled('condition')) {
            $cond = $request->condition;
            if ($cond === 'layak') {
                $query->where(function ($kq) {
                    $kq->where('name', 'not like', '%rusak%')
                       ->where('name', 'not like', '%afkir%')
                       ->where('name', 'not like', '%scrap%')
                       ->where('name', 'not like', '%tidak layak%')
                       ->where(function ($dq) {
                           $dq->whereNull('description')
                              ->orWhere(function ($ddq) {
                                  $ddq->where('description', 'not like', '%rusak%')
                                      ->where('description', 'not like', '%afkir%')
                                      ->where('description', 'not like', '%scrap%')
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
                       ->where('name', 'like', '%afkir%')
                       ->where('name', 'like', '%scrap%')
                       ->orWhere('description', 'like', '%rusak%')
                       ->orWhere('description', 'like', '%afkir%')
                       ->orWhere('description', 'like', '%scrap%');
                });
            }
        }

        $materials = $query->latest('id')->paginate(10)->withQueryString();

        $materials->getCollection()->transform(function ($material) use ($scopedAreaIds) {
            if ($scopedAreaIds) {
                $material->scoped_stock = (float) $material->stocks->sum('stock');
                $material->scoped_initial_stock = (float) $material->stocks->sum('initial_stock');
            } else {
                $material->scoped_stock = $material->stocks->isNotEmpty() 
                    ? (float) $material->stocks->sum('stock') 
                    : (float) $material->stock;
                $material->scoped_initial_stock = $material->stocks->isNotEmpty() 
                    ? (float) $material->stocks->sum('initial_stock') 
                    : (float) ($material->initial_stock ?? 0);
            }

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

        $areas = $isGlobal 
            ? \App\Models\Area::orderBy('name')->get(['id', 'name'])
            : \App\Models\Area::whereIn('id', $accessibleAreas)->orderBy('name')->get(['id', 'name']);
        
        // Summary Stats: hanya menghitung material yang masih ada dan aktif dengan konversi unit yang tepat
        $summary = $this->calculateMaterialSummary($scopedAreaIds);

        $baseCondQuery = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->visibleInMaterialsTable();

        if ($scopedAreaIds) {
            $baseCondQuery->whereHas('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds));
        }

        $summary['condition_stats'] = [
            'all' => (clone $baseCondQuery)->count(),
            'layak' => (clone $baseCondQuery)->where(function ($kq) {
                $kq->where('name', 'not like', '%rusak%')
                   ->where('name', 'not like', '%afkir%')
                   ->where('name', 'not like', '%scrap%')
                   ->where('name', 'not like', '%tidak layak%')
                   ->where(function ($dq) {
                       $dq->whereNull('description')
                          ->orWhere(function ($ddq) {
                              $ddq->where('description', 'not like', '%rusak%')
                                  ->where('description', 'not like', '%afkir%')
                                  ->where('description', 'not like', '%scrap%')
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
                   ->where('name', 'like', '%afkir%')
                   ->where('name', 'like', '%scrap%')
                   ->orWhere('description', 'like', '%rusak%')
                   ->orWhere('description', 'like', '%afkir%')
                   ->orWhere('description', 'like', '%scrap%');
            })->count(),
        ];
        
        $dbCategories = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->toArray();
        $defaultCategories = ['Umum', 'Kabel', 'Patchcord', 'Klem', 'Isolasi', 'ONT', 'Splitter', 'Aksesoris', 'Peralatan'];
        $categories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));
        sort($categories);

        $catalogMaterials = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->select([
                'id',
                'name',
                'category',
                'unit',
                'supplier',
                'meter_per_roll',
                'pcs_per_pack',
                'cm_per_pcs',
                'requires_sn',
                'requires_mac'
            ])
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'areas' => $areas,
            'categories' => $categories,
            'catalogMaterials' => $catalogMaterials,
            'summary' => $summary,
            'filters' => $request->only(['search', 'category', 'stock_status', 'area_id', 'condition'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'total_rolls' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'total_packs' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'area_id' => 'nullable|exists:areas,id',
            'requires_sn' => 'nullable|boolean',
            'requires_mac' => 'nullable|boolean',
        ]);

        $areaId = $validated['area_id'] ?? null;
        unset($validated['area_id']);

        $validated['price_per_unit'] = $validated['price_per_unit'] ?? 0;
        $validated['selling_price'] = $validated['selling_price'] ?? 0;
        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['initial_stock'] = $validated['initial_stock'] ?? $validated['stock'];
        $validated['is_migration'] = true; // Tandai sebagai material fisik area (bukan master katalog toko)
        $validated['is_active'] = true;

        // Cek jika sudah ada material dengan nama yang sama, lakukan penambahan stok / penggabungan
        $existing = \App\Services\MaterialService::findByName($validated['name']);
        if ($existing) {
            \App\Services\MaterialService::addStockToExistingMaterial($existing, $validated, $areaId);
            return redirect()->route('materials.index')->with('success', 'Data material dengan nama tersebut sudah ada. Stok berhasil digabungkan ke material tersebut.');
        }

        $material = Material::create($validated);

        if ($areaId) {
            \App\Models\MaterialStock::create([
                'material_id' => $material->id,
                'area_id' => $areaId,
                'stock' => $material->stock,
                'initial_stock' => $material->initial_stock,
                'total_rolls' => $material->total_rolls ?? 0,
                'total_packs' => $material->total_packs ?? 0,
                'total_pieces' => $material->total_pieces ?? 0,
            ]);

            if ((float)$material->stock > 0) {
                // Catat transaksi mutasi awal ke log
                $transaction = \App\Models\MaterialTransaction::create([
                    'transaction_number' => 'IN-AREA-' . date('YmdHis') . '-' . $material->id,
                    'type' => 'in',
                    'status' => 'approved',
                    'date' => now(),
                    'technician_name' => auth()->user() ? auth()->user()->name : 'Admin',
                    'purpose' => 'Pencatatan Material Fisik Area',
                    'user_id' => auth()->id(),
                    'area_id' => $areaId,
                    'notes' => 'Pencatatan stok material fisik di area (non-toko)'
                ]);

                \App\Models\MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $material->stock,
                    'unit' => $material->unit,
                    'price_per_unit' => 0,
                    'total_price' => 0,
                    'stock_before' => 0,
                    'stock_after' => $material->stock,
                    'condition' => 'Layak Pakai'
                ]);
            }
        }

        return redirect()->route('materials.index')->with('success', 'Data material area berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'meter_per_roll' => 'nullable|numeric|min:0',
            'total_rolls' => 'nullable|numeric|min:0',
            'pcs_per_pack' => 'nullable|numeric|min:0',
            'total_packs' => 'nullable|numeric|min:0',
            'cm_per_pcs' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'area_id' => 'nullable|exists:areas,id',
            'requires_sn' => 'nullable|boolean',
            'requires_mac' => 'nullable|boolean',
        ]);

        $areaId = $validated['area_id'] ?? null;
        unset($validated['area_id']);

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            unset($validated['stock']);
        }

        // Cek jika nama diubah menimpa material lain yang sudah ada sebelumnya
        $existing = \App\Services\MaterialService::findByName($validated['name'], $material->id);
        if ($existing) {
            \App\Services\MaterialService::mergeMaterials($existing, $material, $validated, $areaId, $validated['stock'] ?? null);
            return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui dan menimpa data material sebelumnya.');
        }

        $oldName = $material->name;
        $material->update($validated);

        if ($oldName && $oldName !== $material->name) {
            \App\Models\Ont::where('brand', $oldName)->update(['brand' => $material->name]);
            \App\Models\MaterialTransactionItem::where('material_id', $material->id)->update(['material_name' => $material->name]);
        }

        if ($areaId) {
            $existingStocks = \App\Models\MaterialStock::where('material_id', $material->id)->get();
            if ($existingStocks->count() <= 1) {
                \App\Models\MaterialStock::updateOrCreate(
                    ['material_id' => $material->id],
                    [
                        'area_id' => $areaId,
                        'stock' => $material->stock,
                        'initial_stock' => $material->initial_stock ?? $material->stock,
                        'total_rolls' => $material->total_rolls ?? 0,
                        'total_packs' => $material->total_packs ?? 0,
                        'total_pieces' => $material->total_pieces ?? 0,
                    ]
                );
            } else {
                \App\Models\MaterialStock::updateOrCreate(
                    ['material_id' => $material->id, 'area_id' => $areaId],
                    [
                        'stock' => $material->stock,
                        'initial_stock' => $material->initial_stock ?? $material->stock,
                        'total_rolls' => $material->total_rolls ?? 0,
                        'total_packs' => $material->total_packs ?? 0,
                        'total_pieces' => $material->total_pieces ?? 0,
                    ]
                );
            }
        }

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $hasTransactions = \App\Models\MaterialTransactionItem::where('material_id', $material->id)->exists();
            if ($hasTransactions) {
                // Soft delete via is_active = false dan kosongkan stok agar tidak muncul & tidak terhitung
                $material->update([
                    'is_active' => false,
                    'stock' => 0
                ]);
                \App\Models\MaterialStock::where('material_id', $material->id)->update(['stock' => 0]);
            } else {
                \App\Models\MaterialStock::where('material_id', $material->id)->delete();
                $material->delete();
            }
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('materials.index')->with('success', 'Data material berhasil dihapus.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            // Fallback: nonaktifkan dan kosongkan stok
            $material->update(['is_active' => false, 'stock' => 0]);
            \App\Models\MaterialStock::where('material_id', $material->id)->update(['stock' => 0]);
            return redirect()->route('materials.index')->with('success', 'Data material berhasil dinonaktifkan/dihapus dari sistem.');
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
            foreach ($request->ids as $id) {
                $mat = Material::find($id);
                if (!$mat) continue;
                $hasTransactions = \App\Models\MaterialTransactionItem::where('material_id', $id)->exists();
                if ($hasTransactions) {
                    $mat->update(['is_active' => false, 'stock' => 0]);
                    \App\Models\MaterialStock::where('material_id', $id)->update(['stock' => 0]);
                } else {
                    \App\Models\MaterialStock::where('material_id', $id)->delete();
                    $mat->delete();
                }
            }
            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('materials.index')->with('success', count($request->ids) . ' data material berhasil dihapus.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('materials.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Hitung ringkasan statistik material (hanya yang aktif/tidak dihapus dan nilai aset dari Order Toko per Area).
     */
    protected function calculateMaterialSummary($scopedAreaIds = null): array
    {
        $activeQuery = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->visibleInMaterialsTable();

        if ($scopedAreaIds) {
            $stockQuery = \App\Models\MaterialStock::whereIn('area_id', $scopedAreaIds)
                ->whereHas('material', function ($mq) {
                    $mq->where(function ($q) {
                        $q->where('is_active', true)->orWhereNull('is_active');
                    })->visibleInMaterialsTable();
                });
            
            $totalItems = (clone $stockQuery)->where('stock', '>', 0)->distinct('material_id')->count('material_id');
            $outOfStock = (clone $activeQuery)->whereDoesntHave('stocks', fn($sq) => $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0))->count();
            $lowStock = (clone $stockQuery)->where('stock', '>', 0)->where('stock', '<=', 10)->distinct('material_id')->count('material_id');
            
            // Hitung nilai aset dari Order Toko per Area
            $areaNames = \App\Models\Area::whereIn('id', $scopedAreaIds)->pluck('name')->toArray();
            
            $poTrxIds = \App\Models\MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')
                ->where(function ($q) use ($scopedAreaIds, $areaNames) {
                    $q->whereIn('area_id', $scopedAreaIds);
                    if (!empty($areaNames)) {
                        $q->orWhereIn('area', $areaNames)
                          ->orWhereIn('technician_name', $areaNames);
                    }
                })
                ->pluck('id');

            $totalValue = (float) \App\Models\MaterialTransactionItem::whereIn('material_transaction_id', $poTrxIds)
                ->whereHas('material', function ($mq) {
                    $mq->where('is_active', true)->orWhereNull('is_active');
                })
                ->sum('total_price');

            if ($totalValue <= 0) {
                $totalValue = (float) \App\Models\MaterialTransaction::whereIn('id', $poTrxIds)->sum('total_cost');
            }

            return [
                'total_items' => $totalItems,
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
                'total_value' => round($totalValue),
            ];
        }

        // Mode Global / Gudang Utama (Semua Gudang / Area)
        $activeMaterials = (clone $activeQuery)->with('stocks')->get();
        $totalItems = $activeMaterials->count();
        $outOfStock = $activeMaterials->filter(function ($m) {
            $effStock = $m->stocks->isNotEmpty() ? (float) $m->stocks->sum('stock') : (float) $m->stock;
            return $effStock <= 0;
        })->count();
        $lowStock = $activeMaterials->filter(function ($m) {
            $effStock = $m->stocks->isNotEmpty() ? (float) $m->stocks->sum('stock') : (float) $m->stock;
            return $effStock > 0 && $effStock <= 10;
        })->count();

        // Hitung total nilai aset dari seluruh Order Toko semua area
        $allPoTrxIds = \App\Models\MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')->pluck('id');
        $totalValue = (float) \App\Models\MaterialTransactionItem::whereIn('material_transaction_id', $allPoTrxIds)
            ->whereHas('material', function ($mq) {
                $mq->where('is_active', true)->orWhereNull('is_active');
            })
            ->sum('total_price');

        if ($totalValue <= 0) {
            $totalValue = (float) \App\Models\MaterialTransaction::whereIn('id', $allPoTrxIds)->sum('total_cost');
        }

        return [
            'total_items' => $totalItems,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'total_value' => round($totalValue),
        ];
    }

    /**
     * Hitung nilai aset per material dengan memperhatikan satuan besar (roll/pack/bungkus/pcs).
     */
    protected function calculateItemAssetValue(Material $material, float $stock): float
    {
        $price = (float) ($material->price_per_unit ?? 0);
        if ($price <= 0 || $stock <= 0) return 0;

        $cat = strtolower((string) $material->category);
        $name = strtolower((string) $material->name);

        if ((str_contains($cat, 'kabel') || str_contains($name, 'kabel')) && (float) $material->meter_per_roll > 0) {
            return ($stock / (float) $material->meter_per_roll) * $price;
        }

        $isIsolasi = str_contains($cat, 'isolasi') || str_contains($name, 'isolasi');
        if ($isIsolasi) {
            $cmPerPcs = (float) ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
            $pcsPerPack = (float) ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
            $factor = $cmPerPcs * $pcsPerPack;
            return ($stock / ($factor > 0 ? $factor : 1)) * $price;
        }

        if ((str_contains($cat, 'klem') || str_contains($name, 'klem')) && (float) $material->pcs_per_pack > 0) {
            return ($stock / (float) $material->pcs_per_pack) * $price;
        }

        return $stock * $price;
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

        $unitPrice = (float) ($material->price_per_unit ?? 0);
        $baseRate = $unitPrice;
        $cat = strtolower((string) $material->category);
        $name = strtolower((string) $material->name);
        if ((str_contains($cat, 'kabel') || str_contains($name, 'kabel')) && (float) $material->meter_per_roll > 0) {
            $baseRate = $unitPrice / (float) $material->meter_per_roll;
        } elseif (str_contains($cat, 'isolasi') || str_contains($name, 'isolasi')) {
            $cmPerPcs = (float) ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
            $pcsPerPack = (float) ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
            $factor = $cmPerPcs * $pcsPerPack;
            $baseRate = $factor > 0 ? ($unitPrice / $factor) : $unitPrice;
        } elseif ((str_contains($cat, 'klem') || str_contains($name, 'klem')) && (float) $material->pcs_per_pack > 0) {
            $baseRate = $unitPrice / (float) $material->pcs_per_pack;
        }
        $itemTotal = $baseRate * (float) $validated['added_stock'];

        \App\Models\MaterialTransactionItem::create([
            'material_transaction_id' => $transactionIn->id,
            'material_id' => $material->id,
            'quantity' => $validated['added_stock'],
            'unit' => $material->unit,
            'price_per_unit' => $baseRate,
            'total_price' => $itemTotal,
            'stock_before' => $materialStock->stock - $validated['added_stock'],
            'stock_after' => $materialStock->stock,
            'condition' => 'Layak Pakai'
        ]);

        return redirect()->route('materials.index')->with('success', "Berhasil menambahkan stok masuk untuk {$material->name}.");
    }
}
