<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        // Only get transactions with purpose indicating it's a purchase
        $transactions = MaterialTransaction::where('purpose', 'Pembelian Toko / Supplier')
                            ->with('items.material')
                            ->latest()
                            ->paginate(15);

        $materials = Material::orderBy('name')->get();
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

            $transaction = MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'type' => 'in',
                'date' => $validated['date'],
                'purpose' => 'Pembelian Toko / Supplier',
                'technician_name' => $validated['area_name'], // Reusing this column for area/wilayah name
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_cost' => 0 // Will calculate below
            ]);

            $totalCost = 0;

            $area = \App\Models\Area::where('name', $validated['area_name'])->first();

            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                
                // Smart Unit Conversion Logic
                $convertedQuantity = $item['quantity']; // Default 1:1
                
                if ($item['purchase_unit'] === 'roll' && $material->meter_per_roll > 0) {
                    $convertedQuantity = $item['quantity'] * $material->meter_per_roll;
                } elseif (($material->category === 'Isolasi' || stripos($material->name, 'isolasi') !== false) && $item['purchase_unit'] === 'pack') {
                    $convertedQuantity = $item['quantity'] * (($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1) * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50));
                } elseif ($item['purchase_unit'] === 'pack' && $material->pcs_per_pack > 0) {
                    $convertedQuantity = $item['quantity'] * $material->pcs_per_pack;
                } elseif ($item['purchase_unit'] === 'pcs' && $material->cm_per_pcs > 0) {
                    $convertedQuantity = $item['quantity'] * $material->cm_per_pcs;
                }

                if (isset($item['price']) && $item['price'] !== null && $item['price'] !== '') {
                    $pricePerPurchaseUnit = $item['price'];
                    $itemTotal = $item['quantity'] * $pricePerPurchaseUnit;
                    $pricePerBaseUnit = $convertedQuantity > 0 ? ($itemTotal / $convertedQuantity) : 0;
                } else {
                    $pricingUnitPrice = $material->price_per_unit ?? 0;
                    
                    $baseUnitsPerPricingUnit = 1;
                    if ($material->category === 'Kabel' || $material->category === 'Patchcord') {
                        $baseUnitsPerPricingUnit = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1;
                    } elseif ($material->category === 'Isolasi' || stripos($material->name, 'isolasi') !== false) {
                        $baseUnitsPerPricingUnit = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 1;
                    } elseif ($material->pcs_per_pack > 0) {
                        $baseUnitsPerPricingUnit = $material->pcs_per_pack;
                    }

                    $pricePerBaseUnit = $baseUnitsPerPricingUnit > 0 ? ($pricingUnitPrice / $baseUnitsPerPricingUnit) : 0;
                    $itemTotal = $convertedQuantity * $pricePerBaseUnit;
                }
                $totalCost += $itemTotal;

                $transactionItem = MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $convertedQuantity, 
                    'price_per_unit' => $pricePerBaseUnit,
                    'total_price' => $itemTotal
                ]);

                // Auto-create ONT records
                if ($material->category === 'ONT') {
                    for ($i = 0; $i < $convertedQuantity; $i++) {
                        \App\Models\Ont::create([
                            'brand' => $material->name,
                            'status' => 'Belum Set/Baru Input',
                            'area_id' => $area ? $area->id : null,
                            'material_transaction_item_id' => $transactionItem->id,
                        ]);
                    }
                }

                // Increase Stock globally
                $material->stock += $convertedQuantity;
                $material->save();
                
                // Increase Stock in Area
                if ($area) {
                    $materialStock = \App\Models\MaterialStock::firstOrCreate([
                        'material_id' => $material->id,
                        'area_id' => $area->id,
                    ], ['stock' => 0]);
                    
                    $materialStock->increment('stock', $convertedQuantity);
                }
            }

            $transaction->update(['total_cost' => $totalCost]);

            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dicatat dan stok telah bertambah ke inventory area.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat order: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $transaction = MaterialTransaction::findOrFail($id);

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
            $oldArea = \App\Models\Area::where('name', $transaction->technician_name)->first();
            
            $oldOntsByMaterial = [];
            // Revert old stock
            foreach ($transaction->items as $item) {
                $material = $item->material;
                if ($material) {
                    if ($material->category === 'ONT') {
                        $oldOnts = \App\Models\Ont::where('material_transaction_item_id', $item->id)->get();
                        if (!isset($oldOntsByMaterial[$material->id])) {
                            $oldOntsByMaterial[$material->id] = collect();
                        }
                        $oldOntsByMaterial[$material->id] = $oldOntsByMaterial[$material->id]->concat($oldOnts);
                        \App\Models\Ont::where('material_transaction_item_id', $item->id)->update(['material_transaction_item_id' => null]);
                    }

                    $material->stock -= $item->quantity; // $item->quantity is already in base unit
                    $material->save();
                    
                    if ($oldArea) {
                        $materialStock = \App\Models\MaterialStock::where([
                            'material_id' => $material->id,
                            'area_id' => $oldArea->id,
                        ])->first();
                        
                        if ($materialStock) {
                            $materialStock->decrement('stock', $item->quantity);
                        }
                    }
                }
            }

            // Delete old items
            $transaction->items()->delete();

            // Update transaction basic info
            $transaction->update([
                'date' => $validated['date'],
                'technician_name' => $validated['area_name'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalCost = 0;
            $newArea = \App\Models\Area::where('name', $validated['area_name'])->first();

            // Apply new stock
            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                
                // Smart Unit Conversion Logic
                $convertedQuantity = $item['quantity']; 
                
                if ($item['purchase_unit'] === 'roll' && $material->meter_per_roll > 0) {
                    $convertedQuantity = $item['quantity'] * $material->meter_per_roll;
                } elseif (($material->category === 'Isolasi' || stripos($material->name, 'isolasi') !== false) && $item['purchase_unit'] === 'pack') {
                    $convertedQuantity = $item['quantity'] * (($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1) * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50));
                } elseif ($item['purchase_unit'] === 'pack' && $material->pcs_per_pack > 0) {
                    $convertedQuantity = $item['quantity'] * $material->pcs_per_pack;
                } elseif ($item['purchase_unit'] === 'pcs' && $material->cm_per_pcs > 0) {
                    $convertedQuantity = $item['quantity'] * $material->cm_per_pcs;
                }

                if (isset($item['price']) && $item['price'] !== null && $item['price'] !== '') {
                    $pricePerPurchaseUnit = $item['price'];
                    $itemTotal = $item['quantity'] * $pricePerPurchaseUnit;
                    $pricePerBaseUnit = $convertedQuantity > 0 ? ($itemTotal / $convertedQuantity) : 0;
                } else {
                    $pricingUnitPrice = $material->price_per_unit ?? 0;
                    
                    $baseUnitsPerPricingUnit = 1;
                    if ($material->category === 'Kabel' || $material->category === 'Patchcord') {
                        $baseUnitsPerPricingUnit = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1;
                    } elseif ($material->category === 'Isolasi' || stripos($material->name, 'isolasi') !== false) {
                        $baseUnitsPerPricingUnit = $material->cm_per_pcs > 0 ? $material->cm_per_pcs : 1;
                    } elseif ($material->pcs_per_pack > 0) {
                        $baseUnitsPerPricingUnit = $material->pcs_per_pack;
                    }

                    $pricePerBaseUnit = $baseUnitsPerPricingUnit > 0 ? ($pricingUnitPrice / $baseUnitsPerPricingUnit) : 0;
                    $itemTotal = $convertedQuantity * $pricePerBaseUnit;
                }
                $totalCost += $itemTotal;

                $transactionItem = MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $convertedQuantity, 
                    'price_per_unit' => $pricePerBaseUnit,
                    'total_price' => $itemTotal
                ]);

                if ($material->category === 'ONT') {
                    $existingOnts = $oldOntsByMaterial[$material->id] ?? collect();
                    
                    for ($i = 0; $i < $convertedQuantity; $i++) {
                        if ($existingOnts->isNotEmpty()) {
                            $ont = $existingOnts->shift();
                            $ont->update([
                                'material_transaction_item_id' => $transactionItem->id,
                                'area_id' => $newArea ? $newArea->id : null
                            ]);
                        } else {
                            \App\Models\Ont::create([
                                'brand' => $material->name, 
                                'status' => 'Belum Set/Baru Input',
                                'area_id' => $newArea ? $newArea->id : null,
                                'material_transaction_item_id' => $transactionItem->id,
                            ]);
                        }
                    }
                    
                    foreach ($existingOnts as $leftoverOnt) {
                        $leftoverOnt->delete();
                    }
                }

                // Increase Stock globally
                $material->stock += $convertedQuantity;
                $material->save();
                
                // Increase Stock in Area
                if ($newArea) {
                    $materialStock = \App\Models\MaterialStock::firstOrCreate([
                        'material_id' => $material->id,
                        'area_id' => $newArea->id,
                    ], ['stock' => 0]);
                    
                    $materialStock->increment('stock', $convertedQuantity);
                }
            }

            $transaction->update(['total_cost' => $totalCost]);

            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengupdate order: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $transaction = MaterialTransaction::findOrFail($id);

        DB::beginTransaction();
        try {
            $area = \App\Models\Area::where('name', $transaction->technician_name)->first();
            
            // Revert stock
            foreach ($transaction->items as $item) {
                $material = $item->material;
                if ($material) {
                    $material->stock -= $item->quantity;
                    $material->save();
                    
                    if ($area) {
                        $materialStock = \App\Models\MaterialStock::where([
                            'material_id' => $material->id,
                            'area_id' => $area->id,
                        ])->first();
                        
                        if ($materialStock) {
                            $materialStock->decrement('stock', $item->quantity);
                        }
                    }
                }
            }

            $transaction->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Order Toko berhasil dihapus dan stok telah dikurangi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }
    }
}
