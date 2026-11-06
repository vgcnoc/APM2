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
        $validated['is_migration'] = false; // Material baru yang ditambahkan di UI adalah material aktif (bukan legacy migrasi)
        $validated['is_catalog'] = false; // Material operasional fisik area (bukan master produk katalog toko)
        $validated['is_active'] = true;

        // Cek jika sudah ada material dengan nama yang sama, lakukan penambahan stok / penggabungan
        $existing = \App\Services\MaterialService::findByName($validated['name']);
        if ($existing) {
            \App\Services\MaterialService::addStockToExistingMaterial($existing, $validated, $areaId);
            return redirect()->route('materials.index')->with('success', 'Data material dengan nama tersebut sudah ada. Stok berhasil digabungkan ke material tersebut.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $areaId) {
            $material = Material::create($validated);

            if ($areaId) {
                $matStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, (int) $areaId);
                $matStock->stock = max(0.0, (float) $material->stock);
                $matStock->initial_stock = max(0.0, (float) $material->initial_stock);
                \App\Services\MaterialService::recalcAreaStockUnits($matStock, $material)->save();

                if ((float) $material->stock > 0) {
                    $baseRate = \App\Services\MaterialService::calculateBaseUnitPrice($material);
                    $transaction = \App\Models\MaterialTransaction::create([
                        'transaction_number' => \App\Services\MaterialService::generateTransactionNumber('IN-AREA'),
                        'type' => 'in',
                        'status' => 'approved',
                        'date' => now(),
                        'technician_name' => auth()->user() ? auth()->user()->name : 'Admin',
                        'purpose' => 'Pencatatan Material Fisik Area',
                        'user_id' => auth()->id(),
                        'area_id' => $areaId,
                        'total_cost' => $baseRate * (float) $material->stock,
                        'notes' => 'Pencatatan stok material fisik di area (non-toko)'
                    ]);

                    \App\Models\MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'quantity' => $material->stock,
                        'unit' => $material->unit,
                        'price_per_unit' => $baseRate,
                        'total_price' => $baseRate * (float) $material->stock,
                        'stock_before' => 0,
                        'stock_after' => $material->stock,
                        'condition' => 'Layak Pakai'
                    ]);
                }
            }
        });

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

        $areaId = !empty($validated['area_id']) ? (int) $validated['area_id'] : null;
        unset($validated['area_id']);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('materials_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $areaId && !in_array($areaId, array_map('intval', $accessibleAreas), true)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk mengubah stok pada area ini.');
        }

        if (array_key_exists('stock', $validated) && is_null($validated['stock'])) {
            unset($validated['stock']);
        }

        // Cek jika nama diubah menimpa material lain yang sudah ada sebelumnya
        $existing = \App\Services\MaterialService::findByName($validated['name'], $material->id);
        if ($existing) {
            \App\Services\MaterialService::mergeMaterials($existing, $material, $validated, $areaId, $validated['stock'] ?? null);
            return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui dan menimpa data material sebelumnya.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($material, $validated, $areaId) {
            $lockedMat = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $oldName = $lockedMat->name;
            $inputStock = array_key_exists('stock', $validated) ? (float) $validated['stock'] : null;
            $inputInitial = array_key_exists('initial_stock', $validated) && $validated['initial_stock'] !== null
                ? (float) $validated['initial_stock']
                : null;

            // Bila mengedit dalam konteks Area untuk Produk Katalog, JANGAN timpa stok Gudang Pusat (materials.stock)
            $syncMasterFromAreas = \App\Services\MaterialService::shouldSyncMasterStockFromAreas($lockedMat);
            if ($areaId && !$syncMasterFromAreas) {
                unset(
                    $validated['stock'],
                    $validated['initial_stock'],
                    $validated['total_rolls'],
                    $validated['total_packs'],
                    $validated['total_pieces']
                );
            }

            $lockedMat->update($validated);

            if ($oldName && $oldName !== $lockedMat->name) {
                \App\Models\Ont::where('brand', $oldName)->update(['brand' => $lockedMat->name]);
                \App\Models\MaterialTransactionItem::where('material_id', $lockedMat->id)->update(['material_name' => $lockedMat->name]);
            }

            if ($areaId) {
                $areaStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($lockedMat->id, $areaId);
                if ($inputStock !== null) {
                    $areaStock->stock = max(0.0, $inputStock);
                }
                if ($inputInitial !== null) {
                    $areaStock->initial_stock = max(0.0, $inputInitial);
                } elseif ((float) $areaStock->initial_stock <= 0 && $inputStock !== null) {
                    $areaStock->initial_stock = max(0.0, $inputStock);
                }
                \App\Services\MaterialService::recalcAreaStockUnits($areaStock, $lockedMat)->save();
                \App\Services\MaterialService::syncMasterStockIfAreaOnly($lockedMat);
            }
        });

        return redirect()->route('materials.index')->with('success', 'Data material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $hasTransactions = \App\Models\MaterialTransactionItem::where('material_id', $material->id)->exists();
            if ($hasTransactions) {
                $normalizedName = trim(preg_replace('/^test\s+/i', '', trim($material->name)));
                $replacement = Material::where('id', '!=', $material->id)
                    ->where('id', '>', 260)
                    ->where(function ($q) {
                        $q->where('is_active', true)->orWhereNull('is_active');
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($normalizedName)])
                    ->first();

                if ($replacement) {
                    \App\Models\MaterialTransactionItem::where('material_id', $material->id)
                        ->update(['material_id' => $replacement->id]);
                    \App\Models\Ont::where('material_id', $material->id)
                        ->update(['material_id' => $replacement->id]);
                    \App\Models\MaterialStock::where('material_id', $material->id)->delete();
                    $material->delete();
                } else {
                    // Soft delete via is_active = false dan kosongkan stok agar tidak muncul & tidak terhitung
                    $material->update([
                        'is_active' => false,
                        'stock' => 0,
                        'total_rolls' => 0,
                        'total_packs' => 0,
                        'total_pieces' => 0,
                    ]);
                    \App\Models\MaterialStock::where('material_id', $material->id)->update([
                        'stock' => 0,
                        'total_rolls' => 0,
                        'total_packs' => 0,
                        'total_pieces' => 0,
                    ]);
                }
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
        if ($stock <= 0) {
            return 0.0;
        }

        return $stock * \App\Services\MaterialService::calculateBaseUnitPrice($material);
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

        if (!$isGlobal && !empty($accessibleAreas) && !in_array((int) $validated['area_id'], array_map('intval', $accessibleAreas), true)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk menambah stok pada area ini.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($material, $validated) {
            $lockedMat = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $addedStock = (float) $validated['added_stock'];
            $areaId = (int) $validated['area_id'];

            if (isset($validated['price_per_unit']) && $validated['price_per_unit'] !== null && $validated['price_per_unit'] !== '') {
                $lockedMat->price_per_unit = (float) $validated['price_per_unit'];
            }
            if (isset($validated['selling_price']) && $validated['selling_price'] !== null && $validated['selling_price'] !== '') {
                $lockedMat->selling_price = (float) $validated['selling_price'];
            }

            // Update Area stock dengan row lock & hitung konversi secara konsisten
            $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($lockedMat->id, $areaId);
            $stockBefore = (float) ($materialStock->stock ?? 0);
            $stockAfter = $stockBefore + $addedStock;
            $materialStock->stock = $stockAfter;
            $materialStock->initial_stock = (float) ($materialStock->initial_stock ?? 0) + $addedStock;
            \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $lockedMat)->save();

            // Hanya sinkronkan materials.stock untuk material non-katalog / area-only (jangan menggandakan stok Gudang Pusat)
            if (\App\Services\MaterialService::shouldSyncMasterStockFromAreas($lockedMat)) {
                $lockedMat->initial_stock = (float) ($lockedMat->initial_stock ?? 0) + $addedStock;
                \App\Services\MaterialService::syncMasterStockIfAreaOnly($lockedMat);
            } else {
                $lockedMat->recalcUnitTotals()->save();
            }

            $baseRate = \App\Services\MaterialService::calculateBaseUnitPrice($lockedMat);
            $itemTotal = $baseRate * $addedStock;

            // Rekam ke Log Mutasi (MaterialTransaction & MaterialTransactionItem)
            $transactionIn = \App\Models\MaterialTransaction::create([
                'transaction_number' => \App\Services\MaterialService::generateTransactionNumber('IN-RESTOCK'),
                'type' => 'in',
                'status' => 'approved',
                'date' => now(),
                'technician_name' => auth()->user() ? auth()->user()->name : 'Admin',
                'purpose' => 'Stok Masuk / Restock Gudang',
                'user_id' => auth()->id(),
                'area_id' => $areaId,
                'total_cost' => $itemTotal,
                'notes' => 'Pemasukan stok baru via menu Material'
            ]);

            \App\Models\MaterialTransactionItem::create([
                'material_transaction_id' => $transactionIn->id,
                'material_id' => $lockedMat->id,
                'material_name' => $lockedMat->name,
                'quantity' => $addedStock,
                'unit' => $lockedMat->unit,
                'price_per_unit' => $baseRate,
                'total_price' => $itemTotal,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'condition' => 'Layak Pakai'
            ]);
        });

        return redirect()->route('materials.index')->with('success', "Berhasil menambahkan stok masuk untuk {$material->name}.");
    }
}
