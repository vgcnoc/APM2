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
        // Only get transactions with purpose indicating it's a purchase
        $transactions = MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')
                            ->with(['items.material', 'items.onts', 'expense.category'])
                            ->latest()
                            ->paginate(15);

        // Hanya produk aktif dari menu Data Produk yang bisa diorder
        $materials = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Inventory/PurchaseOrders/Index', [
            'transactions' => $transactions,
            'materials' => $materials,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'area_name' => 'required|string|max:255',
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
            // Generate Transaction Number (PO)
            $count = MaterialTransaction::whereDate('created_at', today())->count() + 1;
            $transactionNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $area = Area::where('name', $validated['area_name'])->first();

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'in',
                'date' => $validated['date'],
                'purpose' => 'Pembelian Toko / Supplier',
                'technician_name' => $validated['area_name'], // Reusing this column for area/wilayah name
                'area_id' => $area ? $area->id : null,
                'area' => $validated['area_name'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_cost' => 0 // Will calculate below
            ]);

            $totalCost = 0;

            foreach ($validated['items'] as $item) {
                $material = Material::lockForUpdate()->findOrFail($item['material_id']);
                
                // Smart Unit Conversion Logic
                $convertedQuantity = $item['quantity']; // Default 1:1
                
                $isIsolasi = str_contains(strtolower($material->name), 'isolasi') || str_contains(strtolower($material->category), 'isolasi');

                if ($item['purchase_unit'] === 'roll' && $material->meter_per_roll > 0) {
                    $convertedQuantity = $item['quantity'] * $material->meter_per_roll;
                } elseif ($isIsolasi && in_array(strtolower($item['purchase_unit']), ['pack', 'bungkus', 'base'])) {
                    $pcsPerPack = $material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1;
                    $cmPerPcs = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50;
                    $convertedQuantity = $item['quantity'] * ($pcsPerPack * $cmPerPcs);
                    if ($material->unit !== 'cm') {
                        $material->unit = 'cm';
                    }
                } elseif ($item['purchase_unit'] === 'pack' && $material->pcs_per_pack > 0) {
                    $convertedQuantity = $item['quantity'] * $material->pcs_per_pack;
                } elseif ($item['purchase_unit'] === 'pcs' && $material->cm_per_pcs > 0) {
                    $convertedQuantity = $item['quantity'] * $material->cm_per_pcs;
                }

                if (isset($item['price']) && $item['price'] !== null && $item['price'] !== '') {
                    $pricePerUnit = $item['price'];
                    $itemTotal = $item['quantity'] * $item['price'];
                } else {
                    // Fallback: harga dasar material per satuan stok
                    $pricePerUnit = $material->price_per_unit ?? 0;
                    $itemTotal = $convertedQuantity * $pricePerUnit;
                }
                $totalCost += $itemTotal;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $convertedQuantity, // The quantity in BASE UNIT
                    'unit' => $material->unit,
                    'price_per_unit' => $pricePerUnit,
                    'total_price' => $itemTotal
                ]);

                // Increase Stock & sinkronkan ke Data Produk
                $material->stock += $convertedQuantity;
                $material->recalcUnitTotals()->save();

                // Increase Stock in Area
                if ($area) {
                    $materialStock = \App\Models\MaterialStock::firstOrCreate([
                        'material_id' => $material->id,
                        'area_id' => $area->id,
                    ], ['stock' => 0, 'initial_stock' => 0]);
                    
                    $materialStock->increment('stock', $convertedQuantity);
                }
            }

            $transaction->update(['total_cost' => $totalCost]);

            // Sinkronkan otomatis ke Pengeluaran Kas (Buku Kas Umum)
            $this->syncOrderExpense($transaction, $totalCost, $validated['date'], $validated['area_name']);

            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dicatat, stok bertambah ke gudang & area, serta tercatat di Laporan Keuangan.');
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
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.purchase_unit' => 'required|string', 
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);
        $validated['date'] = $validated['date'] ?? now()->toDateString();

        DB::beginTransaction();
        try {
            $oldArea = Area::where('name', $transaction->technician_name)->orWhere('id', $transaction->area_id)->first();

            // 1. Revert stok lama terlebih dahulu
            foreach ($transaction->items as $oldItem) {
                $oldMaterial = Material::lockForUpdate()->find($oldItem->material_id);
                if ($oldMaterial) {
                    $oldMaterial->stock -= $oldItem->quantity;
                    $oldMaterial->recalcUnitTotals()->save();
                }

                if ($oldArea) {
                    $oldStock = \App\Models\MaterialStock::where([
                        'material_id' => $oldItem->material_id,
                        'area_id' => $oldArea->id,
                    ])->first();
                    if ($oldStock) {
                        $oldStock->decrement('stock', $oldItem->quantity);
                    }
                }
            }

            // Hapus items lama
            $transaction->items()->delete();

            $area = Area::where('name', $validated['area_name'])->first();

            // 2. Update info transaksi
            $transaction->update([
                'date' => $validated['date'],
                'technician_name' => $validated['area_name'],
                'area_id' => $area ? $area->id : null,
                'area' => $validated['area_name'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalCost = 0;

            // 3. Masukkan items baru
            foreach ($validated['items'] as $item) {
                $material = Material::lockForUpdate()->findOrFail($item['material_id']);
                
                $convertedQuantity = $item['quantity'];
                $isIsolasi = str_contains(strtolower($material->name), 'isolasi') || str_contains(strtolower($material->category), 'isolasi');

                if ($item['purchase_unit'] === 'roll' && $material->meter_per_roll > 0) {
                    $convertedQuantity = $item['quantity'] * $material->meter_per_roll;
                } elseif ($isIsolasi && in_array(strtolower($item['purchase_unit']), ['pack', 'bungkus', 'base'])) {
                    $pcsPerPack = $material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1;
                    $cmPerPcs = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50;
                    $convertedQuantity = $item['quantity'] * ($pcsPerPack * $cmPerPcs);
                    if ($material->unit !== 'cm') {
                        $material->unit = 'cm';
                    }
                } elseif ($item['purchase_unit'] === 'pack' && $material->pcs_per_pack > 0) {
                    $convertedQuantity = $item['quantity'] * $material->pcs_per_pack;
                } elseif ($item['purchase_unit'] === 'pcs' && $material->cm_per_pcs > 0) {
                    $convertedQuantity = $item['quantity'] * $material->cm_per_pcs;
                }

                if (isset($item['price']) && $item['price'] !== null && $item['price'] !== '') {
                    $pricePerUnit = $item['price'];
                    $itemTotal = $item['quantity'] * $item['price'];
                } else {
                    $pricePerUnit = $material->price_per_unit ?? 0;
                    $itemTotal = $convertedQuantity * $pricePerUnit;
                }
                $totalCost += $itemTotal;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $convertedQuantity,
                    'unit' => $material->unit,
                    'price_per_unit' => $pricePerUnit,
                    'total_price' => $itemTotal
                ]);

                // Tambah stok baru ke global
                $material->stock += $convertedQuantity;
                $material->recalcUnitTotals()->save();

                // Tambah stok baru ke Area
                if ($area) {
                    $materialStock = \App\Models\MaterialStock::firstOrCreate([
                        'material_id' => $material->id,
                        'area_id' => $area->id,
                    ], ['stock' => 0, 'initial_stock' => 0]);
                    
                    $materialStock->increment('stock', $convertedQuantity);
                }
            }

            $transaction->update(['total_cost' => $totalCost]);

            // Sinkronkan perubahan ke Expense Buku Kas Umum
            $this->syncOrderExpense($transaction, $totalCost, $validated['date'], $validated['area_name']);

            DB::commit();
            return redirect()->back()->with('success', 'Perubahan Order Toko berhasil disimpan dan disinkronkan ke Keuangan & Area.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $transaction = MaterialTransaction::findOrFail($id);

        DB::beginTransaction();
        try {
            $area = Area::where('name', $transaction->technician_name)->orWhere('id', $transaction->area_id)->first();

            // Revert stock
            foreach ($transaction->items as $item) {
                $material = $item->material;
                if ($material) {
                    $material->stock -= $item->quantity;
                    $material->recalcUnitTotals()->save();
                }

                if ($area) {
                    $materialStock = \App\Models\MaterialStock::where([
                        'material_id' => $item->material_id,
                        'area_id' => $area->id,
                    ])->first();
                    if ($materialStock) {
                        $materialStock->decrement('stock', $item->quantity);
                    }
                }
            }

            // Hapus pengeluaran terkait dari Buku Kas Umum
            if ($transaction->expense_id) {
                Expense::where('id', $transaction->expense_id)->delete();
            }

            $transaction->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dihapus, stok dikurangi, dan pengeluaran kas ditarik kembali.');
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

        if ($transaction->expense_id) {
            $expense = Expense::find($transaction->expense_id);
            if ($expense) {
                $expense->update([
                    'amount' => $totalCost,
                    'expense_date' => $date,
                    'description' => $description,
                    'expense_category_id' => $category->id,
                ]);
                return;
            }
        }

        $expense = Expense::create([
            'amount' => $totalCost,
            'expense_category_id' => $category->id,
            'description' => $description,
            'expense_date' => $date,
            'user_id' => auth()->id() ?? $transaction->user_id,
        ]);

        $transaction->update(['expense_id' => $expense->id]);
    }

    public function sendOnt(Request $request, $id)
    {
        $transaction = MaterialTransaction::with('items.material')->findOrFail($id);
        
        $areaId = $transaction->area_id;
        if (!$areaId && $transaction->technician_name) {
            $area = Area::where('name', $transaction->technician_name)->first();
            if ($area) {
                $areaId = $area->id;
            }
        }

        $ontsCreated = 0;
        DB::beginTransaction();
        try {
            foreach ($transaction->items as $item) {
                $mat = $item->material;
                $isOnt = $mat && (
                    strtoupper($mat->category ?? '') === 'ONT' ||
                    stripos($mat->name ?? '', 'ont') !== false
                );

                if ($isOnt) {
                    $alreadySent = Ont::where('material_transaction_item_id', $item->id)->count();
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
                return redirect()->back()->with('success', "{$ontsCreated} data ONT berhasil dikirim ke tabel Data ONT.");
            } else {
                return redirect()->back()->with('success', 'Semua data ONT untuk order ini sudah terkirim.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengirim data ONT: ' . $e->getMessage());
        }
    }
}
