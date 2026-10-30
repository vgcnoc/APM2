<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\Ont;
use App\Models\User;
use App\Models\Area;
use App\Models\Customer;
use App\Models\PgsTicket;
use App\Models\PgkTicket;
use App\Models\PgjTicket;
use App\Models\InfrastructureWorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialHandoverService
{
    /**
     * 1. Pengeluaran material saat penugasan (Instalasi, PGS, PGK, PGJ, Rollout ODC/ODP).
     * Otomatis memotong stok inventory Gudang & Area.
     */
    public static function dispatchForAssignment(
        string $sourceType,
        ?int $sourceId,
        ?string $sourceRef,
        array $items,
        string $technicianName,
        ?int $areaId = null,
        ?string $notes = null,
        ?User $user = null
    ): MaterialTransaction {
        return DB::transaction(function () use ($sourceType, $sourceId, $sourceRef, $items, $technicianName, $areaId, $notes, $user) {
            $userId = $user?->id ?? auth()->id();
            $area = $areaId ? Area::find($areaId) : null;
            $areaName = $area?->name ?? 'Gudang Utama';

            $prefix = strtoupper(substr($sourceType, 0, 3));
            $trxNumber = 'OUT-' . $prefix . '-' . date('YmdHis') . '-' . strtoupper(Str::random(3));

            $transaction = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'out',
                'status' => 'approved',
                'handover_status' => 'none',
                'request_type' => 'regular',
                'date' => now()->toDateString(),
                'technician_name' => $technicianName,
                'purpose' => "Penugasan {$sourceType} [{$sourceRef}]",
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_reference' => $sourceRef,
                'area' => $areaName,
                'area_id' => $areaId,
                'user_id' => $userId,
                'notes' => $notes,
                'total_cost' => 0,
            ]);

            $totalCost = 0;

            foreach ($items as $item) {
                $materialId = $item['material_id'] ?? null;
                $qty = (float) ($item['quantity'] ?? 0);
                if (!$materialId || $qty <= 0) continue;

                $material = Material::find($materialId);
                if (!$material) continue;

                $unit = $item['unit'] ?? $material->unit ?? 'pcs';
                $ppu = (float) ($material->selling_price ?: ($material->price_per_unit ?? 0));
                if (strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                    $mpr = $material->meter_per_roll > 0 ? (float) $material->meter_per_roll : 1000;
                    $ppu = $ppu / $mpr;
                }
                $itemCost = $ppu * $qty;
                $totalCost += $itemCost;

                $matStock = null;
                if ($areaId) {
                    $matStock = MaterialStock::where('material_id', $material->id)
                        ->where('area_id', $areaId)
                        ->first();
                }

                $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
                $stockAfter = max(0, $stockBefore - $qty);

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemCost,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'condition' => 'Terpasang/Digunakan',
                ]);

                // Kurangi stok di Area atau Master Material (jika non-area)
                if ($matStock) {
                    $matStock->decrement('stock', $qty);
                } else {
                    $material->decrement('stock', $qty);
                }
            }

            $transaction->update(['total_cost' => $totalCost]);
            return $transaction;
        });
    }

    /**
     * 2. Pengajuan pengembalian material ke Gudang (Instalasi, PGS, PGK, PGJ, Rollout).
     * Status pending_handover (stok BELUM masuk sampai diverifikasi dan diterima oleh Petugas Gudang / Ka. Teknisi).
     */
    public static function submitReturnHandover(
        string $sourceType,
        ?int $sourceId,
        ?string $sourceRef,
        array $items,
        string $technicianName,
        ?int $areaId = null,
        ?string $notes = null,
        ?User $user = null
    ): MaterialTransaction {
        return DB::transaction(function () use ($sourceType, $sourceId, $sourceRef, $items, $technicianName, $areaId, $notes, $user) {
            $userId = $user?->id ?? auth()->id();
            $area = $areaId ? Area::find($areaId) : null;
            $areaName = $area?->name ?? 'Gudang Utama';

            $prefix = strtoupper(substr($sourceType, 0, 3));
            $trxNumber = 'RTR-' . $prefix . '-' . date('YmdHis') . '-' . strtoupper(Str::random(3));

            $transaction = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'in',
                'status' => 'pending',
                'handover_status' => 'pending_handover',
                'request_type' => 'return_handover',
                'date' => now()->toDateString(),
                'technician_name' => $technicianName,
                'purpose' => "Serah Terima Pengembalian {$sourceType} [{$sourceRef}]",
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'customer_id' => ($sourceType === 'instalasi' ? $sourceId : null),
                'source_reference' => $sourceRef,
                'area' => $areaName,
                'area_id' => $areaId,
                'user_id' => $userId,
                'notes' => $notes,
                'total_cost' => 0,
            ]);

            $totalCost = 0;

            foreach ($items as $item) {
                $materialId = $item['material_id'] ?? null;
                $qty = (float) ($item['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $material = $materialId ? Material::find($materialId) : null;
                $materialName = $item['material_name'] ?? ($material?->name ?? 'Material Tarikan / Diganti');

                $unit = $item['unit'] ?? ($material?->unit ?? 'pcs');
                $condition = $item['condition'] ?? 'Rusak';
                $ppu = $material ? (float) ($material->selling_price ?: ($material->price_per_unit ?? 0)) : 0;
                if ($material && strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                    $mpr = $material->meter_per_roll > 0 ? (float) $material->meter_per_roll : 1000;
                    $ppu = $ppu / $mpr;
                }
                $itemCost = $ppu * $qty;
                $totalCost += $itemCost;

                $matStock = null;
                if ($areaId && $material) {
                    $matStock = MaterialStock::where('material_id', $material->id)
                        ->where('area_id', $areaId)
                        ->first();
                }

                $stockCurrent = $matStock ? (float) $matStock->stock : ($material ? (float) $material->stock : 0);

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material?->id,
                    'material_name' => $materialName,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemCost,
                    'stock_before' => $stockCurrent,
                    'stock_after' => $stockCurrent + $qty, // Proyeksi setelah serah terima
                    'condition' => $condition,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $transaction->update(['total_cost' => $totalCost]);
            return $transaction;
        });
    }

    /**
     * 3. Penerimaan Serah Terima Pengembalian oleh Karyawan Gudang / Kepala Teknisi.
     * Otomatis memvalidasi fisik barang & MENAMBAHKAN STOK ke Inventory Gudang & Area.
     */
    public static function receiveHandover(
        MaterialTransaction $transaction,
        array $verifiedItems,
        User $receiver,
        ?string $notes = null
    ): MaterialTransaction {
        return DB::transaction(function () use ($transaction, $verifiedItems, $receiver, $notes) {
            $areaId = $transaction->area_id;
            $totalCost = 0;

            // Update item yang diverifikasi
            foreach ($verifiedItems as $vItem) {
                $itemId = $vItem['id'] ?? null;
                $trxItem = $itemId ? MaterialTransactionItem::find($itemId) : null;
                if (!$trxItem || $trxItem->material_transaction_id !== $transaction->id) continue;

                $newQty = isset($vItem['quantity']) ? (float) $vItem['quantity'] : (float) $trxItem->quantity;
                $newCondition = $vItem['condition'] ?? $trxItem->condition ?? 'Layak Pakai';
                $material = $trxItem->material;

                if ($material && $newQty > 0) {
                    $matStock = null;
                    if ($areaId) {
                        $matStock = MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $areaId],
                            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                        );
                    }

                    $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
                    
                    // Cek kelayakan barang: Hanya barang Layak Pakai yang menambah stok aktif inventory
                    $isLayak = (trim($newCondition) === 'Layak Pakai');
                    $stockAfter = $isLayak ? ($stockBefore + $newQty) : $stockBefore;

                    // TAMBAH STOK FISIK AKTIF KE GUDANG AREA ATAU GUDANG UTAMA HANYA JIKA LAYAK PAKAI
                    if ($isLayak) {
                        if ($matStock) {
                            $matStock->increment('stock', $newQty);
                        } else {
                            $material->increment('stock', $newQty);
                        }
                    }

                    $ppu = (float) $trxItem->price_per_unit;
                    $itemTotal = $ppu * $newQty;
                    $totalCost += $itemTotal;

                    $trxItem->update([
                        'quantity' => $newQty,
                        'condition' => $newCondition,
                        'total_price' => $itemTotal,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    // Jika ada ONT yang terhubung langsung dengan item transaksi ini
                    if ($trxItem->onts()->exists()) {
                        foreach ($trxItem->onts as $ont) {
                            $ont->update([
                                'customer_id' => null,
                                'odp_id' => null,
                                'port_number' => null,
                                'odp_port_id' => null,
                                'status' => $isLayak ? 'Sudah Set' : 'rusak',
                                'description' => trim(($ont->description ? $ont->description . ' | ' : '') . ($isLayak ? 'Diterima Gudang (Layak Pakai)' : 'Diterima Gudang (Rusak/Afkir)') . ' via ' . $transaction->transaction_number),
                            ]);
                        }
                    }
                } else {
                    $trxItem->update([
                        'quantity' => $newQty,
                        'condition' => $newCondition,
                    ]);
                }
            }

            // Jika ada ONT yang dikembalikan pada transaksi ini (misal old_ont_id dari tiket)
            if ($transaction->old_ont_id) {
                $oldOnt = Ont::find($transaction->old_ont_id);
                if ($oldOnt) {
                    $hasDamaged = collect($verifiedItems)->contains(fn($i) => in_array($i['condition'] ?? '', ['Rusak', 'Tidak Layak']));
                    if (!$hasDamaged) {
                        $oldOnt->update([
                            'customer_id' => null,
                            'odp_id' => null,
                            'port_number' => null,
                            'odp_port_id' => null,
                            'status' => 'Sudah Set', // Siap digunakan kembali di PSB/PGS/PGK
                            'description' => trim(($oldOnt->description ? $oldOnt->description . ' | ' : '') . 'Diterima kembali di Gudang (Layak Pakai) via ' . $transaction->transaction_number),
                        ]);
                    } else {
                        $oldOnt->update([
                            'customer_id' => null,
                            'odp_id' => null,
                            'port_number' => null,
                            'odp_port_id' => null,
                            'status' => 'rusak',
                            'description' => trim(($oldOnt->description ? $oldOnt->description . ' | ' : '') . 'Diterima di Gudang (Kondisi Rusak/Afkir) via ' . $transaction->transaction_number),
                        ]);
                    }
                }
            }

            $transaction->update([
                'status' => 'approved',
                'handover_status' => 'received',
                'received_by' => $receiver->id,
                'received_at' => now(),
                'received_notes' => $notes,
                'total_cost' => $totalCost,
            ]);

            return $transaction;
        });
    }

    /**
     * 4. Tolak Serah Terima Pengembalian (minta revisi ke teknisi).
     */
    public static function rejectHandover(
        MaterialTransaction $transaction,
        User $receiver,
        string $reason
    ): MaterialTransaction {
        $transaction->update([
            'status' => 'rejected',
            'handover_status' => 'rejected',
            'received_by' => $receiver->id,
            'received_at' => now(),
            'received_notes' => $reason,
        ]);
        return $transaction;
    }

    /**
     * 5. Buat Permintaan Material Tambahan atau Pergantian ONT ke Kepala Teknisi.
     */
    public static function submitRequest(
        string $sourceType,
        ?int $sourceId,
        ?string $sourceRef,
        string $requestType, // 'additional_material' | 'ont_replacement'
        array $items,
        ?int $oldOntId = null,
        ?int $replacementOntId = null,
        ?User $requester = null,
        ?string $notes = null,
        ?int $areaId = null
    ): MaterialTransaction {
        return DB::transaction(function () use ($sourceType, $sourceId, $sourceRef, $requestType, $items, $oldOntId, $replacementOntId, $requester, $notes, $areaId) {
            $userId = $requester?->id ?? auth()->id();
            $technicianName = $requester?->name ?? auth()->user()?->name ?? 'Teknisi Lapangan';

            $prefix = $requestType === 'ont_replacement' ? 'ONT' : 'MAT';
            $trxNumber = 'REQ-' . $prefix . '-' . date('YmdHis') . '-' . strtoupper(Str::random(3));

            $transaction = MaterialTransaction::create([
                'transaction_number' => $trxNumber,
                'type' => 'out',
                'status' => 'pending',
                'handover_status' => 'none',
                'request_type' => $requestType,
                'date' => now()->toDateString(),
                'technician_name' => $technicianName,
                'purpose' => ($requestType === 'ont_replacement' ? 'Permintaan Ganti ONT ' : 'Permintaan Material Tambahan ') . "{$sourceType} [{$sourceRef}]",
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_reference' => $sourceRef,
                'area_id' => $areaId,
                'user_id' => $userId,
                'old_ont_id' => $oldOntId,
                'replacement_ont_id' => $replacementOntId,
                'notes' => $notes,
                'total_cost' => 0,
            ]);

            $totalCost = 0;
            foreach ($items as $item) {
                $materialId = $item['material_id'] ?? null;
                $qty = (float) ($item['quantity'] ?? 0);
                if (!$materialId || $qty <= 0) continue;

                $material = Material::find($materialId);
                if (!$material) continue;

                $unit = $item['unit'] ?? $material->unit ?? 'pcs';
                $ppu = (float) ($material->selling_price ?: ($material->price_per_unit ?? 0));
                $itemCost = $ppu * $qty;
                $totalCost += $itemCost;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemCost,
                    'stock_before' => (float) $material->stock,
                    'stock_after' => max(0, (float) $material->stock - $qty),
                    'condition' => 'Pengajuan',
                ]);
            }

            $transaction->update(['total_cost' => $totalCost]);
            return $transaction;
        });
    }

    /**
     * 6. Persetujuan Permintaan Material / ONT oleh Kepala Teknisi.
     * Otomatis memotong stok gudang & menugaskan ONT pengganti jika pergantian ONT.
     */
    public static function approveRequest(
        MaterialTransaction $transaction,
        User $approver,
        ?string $notes = null
    ): MaterialTransaction {
        return DB::transaction(function () use ($transaction, $approver, $notes) {
            $areaId = $transaction->area_id;

            // Potong stok material
            foreach ($transaction->items as $trxItem) {
                $material = $trxItem->material;
                $qty = (float) $trxItem->quantity;

                if ($material && $qty > 0) {
                    $matStock = null;
                    if ($areaId) {
                        $matStock = MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $areaId)
                            ->first();
                    }

                    $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    if ($matStock) {
                        $matStock->decrement('stock', $qty);
                    } else {
                        $material->decrement('stock', $qty);
                    }

                    $trxItem->update([
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'condition' => 'Terpasang/Digunakan',
                    ]);
                }
            }

            // Jika ada pergantian ONT
            if ($transaction->request_type === 'ont_replacement' && $transaction->replacement_ont_id) {
                $repOnt = Ont::find($transaction->replacement_ont_id);
                if ($repOnt) {
                    $repOnt->update([
                        'status' => 'in_use',
                        'customer_id' => $transaction->customer_id ?? ($transaction->source_type === 'installation' ? $transaction->source_id : null),
                    ]);
                }

                if ($transaction->old_ont_id) {
                    $oldOnt = Ont::find($transaction->old_ont_id);
                    if ($oldOnt) {
                        $oldOnt->update([
                            'status' => 'pending_return',
                        ]);
                    }
                }
            }

            $transaction->update([
                'status' => 'approved',
                'received_by' => $approver->id,
                'received_at' => now(),
                'received_notes' => $notes ?: 'Disetujui oleh Kepala Teknisi',
            ]);

            return $transaction;
        });
    }

    /**
     * 7. Tolak Permintaan Material / ONT oleh Kepala Teknisi.
     */
    public static function rejectRequest(
        MaterialTransaction $transaction,
        User $approver,
        string $reason
    ): MaterialTransaction {
        $transaction->update([
            'status' => 'rejected',
            'received_by' => $approver->id,
            'received_at' => now(),
            'received_notes' => $reason,
        ]);
        return $transaction;
    }

    /**
     * 8. Batalkan penugasan & Kembalikan seluruh material ke Gudang & Area (Instalasi, PGS, PGK, PGJ, Rollout ODC/ODP).
     * Otomatis mengembalikan stok global master dan stok area inventory,
     * serta membersihkan transaksi pengeluaran (OUT) dan pengembalian (IN).
     */
    public static function restoreDispatchedMaterials(
        string $sourceType,
        int $sourceId,
        ?int $areaId = null,
        ?string $reference = null
    ): void {
        DB::transaction(function () use ($sourceType, $sourceId, $areaId, $reference) {
            // 1. Ambil transaksi material keluar (OUT) terkait penugasan ini
            $outTransactions = MaterialTransaction::with(['items.material', 'items.onts'])
                ->where('type', 'out')
                ->where(function ($q) use ($sourceType, $sourceId, $reference) {
                    $q->where(function ($sq) use ($sourceType, $sourceId) {
                        $sq->where('source_type', $sourceType)
                           ->where('source_id', $sourceId);
                    });

                    // Khusus instalasi / customer
                    if (in_array(strtolower($sourceType), ['instalasi', 'installation', 'customer'])) {
                        $q->orWhere('customer_id', $sourceId)
                          ->orWhere(function ($cq) use ($sourceId) {
                              $cq->whereIn('source_type', ['instalasi', 'installation', 'customer'])
                                 ->where('source_id', $sourceId);
                          });
                    }

                    // Cocokkan juga dengan reference / nomor tiket / WO jika ada
                    if ($reference) {
                        $q->orWhere('source_reference', $reference)
                          ->orWhere('transaction_number', 'like', "%{$reference}%")
                          ->orWhere('purpose', 'like', "%{$reference}%")
                          ->orWhere('notes', 'like', "%{$reference}%");
                    }
                })
                ->get();

            foreach ($outTransactions as $trx) {
                $targetAreaId = $trx->area_id ?: $areaId;

                foreach ($trx->items as $item) {
                    $material = $item->material;
                    $qty = (float) $item->quantity;

                    if ($material && $qty > 0) {
                        // Tambah stok area gudang jika ada target area, atau master material jika non-area
                        if ($targetAreaId) {
                            $matStock = MaterialStock::where('material_id', $material->id)
                                ->where('area_id', $targetAreaId)
                                ->first();
                            if ($matStock) {
                                $matStock->increment('stock', $qty);
                                if (str_contains(strtolower($material->category ?? ''), 'kabel') && $material->meter_per_roll > 0) {
                                    $matStock->total_rolls = $matStock->stock / $material->meter_per_roll;
                                    $matStock->save();
                                }
                            }
                        } else {
                            $material->increment('stock', $qty);
                            if (str_contains(strtolower($material->category ?? ''), 'kabel') && $material->meter_per_roll > 0) {
                                $material->total_rolls = $material->stock / $material->meter_per_roll;
                                $material->save();
                            }
                        }
                    }

                    // Kembalikan ONT yang terhubung ke Gudang
                    if ($item->onts && $item->onts->isNotEmpty()) {
                        foreach ($item->onts as $ont) {
                            $ont->update([
                                'customer_id' => null,
                                'odp_id' => null,
                                'odp_port_id' => null,
                                'port_number' => null,
                                'status' => 'Belum Set/Baru Input',
                                'description' => trim(($ont->description ? $ont->description . ' | ' : '') . 'Kembali ke Gudang (Batal ' . $sourceType . ')'),
                            ]);
                        }
                    }
                }

                $trx->items()->delete();
                $trx->delete();
            }

            // 2. Bersihkan transaksi pengembalian (IN / pending serah terima) terkait penugasan ini
            $inTransactions = MaterialTransaction::where('type', 'in')
                ->where(function ($q) use ($sourceType, $sourceId, $reference) {
                    $q->where('source_type', $sourceType)
                       ->where('source_id', $sourceId);

                    if (in_array(strtolower($sourceType), ['instalasi', 'installation', 'customer'])) {
                        $q->orWhere('customer_id', $sourceId);
                    }

                    if ($reference) {
                        $q->orWhere('source_reference', $reference)
                          ->orWhere('transaction_number', 'like', "%{$reference}%")
                          ->orWhere('purpose', 'like', "%{$reference}%")
                          ->orWhere('notes', 'like', "%{$reference}%");
                    }
                })
                ->get();

            foreach ($inTransactions as $inTrx) {
                $inTrx->items()->delete();
                $inTrx->delete();
            }
        });
    }
}
