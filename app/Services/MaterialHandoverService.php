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

                // Kurangi stok di Area dan Master Material
                if ($matStock) {
                    $matStock->decrement('stock', $qty);
                }
                $material->decrement('stock', $qty);
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
                $condition = $item['condition'] ?? 'Layak Pakai';
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

                $stockCurrent = $matStock ? (float) $matStock->stock : (float) $material->stock;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemCost,
                    'stock_before' => $stockCurrent,
                    'stock_after' => $stockCurrent + $qty, // Proyeksi setelah serah terima
                    'condition' => $condition,
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
                    $stockAfter = $stockBefore + $newQty;

                    // TAMBAH STOK FISIK KE GUDANG & AREA
                    if ($matStock) {
                        $matStock->increment('stock', $newQty);
                    }
                    $material->increment('stock', $newQty);

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
        User $requester = null,
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
                    }
                    $material->decrement('stock', $qty);

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
}
