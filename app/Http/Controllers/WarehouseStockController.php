<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseStockController extends Controller
{
    /**
     * Filter material gudang yang relevan:
     * - Barang katalog / standar non-migrasi: Tampil di gudang jika aktif
     * - Barang migrasi / bekas / batch sisa: HANYA tampil jika BENAR-BENAR memiliki sisa stok fisik di gudang (> 0)
     *   dan khusus perangkat modem ONT migrasi, WAJIB memiliki unit fisik nyata yang unassigned di tabel onts.
     */
    protected function getWarehouseFilter(?array $scopedAreaIds = null)
    {
        return function ($q) use ($scopedAreaIds) {
            $q->where(function ($sub) use ($scopedAreaIds) {
                // 1. Barang Katalog / Standar Non-Migrasi Aktif
                $sub->where(function ($catQ) {
                    $catQ->where(function ($mq) {
                        $mq->where('is_migration', false)->orWhereNull('is_migration');
                    })
                    ->where(function ($nameQ) {
                        $nameQ->where('name', 'not like', '%(migrasi%')
                              ->where('name', 'not like', '%(bekas%')
                              ->where('name', 'not like', '%(batch%')
                              ->where('name', 'not like', '%-Nay%')
                              ->where('name', 'not like', '%-Yud%')
                              ->where('name', 'not like', '%-A12%')
                              ->where('name', 'not like', '%-By%')
                              ->where('name', 'not like', '%-SNW%');
                    });
                })
                // 2. Barang Migrasi / Bekas: HANYA tampil jika memiliki sisa stok fisik nyata di gudang (> 0)
                ->orWhere(function ($migQ) use ($scopedAreaIds) {
                    $migQ->where(function ($mq) {
                        $mq->where('is_migration', true)
                           ->orWhere('id', '<=', 260)
                           ->orWhere('name', 'like', '%(migrasi%')
                           ->orWhere('name', 'like', '%(bekas%')
                           ->orWhere('name', 'like', '%(batch%')
                           ->orWhere('name', 'like', '%-Nay%')
                           ->orWhere('name', 'like', '%-Yud%')
                           ->orWhere('name', 'like', '%-A12%')
                           ->orWhere('name', 'like', '%-By%')
                           ->orWhere('name', 'like', '%-SNW%');
                    })
                    ->where(function ($tq) use ($scopedAreaIds) {
                        // Barang non-ONT migrasi
                        $tq->where(function ($nonOnt) {
                            $nonOnt->where('category', '!=', 'ONT')
                                   ->where('category', 'not like', '%ont%')
                                   ->where('name', 'not like', 'ONT %');
                        })
                        // Atau jika ONT migrasi: tampil jika memiliki sisa stok fisik di gudang (> 0) ATAU ada unit fisik unassigned di tabel onts
                        ->orWhere(function ($ontQ) use ($scopedAreaIds) {
                            $ontQ->where(function ($oq) {
                                $oq->where('category', 'ONT')
                                   ->orWhere('category', 'like', '%ont%')
                                   ->orWhere('name', 'like', 'ONT %');
                            })
                            ->where(function ($condQ) use ($scopedAreaIds) {
                                $condQ->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                                    if ($scopedAreaIds) {
                                        $sq->whereIn('area_id', $scopedAreaIds);
                                    }
                                    $sq->where('stock', '>', 0);
                                })
                                ->orWhereExists(function ($subOntQ) use ($scopedAreaIds) {
                                    $subOntQ->select(\Illuminate\Support\Facades\DB::raw(1))
                                        ->from('onts')
                                        ->whereNull('onts.customer_id')
                                        ->whereNotIn('onts.status', ['installed', 'rusak', 'afkir'])
                                        ->when($scopedAreaIds, function ($sq) use ($scopedAreaIds) {
                                            $sq->whereIn('onts.area_id', $scopedAreaIds);
                                        })
                                        ->where(function ($matchQ) {
                                            $matchQ->whereColumn('onts.model', 'materials.name')
                                                   ->orWhereRaw("onts.model = REPLACE(REPLACE(materials.name, 'ONT ', ''), ' (Bekas Layak Pakai)', '')");
                                        });
                                });
                            });
                        });
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
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('warehouse_stocks_view_all') || $user->can('materials_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        $selectedAreaId = $request->filled('area_id') ? (int) $request->area_id : null;
        $scopedAreaIds = $selectedAreaId ? [$selectedAreaId] : (!$isGlobal && !empty($accessibleAreas) ? $accessibleAreas : null);

        $warehouseFilter = $this->getWarehouseFilter($scopedAreaIds);

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

        // 1. Scope restriction & Area list
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

        // 7. Filter Tanggal Cerdas (Date Range Filter: Mutasi Stok, Tanggal Didaftarkan, Terakhir Update)
        $dateFrom = $request->filled('date_from') ? trim($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? trim($request->date_to) : null;
        $dateFilterBy = $request->get('date_filter_by', 'transaction'); // 'transaction', 'created_at', 'updated_at'
        $datePreset = $request->get('date_preset', '');

        if ($dateFrom || $dateTo) {
            if ($dateFilterBy === 'created_at') {
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(materials.created_at)'), [$dateFrom, $dateTo]);
                    } elseif ($dateFrom) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.created_at)'), '>=', $dateFrom);
                    } elseif ($dateTo) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.created_at)'), '<=', $dateTo);
                    }
                });
            } elseif ($dateFilterBy === 'updated_at') {
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(materials.updated_at)'), [$dateFrom, $dateTo]);
                    } elseif ($dateFrom) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.updated_at)'), '>=', $dateFrom);
                    } elseif ($dateTo) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.updated_at)'), '<=', $dateTo);
                    }
                });
            } else { // default: 'transaction' (Mutasi / Transaksi Stok Fisik Gudang)
                $query->whereHas('transactionItems', function ($tiq) use ($dateFrom, $dateTo, $scopedAreaIds) {
                    $tiq->whereHas('transaction', function ($tq) use ($dateFrom, $dateTo, $scopedAreaIds) {
                        if ($dateFrom && $dateTo) {
                            $tq->where(function ($sub) use ($dateFrom, $dateTo) {
                                $sub->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), [$dateFrom, $dateTo])
                                    ->orWhere(function ($orQ) use ($dateFrom, $dateTo) {
                                        $orQ->whereNull('material_transactions.date')
                                            ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), [$dateFrom, $dateTo]);
                                    });
                            });
                        } elseif ($dateFrom) {
                            $tq->where(function ($sub) use ($dateFrom) {
                                $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '>=', $dateFrom)
                                    ->orWhere(function ($orQ) use ($dateFrom) {
                                        $orQ->whereNull('material_transactions.date')
                                            ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '>=', $dateFrom);
                                    });
                            });
                        } elseif ($dateTo) {
                            $tq->where(function ($sub) use ($dateTo) {
                                $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '<=', $dateTo)
                                    ->orWhere(function ($orQ) use ($dateTo) {
                                        $orQ->whereNull('material_transactions.date')
                                            ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '<=', $dateTo);
                                    });
                            });
                        }
                        if (!empty($scopedAreaIds)) {
                            $tq->whereIn('material_transactions.area_id', $scopedAreaIds);
                        }
                    });
                });
            }
        }

        // 8. Pengurutan (Sorting)
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

        // Ambil kalkulasi mutasi per item pada halaman aktif jika filter tanggal mutasi digunakan
        $periodStats = collect();
        if (($dateFrom || $dateTo) && $dateFilterBy === 'transaction') {
            $materialIds = $materials->getCollection()->pluck('id')->filter()->all();
            if (!empty($materialIds)) {
                $periodStats = \App\Models\MaterialTransactionItem::join('material_transactions', 'material_transaction_items.material_transaction_id', '=', 'material_transactions.id')
                    ->whereIn('material_transaction_items.material_id', $materialIds)
                    ->where(function ($sub) use ($dateFrom, $dateTo) {
                        if ($dateFrom && $dateTo) {
                            $sub->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), [$dateFrom, $dateTo])
                                ->orWhere(function ($orQ) use ($dateFrom, $dateTo) {
                                    $orQ->whereNull('material_transactions.date')
                                        ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), [$dateFrom, $dateTo]);
                                });
                        } elseif ($dateFrom) {
                            $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '>=', $dateFrom)
                                ->orWhere(function ($orQ) use ($dateFrom) {
                                    $orQ->whereNull('material_transactions.date')
                                        ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '>=', $dateFrom);
                                });
                        } elseif ($dateTo) {
                            $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '<=', $dateTo)
                                ->orWhere(function ($orQ) use ($dateTo) {
                                    $orQ->whereNull('material_transactions.date')
                                        ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '<=', $dateTo);
                                });
                        }
                    })
                    ->when(!empty($scopedAreaIds), fn($q) => $q->whereIn('material_transactions.area_id', $scopedAreaIds))
                    ->select(
                        'material_transaction_items.material_id',
                        \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN material_transactions.type = 'in' THEN material_transaction_items.quantity ELSE 0 END) as total_in"),
                        \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN material_transactions.type = 'out' THEN material_transaction_items.quantity ELSE 0 END) as total_out"),
                        \Illuminate\Support\Facades\DB::raw("MAX(COALESCE(material_transactions.date, material_transactions.created_at)) as latest_activity")
                    )
                    ->groupBy('material_transaction_items.material_id')
                    ->get()
                    ->keyBy('material_id');
            }
        }

        $materials->getCollection()->transform(function ($material) use ($periodStats) {
            $nameLower = strtolower($material->name . ' ' . ($material->description ?? ''));
            if (str_contains($nameLower, 'rusak') || str_contains($nameLower, 'afkir') || str_contains($nameLower, 'scrap')) {
                $material->condition_label = 'Rusak';
            } elseif (str_contains($nameLower, 'tidak layak')) {
                $material->condition_label = 'Tidak Layak';
            } else {
                $material->condition_label = 'Layak Pakai';
            }

            $st = $periodStats->get($material->id);
            if ($st) {
                $material->period_in = (float) $st->total_in;
                $material->period_out = (float) $st->total_out;
                $material->latest_activity = $st->latest_activity;
                $material->has_period_activity = true;
            } else {
                $material->period_in = 0.0;
                $material->period_out = 0.0;
                $material->latest_activity = null;
                $material->has_period_activity = false;
            }

            return $material;
        });

        $baseSummaryQuery = Material::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->visibleInMaterialsTable()
        ->where($warehouseFilter);

        $categories = (clone $baseSummaryQuery)->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->sort()->values();
        $units = (clone $baseSummaryQuery)->whereNotNull('unit')->where('unit', '!=', '')->distinct()->pluck('unit')->sort()->values();

        // Summary counts (Hitung material yang masuk kriteria area warehouse)
        if ($scopedAreaIds) {
            $totalMaterials = (clone $baseSummaryQuery)->count();
            $outOfStockCount = (clone $baseSummaryQuery)
                ->whereDoesntHave('stocks', function ($sq) use ($scopedAreaIds) {
                    $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0);
                })->count();
            $availableCount = (clone $baseSummaryQuery)->whereHas('stocks', function ($sq) use ($scopedAreaIds) {
                $sq->whereIn('area_id', $scopedAreaIds)->where('stock', '>', 0);
            })->count();
        } else {
            $totalMaterials = (clone $baseSummaryQuery)->count();
            $outOfStockCount = (clone $baseSummaryQuery)
                ->whereDoesntHave('stocks', function ($sq) {
                    $sq->where('stock', '>', 0);
                })->count();
            $availableCount = (clone $baseSummaryQuery)->whereHas('stocks', function ($sq) {
                $sq->where('stock', '>', 0);
            })->count();
        }

        $baseCondQuery = clone $baseSummaryQuery;
        
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
            'filters' => $request->only(['search', 'category', 'unit', 'area_id', 'stock_status', 'sort_by', 'condition', 'date_from', 'date_to', 'date_filter_by', 'date_preset']),
            'summary' => [
                'total_materials' => $totalMaterials,
                'out_of_stock' => $outOfStockCount,
                'available' => $availableCount,
                'filtered_count' => $materials->total(),
                'condition_stats' => $conditionStats,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'date_filter_by' => $dateFilterBy,
                'date_preset' => $datePreset,
                'has_date_filter' => (bool) ($dateFrom || $dateTo),
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

    /**
     * Halaman Cetak Dokumen Rekapitulasi Stok Gudang (Print View)
     */
    public function print(Request $request)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('warehouse_stocks_view_all') || $user->can('materials_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        $selectedAreaId = $request->filled('area_id') ? (int) $request->area_id : null;
        $scopedAreaIds = $selectedAreaId ? [$selectedAreaId] : (!$isGlobal && !empty($accessibleAreas) ? $accessibleAreas : null);

        $warehouseFilter = $this->getWarehouseFilter($scopedAreaIds);

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
            $areas = Area::orderBy('name')->get(['id', 'name']);
        }

        // 2. Search
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

        // 3. Category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // 4. Unit
        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        // 5. Stock Status
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

        // 6. Condition
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

        // 7. Filter Tanggal Cerdas pada Laporan Cetak
        $dateFrom = $request->filled('date_from') ? trim($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? trim($request->date_to) : null;
        $dateFilterBy = $request->get('date_filter_by', 'transaction');

        if ($dateFrom || $dateTo) {
            if ($dateFilterBy === 'created_at') {
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(materials.created_at)'), [$dateFrom, $dateTo]);
                    } elseif ($dateFrom) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.created_at)'), '>=', $dateFrom);
                    } elseif ($dateTo) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.created_at)'), '<=', $dateTo);
                    }
                });
            } elseif ($dateFilterBy === 'updated_at') {
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(materials.updated_at)'), [$dateFrom, $dateTo]);
                    } elseif ($dateFrom) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.updated_at)'), '>=', $dateFrom);
                    } elseif ($dateTo) {
                        $q->where(\Illuminate\Support\Facades\DB::raw('DATE(materials.updated_at)'), '<=', $dateTo);
                    }
                });
            } else { // default: 'transaction'
                $query->whereHas('transactionItems', function ($tiq) use ($dateFrom, $dateTo, $scopedAreaIds) {
                    $tiq->whereHas('transaction', function ($tq) use ($dateFrom, $dateTo, $scopedAreaIds) {
                        if ($dateFrom && $dateTo) {
                            $tq->where(function ($sub) use ($dateFrom, $dateTo) {
                                $sub->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), [$dateFrom, $dateTo])
                                    ->orWhere(function ($orQ) use ($dateFrom, $dateTo) {
                                        $orQ->whereNull('material_transactions.date')
                                            ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), [$dateFrom, $dateTo]);
                                    });
                            });
                        } elseif ($dateFrom) {
                            $tq->where(function ($sub) use ($dateFrom) {
                                $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '>=', $dateFrom)
                                    ->orWhere(function ($orQ) use ($dateFrom) {
                                        $orQ->whereNull('material_transactions.date')
                                            ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '>=', $dateFrom);
                                    });
                            });
                        } elseif ($dateTo) {
                            $tq->where(function ($sub) use ($dateTo) {
                                $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '<=', $dateTo)
                                    ->orWhere(function ($orQ) use ($dateTo) {
                                        $orQ->whereNull('material_transactions.date')
                                            ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '<=', $dateTo);
                                    });
                            });
                        }
                        if (!empty($scopedAreaIds)) {
                            $tq->whereIn('material_transactions.area_id', $scopedAreaIds);
                        }
                    });
                });
            }
        }

        // 8. Sorting
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

        $materials = $query->get();

        $periodStats = collect();
        if (($dateFrom || $dateTo) && $dateFilterBy === 'transaction') {
            $materialIds = $materials->pluck('id')->filter()->all();
            if (!empty($materialIds)) {
                $periodStats = \App\Models\MaterialTransactionItem::join('material_transactions', 'material_transaction_items.material_transaction_id', '=', 'material_transactions.id')
                    ->whereIn('material_transaction_items.material_id', $materialIds)
                    ->where(function ($sub) use ($dateFrom, $dateTo) {
                        if ($dateFrom && $dateTo) {
                            $sub->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), [$dateFrom, $dateTo])
                                ->orWhere(function ($orQ) use ($dateFrom, $dateTo) {
                                    $orQ->whereNull('material_transactions.date')
                                        ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), [$dateFrom, $dateTo]);
                                });
                        } elseif ($dateFrom) {
                            $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '>=', $dateFrom)
                                ->orWhere(function ($orQ) use ($dateFrom) {
                                    $orQ->whereNull('material_transactions.date')
                                        ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '>=', $dateFrom);
                                });
                        } elseif ($dateTo) {
                            $sub->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.date)'), '<=', $dateTo)
                                ->orWhere(function ($orQ) use ($dateTo) {
                                    $orQ->whereNull('material_transactions.date')
                                        ->where(\Illuminate\Support\Facades\DB::raw('DATE(material_transactions.created_at)'), '<=', $dateTo);
                                });
                        }
                    })
                    ->when(!empty($scopedAreaIds), fn($q) => $q->whereIn('material_transactions.area_id', $scopedAreaIds))
                    ->select(
                        'material_transaction_items.material_id',
                        \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN material_transactions.type = 'in' THEN material_transaction_items.quantity ELSE 0 END) as total_in"),
                        \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN material_transactions.type = 'out' THEN material_transaction_items.quantity ELSE 0 END) as total_out"),
                        \Illuminate\Support\Facades\DB::raw("MAX(COALESCE(material_transactions.date, material_transactions.created_at)) as latest_activity")
                    )
                    ->groupBy('material_transaction_items.material_id')
                    ->get()
                    ->keyBy('material_id');
            }
        }

        $materials->transform(function ($material) use ($periodStats) {
            $nameLower = strtolower($material->name . ' ' . ($material->description ?? ''));
            if (str_contains($nameLower, 'rusak') || str_contains($nameLower, 'afkir') || str_contains($nameLower, 'scrap')) {
                $material->condition_label = 'Rusak';
            } elseif (str_contains($nameLower, 'tidak layak')) {
                $material->condition_label = 'Tidak Layak';
            } else {
                $material->condition_label = 'Layak Pakai';
            }

            $st = $periodStats->get($material->id);
            if ($st) {
                $material->period_in = (float) $st->total_in;
                $material->period_out = (float) $st->total_out;
                $material->latest_activity = $st->latest_activity;
                $material->has_period_activity = true;
            } else {
                $material->period_in = 0.0;
                $material->period_out = 0.0;
                $material->latest_activity = null;
                $material->has_period_activity = false;
            }

            return $material;
        });

        // Company
        $company = ($user && $user->company) ? $user->company : \App\Models\Company::holding();
        if (!$company) {
            $company = (object) [
                'name' => 'PT VIRUZS GLOBAL CONNECTION',
                'brand_name' => 'VIZBILL',
                'tagline' => 'Internet & Digital Infrastructure Services',
                'address' => 'Desa Sirnajaya, Kec. Gunungguruh, Kab. Sukabumi, Jawa Barat',
                'phone' => '0823-1864-5178',
                'email' => 'support@viruzs.net',
                'website' => 'https://viruzs.net',
            ];
        }

        $totalItems = $materials->count();
        $availableItems = $materials->filter(function ($m) use ($selectedAreaId) {
            if ($selectedAreaId) {
                return (float) ($m->stocks->where('area_id', $selectedAreaId)->first()?->stock ?? 0) > 0;
            }
            return $m->stocks->sum('stock') > 0;
        })->count();
        $outOfStockItems = $totalItems - $availableItems;
        $layakItems = $materials->filter(fn($m) => $m->condition_label === 'Layak Pakai')->count();
        $tidakLayakItems = $materials->filter(fn($m) => $m->condition_label === 'Tidak Layak')->count();
        $rusakItems = $materials->filter(fn($m) => $m->condition_label === 'Rusak')->count();

        $selectedArea = $selectedAreaId ? Area::find($selectedAreaId) : null;

        // Signer / Account Owner info
        $defaultCity = 'Sukabumi';
        if (!empty($user?->last_location_name)) {
            $parts = explode(',', $user->last_location_name);
            $defaultCity = trim(end($parts) ?: $parts[0]);
        }
        $signerCity = $request->get('city') ?: $defaultCity;
        $signerDate = $request->get('date') ?: now()->toDateString();
        $signerName = $request->get('signer_name') ?: ($user?->name ?? 'Super Admin');
        $signerRole = $request->get('signer_role') ?: (ucwords(str_replace('_', ' ', $user?->role ?? 'Penanggung Jawab Gudang')));

        return Inertia::render('Inventory/Warehouse/Print', [
            'materials' => $materials,
            'areas' => $areas,
            'company' => $company,
            'user' => $user,
            'filters' => $request->only(['search', 'category', 'unit', 'area_id', 'stock_status', 'sort_by', 'condition', 'date_from', 'date_to', 'date_filter_by', 'date_preset']),
            'selectedArea' => $selectedArea,
            'summary' => [
                'total' => $totalItems,
                'available' => $availableItems,
                'out_of_stock' => $outOfStockItems,
                'layak' => $layakItems,
                'tidak_layak' => $tidakLayakItems,
                'rusak' => $rusakItems,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'date_filter_by' => $dateFilterBy,
                'has_date_filter' => (bool) ($dateFrom || $dateTo),
            ],
            'meta' => [
                'city' => trim($signerCity),
                'date' => $signerDate,
                'signer_name' => $signerName,
                'signer_role' => $signerRole,
                'signature_mode' => $request->get('signature_mode', 'digital'),
                'show_approver' => $request->boolean('show_approver', true),
                'approver_title' => $request->get('approver_title', 'Pimpinan / Manager Operasional'),
                'printed_at' => now()->format('Y-m-d H:i:s'),
                'report_number' => 'INV-RPT-' . now()->format('Ymd-His'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'date_filter_by' => $dateFilterBy,
                'has_date_filter' => (bool) ($dateFrom || $dateTo),
            ]
        ]);
    }
}
