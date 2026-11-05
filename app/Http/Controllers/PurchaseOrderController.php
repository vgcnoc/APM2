<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Area;
use App\Models\Ont;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('purchase_orders_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        // Only get transactions with purpose indicating it's a purchase and has existing active materials
        $query = MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')
                            ->whereHas('items.material', function ($mq) {
                                $mq->where('is_active', true)->orWhereNull('is_active');
                            })
                            ->with([
                                'items' => function ($iq) {
                                    $iq->whereHas('material', function ($mq) {
                                        $mq->where('is_active', true)->orWhereNull('is_active');
                                    })->with('material', 'onts');
                                },
                                'expense.category',
                                'areaModel',
                                'user',
                                'orderUser',
                                'giverUser',
                            ]);

        // Terapkan filter scope pada query
        $query = \App\Services\DataScopeService::applyPurchaseOrderScope($query, $user);

        // 1. Pencarian Teks Fleksibel (No PO, Area/Teknisi, Catatan, Nama Material)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('technician_name', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('order_account_name', 'like', "%{$search}%")
                  ->orWhere('giver_account_name', 'like', "%{$search}%")
                  ->orWhereHas('orderUser', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('giverUser', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('items.material', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('category', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter Area / Wilayah
        if ($request->filled('area')) {
            $area = trim($request->area);
            $query->where(function ($q) use ($area) {
                $q->where('technician_name', $area)
                  ->orWhere('area', $area);
            });
        }

        // 3. Filter Material / Barang Tertentu
        if ($request->filled('material_id')) {
            $materialId = $request->material_id;
            $query->whereHas('items', function ($q) use ($materialId) {
                $q->where('material_id', $materialId);
            });
        }

        // 4. Filter Rentang Tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // 5. Filter Status ONT
        if ($request->filled('ont_filter')) {
            if ($request->ont_filter === 'with_ont') {
                $query->whereHas('items.material', function ($q) {
                    $q->where('category', 'like', '%ont%')
                      ->orWhere('name', 'like', '%ont%');
                });
            } elseif ($request->ont_filter === 'without_ont') {
                $query->whereDoesntHave('items.material', function ($q) {
                    $q->where('category', 'like', '%ont%')
                      ->orWhere('name', 'like', '%ont%');
                });
            }
        }

        // Clone query for summary calculation before pagination - hanya hitung material yang masih ada/aktif
        $summaryQuery = clone $query;
        $transactionIds = (clone $summaryQuery)->pluck('id');
        $totalCost = (float) DB::table('material_transaction_items')
            ->join('materials', 'material_transaction_items.material_id', '=', 'materials.id')
            ->whereIn('material_transaction_items.material_transaction_id', $transactionIds)
            ->where(function ($q) {
                $q->where('materials.is_active', true)->orWhereNull('materials.is_active');
            })
            ->sum('material_transaction_items.total_price');

        $transactions = $query->latest('date')->latest('id')->paginate(15)->withQueryString();

        // Pastikan total_cost per baris transaksi mencerminkan total dari material yang aktif
        $transactions->getCollection()->transform(function ($trx) {
            $trx->total_cost = (float) $trx->items->sum('total_price');
            return $trx;
        });

        // Hanya produk aktif dari menu Data Produk (Katalog Produk & Satuan) yang bisa diorder
        $materials = Material::catalogProducts()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $areaQuery = \App\Models\Area::orderBy('name');
        if (!$isGlobal && !empty($accessibleAreas)) {
            $areaQuery->whereIn('id', $accessibleAreas);
        }
        $areas = $areaQuery->get(['id', 'name']);

        return Inertia::render('Inventory/PurchaseOrders/Index', [
            'transactions' => $transactions,
            'materials' => $materials,
            'areas' => $areas,
            'users' => \App\Models\User::orderBy('name')->get(['id', 'name', 'role']),
            'currentUser' => $user ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->role] : null,
            'filters' => $request->only(['search', 'area', 'material_id', 'date_from', 'date_to', 'ont_filter']),
            'summary' => [
                'total_cost' => $totalCost,
                'total_count' => $transactions->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'area_name' => 'required|string|max:255',
            'order_user_id' => 'nullable|exists:users,id',
            'giver_user_id' => 'nullable|exists:users,id',
            'order_account_name' => 'nullable|string|max:255',
            'giver_account_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.purchase_unit' => 'required|string', // e.g., 'roll', 'pack', 'pcs', 'meter', 'cm'
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);
        $validated['date'] = $validated['date'] ?? now()->toDateString();

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('purchase_orders_view_all');
            $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

            $area = Area::where('name', $validated['area_name'])->first();

            if (!$isGlobal && !empty($accessibleAreas)) {
                if ($area && !in_array($area->id, $accessibleAreas)) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk wilayah ' . $validated['area_name'] . '.');
                }
            }

            // Generate Transaction Number (PO) anti-bentrok
            $ymd = \Carbon\Carbon::parse($validated['date'])->format('Ymd');
            $transactionNumber = \App\Services\MaterialService::generateTransactionNumber('PO', $ymd);

            $orderUserId = !empty($validated['order_user_id']) ? (int) $validated['order_user_id'] : auth()->id();
            $giverUserId = !empty($validated['giver_user_id']) 
                ? (int) $validated['giver_user_id'] 
                : \App\Models\User::where('role', 'toko')->orWhere('name', 'TOKO')->value('id');

            $orderUser = $orderUserId ? \App\Models\User::find($orderUserId) : null;
            $giverUser = $giverUserId ? \App\Models\User::find($giverUserId) : null;

            $orderAccountName = $orderUser ? $orderUser->name : ($validated['order_account_name'] ?? auth()->user()?->name ?? 'Pemesan Toko');
            $giverAccountName = $giverUser ? $giverUser->name : ($validated['giver_account_name'] ?? 'TOKO (Gudang Pusat)');

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'in',
                'status' => 'approved',
                'date' => $validated['date'],
                'purpose' => 'Pembelian Toko / Supplier',
                'technician_name' => $validated['area_name'], // Reusing this column for area/wilayah name
                'area_id' => $area ? $area->id : null,
                'area' => $validated['area_name'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'order_user_id' => $orderUserId,
                'giver_user_id' => $giverUserId,
                'order_account_name' => $orderAccountName,
                'giver_account_name' => $giverAccountName,
                'total_cost' => 0 // Will calculate below
            ]);

            $totalCost = 0;

            foreach ($validated['items'] as $item) {
                $material = Material::lockForUpdate()->findOrFail($item['material_id']);

                $inputPrice = (isset($item['price']) && $item['price'] !== null && $item['price'] !== '')
                    ? (float) $item['price']
                    : null;

                $pricing = \App\Services\MaterialService::calculateOrderPricing(
                    $material,
                    (float) $item['quantity'],
                    $item['purchase_unit'],
                    $inputPrice
                );

                $convertedQuantity = $pricing['converted_qty'];
                $itemTotal = $pricing['total_price'];
                $pricePerBaseUnit = $pricing['base_unit_price'];

                // Cek ketersediaan stok di Gudang Pusat sebelum diproses
                if ((float) $material->stock < (float) $convertedQuantity) {
                    throw new \Exception("Stok '{$material->name}' di Gudang Pusat tidak mencukupi (Tersisa: {$material->stock} {$material->unit}, diminta: {$convertedQuantity} {$material->unit}).");
                }

                $totalCost += $itemTotal;

                // Kurangi Stok Gudang Pusat & sinkronkan konversi (Order Toko mendistribusikan dari Pusat ke Cabang)
                $material->stock = max(0.0, (float) $material->stock - (float) $convertedQuantity);
                $material->recalcUnitTotals()->save();

                // Tambah Stok di Area Tujuan dengan row lock
                $areaStockBefore = 0.0;
                $areaStockAfter = (float) $convertedQuantity;
                if ($area) {
                    $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, $area->id);
                    $areaStockBefore = (float) ($materialStock->stock ?? 0);
                    $areaStockAfter = $areaStockBefore + (float) $convertedQuantity;
                    $materialStock->stock = $areaStockAfter;
                    $materialStock->initial_stock = (float) ($materialStock->initial_stock ?? 0) + (float) $convertedQuantity;
                    \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();
                }

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'quantity' => $convertedQuantity, // The quantity in BASE UNIT
                    'unit' => $material->unit,
                    'price_per_unit' => $pricePerBaseUnit,
                    'total_price' => $itemTotal,
                    'stock_before' => $areaStockBefore,
                    'stock_after' => $areaStockAfter,
                    'condition' => 'Layak Pakai',
                ]);
            }

            $transaction->update(['total_cost' => $totalCost]);

            // Sinkronkan otomatis ke Pengeluaran Kas (Buku Kas Umum)
            $this->syncOrderExpense($transaction, $totalCost, $validated['date'], $validated['area_name']);

            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dicatat, stok didistribusikan ke area, dan tercatat di Laporan Keuangan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat order: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $transaction = MaterialTransaction::with('items')->findOrFail($id);

        $validated = $request->validate([
            'date' => 'nullable|date',
            'area_name' => 'required|string|max:255',
            'order_user_id' => 'nullable|exists:users,id',
            'giver_user_id' => 'nullable|exists:users,id',
            'order_account_name' => 'nullable|string|max:255',
            'giver_account_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.purchase_unit' => 'required|string', 
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);
        $validated['date'] = $validated['date'] ?? now()->toDateString();

        DB::beginTransaction();
        try {
            $transaction = MaterialTransaction::whereKey($id)->lockForUpdate()->with('items')->firstOrFail();

            $user = auth()->user();
            $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('purchase_orders_view_all');
            $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

            $area = Area::where('name', $validated['area_name'])->first();

            if (!$isGlobal && !empty($accessibleAreas)) {
                if ($area && !in_array($area->id, $accessibleAreas)) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk wilayah ' . $validated['area_name'] . '.');
                }
                if ($transaction->area_id && !in_array($transaction->area_id, $accessibleAreas)) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk mengubah order dari wilayah ini.');
                }
            }

            $oldArea = $transaction->area_id
                ? Area::find($transaction->area_id)
                : Area::where('name', $transaction->technician_name)->first();
            $newArea = $area;
            $newAreaId = $newArea ? $newArea->id : null;

            $orderUserId = !empty($validated['order_user_id']) ? (int) $validated['order_user_id'] : $transaction->order_user_id;
            $giverUserId = !empty($validated['giver_user_id']) ? (int) $validated['giver_user_id'] : $transaction->giver_user_id;

            $orderUser = $orderUserId ? \App\Models\User::find($orderUserId) : null;
            $giverUser = $giverUserId ? \App\Models\User::find($giverUserId) : null;

            $orderAccountName = $orderUser ? $orderUser->name : ($validated['order_account_name'] ?? $transaction->order_account_name);
            $giverAccountName = $giverUser ? $giverUser->name : ($validated['giver_account_name'] ?? $transaction->giver_account_name);

            // Update info transaksi header
            $transaction->update([
                'status' => 'approved',
                'date' => $validated['date'],
                'technician_name' => $validated['area_name'],
                'area_id' => $newAreaId,
                'area' => $validated['area_name'],
                'order_user_id' => $orderUserId,
                'giver_user_id' => $giverUserId,
                'order_account_name' => $orderAccountName,
                'giver_account_name' => $giverAccountName,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Existing items indexed by id
            $existingItems = $transaction->items->keyBy('id');
            $submittedItemIds = collect($validated['items'])->pluck('id')->filter()->map(fn($v) => (int) $v)->toArray();

            // 1. Tangani item-item yang DIHAPUS dari order
            foreach ($existingItems as $oldItemId => $oldItem) {
                if (!in_array((int) $oldItemId, $submittedItemIds, true)) {
                    $oldMat = Material::lockForUpdate()->find($oldItem->material_id);

                    // Cegah penghapusan jika unit ONT dari item PO ini sudah aktif terpasang di pelanggan
                    $hasInstalledOnt = Ont::where('material_transaction_item_id', $oldItem->id)
                        ->whereNotNull('customer_id')
                        ->exists();
                    if ($hasInstalledOnt) {
                        $matLabel = $oldMat ? $oldMat->name : ('Item #' . $oldItem->id);
                        throw new \Exception("Item '{$matLabel}' tidak dapat dihapus dari order ini karena terdapat unit ONT yang sudah terpasang pada pelanggan.");
                    }

                    // Revert stok pusat & area
                    if ($oldMat) {
                        $oldMat->stock = max(0.0, (float) $oldMat->stock + (float) $oldItem->quantity);
                        $oldMat->recalcUnitTotals()->save();
                    }
                    if ($oldArea) {
                        $oldStock = \App\Models\MaterialStock::where([
                            'material_id' => $oldItem->material_id,
                            'area_id' => $oldArea->id,
                        ])->lockForUpdate()->first();
                        if ($oldStock) {
                            $oldStock->stock = max(0.0, (float) $oldStock->stock - (float) $oldItem->quantity);
                            $oldStock->initial_stock = max(0.0, (float) $oldStock->initial_stock - (float) $oldItem->quantity);
                            if ($oldMat) {
                                \App\Services\MaterialService::recalcAreaStockUnits($oldStock, $oldMat);
                            }
                            $oldStock->save();
                        }
                    }

                    // Hapus data ONT standby dari item ini
                    $linkedOnts = Ont::where('material_transaction_item_id', $oldItem->id)->lockForUpdate()->get();
                    foreach ($linkedOnts as $lo) {
                        if (!$lo->customer_id && !$lo->odp_id) {
                            $lo->delete();
                        } else {
                            $lo->update(['material_transaction_item_id' => null]);
                        }
                    }

                    $oldItem->delete();
                }
            }

            // 2. Proses update item yang ada atau tambah item baru
            $totalCost = 0;

            foreach ($validated['items'] as $itemData) {
                $material = Material::lockForUpdate()->findOrFail($itemData['material_id']);

                $existingItem = (!empty($itemData['id']) && isset($existingItems[$itemData['id']]))
                    ? $existingItems[$itemData['id']]
                    : null;

                $inputPrice = (isset($itemData['price']) && $itemData['price'] !== null && $itemData['price'] !== '')
                    ? (float) $itemData['price']
                    : null;

                // Proteksi: bila form edit mengirim kembali base_unit_price lama (misal 3.23/cm atau 106/pcs)
                // padahal purchase_unit adalah satuan besar ('pack'/'bungkus'/'roll'), abaikan $inputPrice agar tidak salah kali!
                if ($existingItem && $inputPrice !== null) {
                    $pUnitCheck = strtolower(trim((string) ($itemData['purchase_unit'] ?? 'base')));
                    $matUnitCheck = strtolower(trim((string) ($material->unit ?? 'pcs')));
                    if ($pUnitCheck !== 'base' && $pUnitCheck !== $matUnitCheck && abs($inputPrice - (float) $existingItem->price_per_unit) < 0.05) {
                        $inputPrice = null;
                    }
                }

                $pricing = \App\Services\MaterialService::calculateOrderPricing(
                    $material,
                    (float) $itemData['quantity'],
                    $itemData['purchase_unit'],
                    $inputPrice
                );

                $convertedQuantity = $pricing['converted_qty'];
                $itemTotal = $pricing['total_price'];
                $pricePerBaseUnit = $pricing['base_unit_price'];
                $totalCost += $itemTotal;

                if ($existingItem) {
                    // Revert old item stock from central & old area
                    $oldMat = Material::lockForUpdate()->find($existingItem->material_id);
                    if ($oldMat) {
                        $oldMat->stock = max(0.0, (float) $oldMat->stock + (float) $existingItem->quantity);
                        $oldMat->recalcUnitTotals()->save();
                    }
                    if ($oldArea) {
                        $oldStock = \App\Models\MaterialStock::where([
                            'material_id' => $existingItem->material_id,
                            'area_id' => $oldArea->id,
                        ])->lockForUpdate()->first();
                        if ($oldStock) {
                            $oldStock->stock = max(0.0, (float) $oldStock->stock - (float) $existingItem->quantity);
                            $oldStock->initial_stock = max(0.0, (float) $oldStock->initial_stock - (float) $existingItem->quantity);
                            if ($oldMat) {
                                \App\Services\MaterialService::recalcAreaStockUnits($oldStock, $oldMat);
                            }
                            $oldStock->save();
                        }
                    }

                    // Cek ketersediaan di Gudang Pusat untuk new quantity
                    $material->refresh();
                    if ((float) $material->stock < (float) $convertedQuantity) {
                        throw new \Exception("Stok '{$material->name}' di Gudang Pusat tidak mencukupi (Tersisa: {$material->stock} {$material->unit}, diminta: {$convertedQuantity} {$material->unit}).");
                    }

                    // Kurangi dari Gudang Pusat & tambah ke Area Baru
                    $material->stock = max(0.0, (float) $material->stock - (float) $convertedQuantity);
                    $material->recalcUnitTotals()->save();

                    $areaStockBefore = 0.0;
                    $areaStockAfter = (float) $convertedQuantity;
                    if ($newArea) {
                        $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, $newArea->id);
                        $areaStockBefore = (float) ($materialStock->stock ?? 0);
                        $areaStockAfter = $areaStockBefore + (float) $convertedQuantity;
                        $materialStock->stock = $areaStockAfter;
                        $materialStock->initial_stock = (float) ($materialStock->initial_stock ?? 0) + (float) $convertedQuantity;
                        \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();
                    }

                    // Update existing item record in place (keeps ID intact!)
                    $existingItem->update([
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'quantity' => $convertedQuantity,
                        'unit' => $material->unit,
                        'price_per_unit' => $pricePerBaseUnit,
                        'total_price' => $itemTotal,
                        'stock_before' => $areaStockBefore,
                        'stock_after' => $areaStockAfter,
                    ]);

                    // SINKRONISASI DATA ONT (JIKA SUDAH PERNAH DIKIRIM KE DATA ONT)
                    $linkedOnts = Ont::where('material_transaction_item_id', $existingItem->id)->lockForUpdate()->get();
                    $isOnt = (strtoupper($material->category ?? '') === 'ONT' || stripos($material->name ?? '', 'ont') !== false);

                    if ($linkedOnts->isNotEmpty()) {
                        if ($isOnt) {
                            $installedCount = $linkedOnts->filter(fn($o) => $o->customer_id || $o->odp_id)->count();
                            $targetCount = (int) $convertedQuantity;
                            if ($targetCount < $installedCount) {
                                throw new \Exception("Jumlah '{$material->name}' tidak dapat dikurangi menjadi {$targetCount} karena {$installedCount} unit ONT sudah terpasang pada pelanggan/ODP.");
                            }

                            // Update brand & area pada ONT yang sudah ada
                            foreach ($linkedOnts as $lo) {
                                $loUpdates = ['brand' => $material->name];
                                if (!$lo->customer_id && !$lo->odp_id) {
                                    $loUpdates['area_id'] = $newAreaId;
                                }
                                $lo->update($loUpdates);
                            }

                            // Sesuaikan jumlah ONT standby jika kuantiti berubah
                            $currentCount = $linkedOnts->count();

                            if ($targetCount > $currentCount) {
                                $diff = $targetCount - $currentCount;
                                for ($i = 0; $i < $diff; $i++) {
                                    Ont::create([
                                        'serial_number' => 'TBA-' . time() . '-' . uniqid(),
                                        'brand' => $material->name,
                                        'status' => 'Belum Set/Baru Input',
                                        'area_id' => $newAreaId,
                                        'material_transaction_item_id' => $existingItem->id,
                                    ]);
                                }
                            } elseif ($targetCount < $currentCount) {
                                $excess = $currentCount - $targetCount;
                                $removable = $linkedOnts->filter(fn($o) => !$o->customer_id && !$o->odp_id && ($o->status === 'Belum Set/Baru Input' || str_starts_with($o->serial_number, 'TBA-')))->take($excess);
                                foreach ($removable as $ro) {
                                    $ro->delete();
                                }
                            }
                        } else {
                            // Material diubah bukan ONT lagi -> hapus standby ONTs
                            foreach ($linkedOnts as $lo) {
                                if (!$lo->customer_id && !$lo->odp_id) {
                                    $lo->delete();
                                } else {
                                    $lo->update(['material_transaction_item_id' => null]);
                                }
                            }
                        }
                    }
                } else {
                    // Item baru ditambahkan
                    if ((float) $material->stock < (float) $convertedQuantity) {
                        throw new \Exception("Stok '{$material->name}' di Gudang Pusat tidak mencukupi (Tersisa: {$material->stock} {$material->unit}, diminta: {$convertedQuantity} {$material->unit}).");
                    }

                    $material->stock = max(0.0, (float) $material->stock - (float) $convertedQuantity);
                    $material->recalcUnitTotals()->save();

                    $areaStockBefore = 0.0;
                    $areaStockAfter = (float) $convertedQuantity;
                    if ($newArea) {
                        $materialStock = \App\Services\MaterialService::getOrCreateLockedAreaStock($material->id, $newArea->id);
                        $areaStockBefore = (float) ($materialStock->stock ?? 0);
                        $areaStockAfter = $areaStockBefore + (float) $convertedQuantity;
                        $materialStock->stock = $areaStockAfter;
                        $materialStock->initial_stock = (float) ($materialStock->initial_stock ?? 0) + (float) $convertedQuantity;
                        \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material)->save();
                    }

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'quantity' => $convertedQuantity,
                        'unit' => $material->unit,
                        'price_per_unit' => $pricePerBaseUnit,
                        'total_price' => $itemTotal,
                        'stock_before' => $areaStockBefore,
                        'stock_after' => $areaStockAfter,
                        'condition' => 'Layak Pakai',
                    ]);
                }
            }

            $transaction->update(['total_cost' => $totalCost]);

            // Sinkronkan perubahan ke Expense Buku Kas Umum
            $this->syncOrderExpense($transaction, $totalCost, $validated['date'], $validated['area_name']);

            DB::commit();
            return redirect()->back()->with('success', 'Perubahan Order Toko berhasil disimpan dan disinkronkan ke Keuangan, Area, dan Data ONT.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $transaction = MaterialTransaction::findOrFail($id);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('purchase_orders_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas)) {
            if ($transaction->area_id && !in_array($transaction->area_id, $accessibleAreas)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus order wilayah ini.');
            }
        }

        DB::beginTransaction();
        try {
            $transaction = MaterialTransaction::whereKey($id)->lockForUpdate()->with('items.material')->firstOrFail();
            $area = $transaction->area_id
                ? Area::find($transaction->area_id)
                : Area::where('name', $transaction->technician_name)->first();

            // Cegah pembatalan PO jika unit ONT dari PO ini sudah aktif terpasang pada pelanggan
            $itemIds = $transaction->items->pluck('id')->all();
            if (!empty($itemIds)) {
                $installedOntCount = Ont::whereIn('material_transaction_item_id', $itemIds)
                    ->whereNotNull('customer_id')
                    ->count();
                if ($installedOntCount > 0) {
                    throw new \Exception("Order Toko tidak dapat dibatalkan karena {$installedOntCount} unit ONT dari order ini sudah terpasang aktif pada pelanggan.");
                }
            }

            // Batalkan transaksi order toko (kembalikan stok ke Gudang Pusat, kurangi dari Area)
            foreach ($transaction->items as $item) {
                // Hapus data ONT standby yang belum terpakai dari order ini
                Ont::where('material_transaction_item_id', $item->id)
                    ->whereNull('customer_id')
                    ->whereNull('odp_id')
                    ->delete();
                Ont::where('material_transaction_item_id', $item->id)
                    ->update(['material_transaction_item_id' => null]);

                $material = Material::whereKey($item->material_id)->lockForUpdate()->first();
                if ($material) {
                    $material->stock = max(0.0, (float) $material->stock + (float) $item->quantity);
                    $material->recalcUnitTotals()->save();
                }

                if ($area) {
                    $materialStock = \App\Models\MaterialStock::where([
                        'material_id' => $item->material_id,
                        'area_id' => $area->id,
                    ])->lockForUpdate()->first();
                    if ($materialStock) {
                        $materialStock->stock = max(0.0, (float) $materialStock->stock - (float) $item->quantity);
                        $materialStock->initial_stock = max(0.0, (float) $materialStock->initial_stock - (float) $item->quantity);
                        if ($material) {
                            \App\Services\MaterialService::recalcAreaStockUnits($materialStock, $material);
                        }
                        $materialStock->save();
                    }
                }
            }

            // Hapus pengeluaran terkait dari Buku Kas Umum
            if ($transaction->expense_id) {
                Expense::where('id', $transaction->expense_id)->delete();
            }

            $transaction->skipStockRestoreOnDelete = true;
            $transaction->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dihapus, stok dikembalikan ke Gudang Utama, dan pengeluaran kas ditarik kembali.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronkan otomatis ke tabel expenses (Buku Kas Umum)
     */
    protected function syncOrderExpense(MaterialTransaction $transaction, float $totalCost, string $date, string $areaName): void
    {
        if ($totalCost <= 0) {
            if ($transaction->expense_id) {
                Expense::where('id', $transaction->expense_id)->delete();
                $transaction->update(['expense_id' => null]);
            }
            return;
        }

        // Cari atau buat kategori Pembelian Material
        $category = ExpenseCategory::firstOrCreate(
            ['code' => 'MAT'],
            [
                'name' => 'Pembelian Material & Restock',
                'description' => 'Pembelian kabel, ONT, dropcore, aksesoris dari toko/supplier',
                'color' => 'indigo',
                'is_active' => true,
            ]
        );

        $transaction->loadMissing('items.material');
        $itemNames = $transaction->items->map(function ($it) {
            $mName = $it->material ? $it->material->name : 'Item';
            return "{$mName} (" . (float)$it->quantity . " {$it->unit})";
        })->take(3)->implode(', ');

        if ($transaction->items->count() > 3) {
            $itemNames .= ' +' . ($transaction->items->count() - 3) . ' lainnya';
        }

        $description = "Order Toko [{$transaction->transaction_number}] Wilayah {$areaName}: {$itemNames}";

        $expensePayload = [
            'amount' => $totalCost,
            'expense_date' => $date,
            'description' => $description,
            'expense_category_id' => $category->id,
            'area_id' => $transaction->area_id,
            'area' => $areaName,
        ];

        if ($transaction->expense_id) {
            $expense = Expense::find($transaction->expense_id);
            if ($expense) {
                $expense->update($expensePayload);
                return;
            }
        }

        $expensePayload['user_id'] = auth()->id() ?? $transaction->user_id;
        $expense = Expense::create($expensePayload);

        $transaction->update(['expense_id' => $expense->id]);
    }

    public function sendOnt(Request $request, $id)
    {
        $transaction = MaterialTransaction::with('items.material')->findOrFail($id);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('purchase_orders_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $transaction->area_id && !in_array($transaction->area_id, $accessibleAreas)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk order wilayah ini.');
        }

        $areaId = $transaction->area_id;
        if (!$areaId && $transaction->technician_name) {
            $area = Area::where('name', $transaction->technician_name)->first();
            if ($area) {
                $areaId = $area->id;
            }
        }

        $ontsCreated = 0;
        $ontsUpdated = 0;
        DB::beginTransaction();
        try {
            MaterialTransaction::whereKey($transaction->id)->lockForUpdate()->first();

            foreach ($transaction->items as $item) {
                $mat = $item->material;
                $isOnt = $mat && (
                    strtoupper($mat->category ?? '') === 'ONT' ||
                    stripos($mat->name ?? '', 'ont') !== false
                );

                if ($isOnt) {
                    $existingOnts = Ont::where('material_transaction_item_id', $item->id)->lockForUpdate()->get();

                    // Update brand dan area pada ONT yang sudah ada
                    foreach ($existingOnts as $eo) {
                        $eoUpdates = ['brand' => $mat->name];
                        if (!$eo->customer_id && !$eo->odp_id) {
                            $eoUpdates['area_id'] = $areaId;
                        }
                        $eo->update($eoUpdates);
                        $ontsUpdated++;
                    }

                    $alreadySent = $existingOnts->count();
                    $needed = max(0, (int) $item->quantity - $alreadySent);

                    for ($i = 0; $i < $needed; $i++) {
                        Ont::create([
                            'serial_number' => 'TBA-' . time() . '-' . uniqid(),
                            'brand' => $mat->name,
                            'status' => 'Belum Set/Baru Input',
                            'area_id' => $areaId,
                            'material_transaction_item_id' => $item->id,
                        ]);
                        $ontsCreated++;
                    }
                }
            }
            DB::commit();
            if ($ontsCreated > 0) {
                return redirect()->back()->with('success', "{$ontsCreated} data ONT baru berhasil ditambahkan, dan {$ontsUpdated} ONT diperbarui.");
            } else {
                return redirect()->back()->with('success', 'Semua data ONT untuk order ini sudah tersinkronisasi dan diperbarui.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengirim data ONT: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $transaction = MaterialTransaction::with([
            'items' => function ($iq) {
                $iq->whereHas('material', function ($mq) {
                    $mq->where('is_active', true)->orWhereNull('is_active');
                })->with('material', 'onts');
            },
            'expense.category',
            'areaModel',
            'user',
            'orderUser',
            'giverUser',
        ])->findOrFail($id);

        $user = auth()->user();
        $isGlobal = !$user || $user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->role === 'super admin' || $user->can('purchase_orders_view_all');
        $accessibleAreas = $isGlobal ? [] : ($user ? $user->getAccessibleAreaIds() : []);

        if (!$isGlobal && !empty($accessibleAreas) && $transaction->area_id && !in_array($transaction->area_id, $accessibleAreas)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak order wilayah ini.');
        }

        $company = [
            'name' => \App\Models\Setting::get('company_name', 'VIZBILL ISP'),
            'address' => \App\Models\Setting::get('company_address', 'Sistem Manajemen Gudang & Logistik'),
            'phone' => \App\Models\Setting::get('company_phone', '0812-XXXX-XXXX'),
            'email' => \App\Models\Setting::get('company_email', 'admin@viruzs.my.id'),
        ];

        return view('print.order-toko', compact('transaction', 'company'));
    }
}
