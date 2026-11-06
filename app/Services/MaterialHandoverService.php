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
            $trxNumber = MaterialService::generateTransactionNumber('OUT-' . $prefix);

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
                $ppu = MaterialService::calculateUnitPrice($material, $unit);
                $itemCost = $ppu * $qty;
                $totalCost += $itemCost;

                $stockRes = MaterialService::deductOperationalStock($material, $qty, $areaId, $unit);

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemCost,
                    'stock_before' => $stockRes['stock_before'],
                    'stock_after' => $stockRes['stock_after'],
                    'condition' => 'Terpasang/Digunakan',
                ]);
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
            $trxNumber = MaterialService::generateTransactionNumber('RTR-' . $prefix);

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
                $ppu = $material ? MaterialService::calculateUnitPrice($material, $unit) : 0;
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
            $lockedTrx = MaterialTransaction::where('id', $transaction->id)->lockForUpdate()->first();
            if (!$lockedTrx) {
                throw new \Exception("Transaksi tidak ditemukan.");
            }
            if ($lockedTrx->handover_status === 'received' || $lockedTrx->status === 'approved') {
                return $lockedTrx; // Sudah pernah diproses, hindari penambahan stok ganda
            }
            $transaction = $lockedTrx;
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
                    $matStock = $areaId
                        ? MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $areaId],
                            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                        )
                        : null;

                    $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;

                    // Cek kelayakan barang: Hanya barang Layak Pakai yang menambah stok aktif inventory
                    $isLayak = (trim($newCondition) === 'Layak Pakai');
                    $stockAfter = $stockBefore;

                    // TAMBAH STOK FISIK AKTIF KE GUDANG AREA ATAU GUDANG UTAMA HANYA JIKA LAYAK PAKAI
                    if ($isLayak) {
                        $restoreRes = MaterialService::restoreOperationalStock(
                            $material,
                            $newQty,
                            $areaId,
                            $trxItem->unit
                        );
                        $stockBefore = $restoreRes['stock_before'];
                        $stockAfter = $restoreRes['stock_after'];
                    }

                    $ppu = (float) $trxItem->price_per_unit;
                    if ($ppu <= 0) {
                        $ppu = MaterialService::calculateUnitPrice($material, $trxItem->unit);
                    }
                    $itemTotal = $ppu * $newQty;
                    $totalCost += $itemTotal;

                    $trxItem->update([
                        'quantity' => $newQty,
                        'condition' => $newCondition,
                        'price_per_unit' => $ppu,
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
                    $hasDamaged = collect($verifiedItems)->contains(function ($i) {
                        $c = strtolower(trim($i['condition'] ?? ''));
                        return str_contains($c, 'rusak') || str_contains($c, 'tidak') || str_contains($c, 'lab') || str_contains($c, 'scrap');
                    });
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
        return DB::transaction(function () use ($transaction, $receiver, $reason) {
            $lockedTrx = MaterialTransaction::where('id', $transaction->id)->lockForUpdate()->first();
            if (!$lockedTrx) {
                throw new \Exception("Transaksi tidak ditemukan.");
            }
            if ($lockedTrx->handover_status === 'received' || $lockedTrx->status === 'approved') {
                throw new \Exception("Transaksi sudah disetujui/diterima, tidak dapat ditolak secara langsung.");
            }
            $lockedTrx->update([
                'status' => 'rejected',
                'handover_status' => 'rejected',
                'received_by' => $receiver->id,
                'received_at' => now(),
                'received_notes' => $reason,
            ]);
            return $lockedTrx;
        });
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
            $trxNumber = MaterialService::generateTransactionNumber('REQ-' . $prefix);

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
                $ppu = MaterialService::calculateUnitPrice($material, $unit);
                $itemCost = $ppu * $qty;
                $totalCost += $itemCost;

                $matStock = $areaId
                    ? MaterialStock::where('material_id', $material->id)->where('area_id', $areaId)->first()
                    : null;
                $currentStock = $matStock ? (float) $matStock->stock : (float) $material->stock;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemCost,
                    'stock_before' => $currentStock,
                    'stock_after' => max(0, $currentStock - $qty),
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
            $lockedTrx = MaterialTransaction::where('id', $transaction->id)->lockForUpdate()->first();
            if (!$lockedTrx) {
                throw new \Exception("Transaksi permintaan tidak ditemukan.");
            }
            if ($lockedTrx->status === 'approved') {
                return $lockedTrx; // Sudah pernah disetujui, hindari pemotongan ganda
            }
            if ($lockedTrx->status !== 'pending') {
                throw new \Exception("Permintaan tidak dalam status pending.");
            }
            $transaction = $lockedTrx;
            $areaId = $transaction->area_id;

            // Potong stok material
            foreach ($transaction->items as $trxItem) {
                $material = $trxItem->material;
                $qty = (float) $trxItem->quantity;

                if ($material && $qty > 0) {
                    $deductRes = MaterialService::deductOperationalStock(
                        $material,
                        $qty,
                        $areaId,
                        $trxItem->unit
                    );

                    $trxItem->update([
                        'stock_before' => $deductRes['stock_before'],
                        'stock_after' => $deductRes['stock_after'],
                        'condition' => 'Terpasang/Digunakan',
                    ]);
                }
            }

            // Jika ada pergantian ONT
            if ($transaction->request_type === 'ont_replacement' && $transaction->replacement_ont_id) {
                $repOnt = Ont::find($transaction->replacement_ont_id);
                $oldOnt = $transaction->old_ont_id ? Ont::find($transaction->old_ont_id) : null;

                if ($repOnt) {
                    $ontMat = MaterialService::resolveOntMaterial($repOnt);
                    if ($ontMat && !$transaction->items()->where('material_id', $ontMat->id)->exists()) {
                        $ontAreaId = $areaId ?: $repOnt->area_id;
                        $ontDeduct = MaterialService::deductOperationalStock($ontMat, 1, $ontAreaId, 'pcs');
                        $ontPpu = MaterialService::calculateUnitPrice($ontMat, 'pcs');
                        MaterialTransactionItem::create([
                            'material_transaction_id' => $transaction->id,
                            'material_id' => $ontMat->id,
                            'quantity' => 1,
                            'unit' => $ontMat->unit ?: 'pcs',
                            'price_per_unit' => $ontPpu,
                            'total_price' => $ontPpu,
                            'stock_before' => $ontDeduct['stock_before'],
                            'stock_after' => $ontDeduct['stock_after'],
                            'condition' => 'Terpasang/Digunakan',
                        ]);
                    }

                    $repUpdates = [
                        'status' => 'active',
                        'customer_id' => $transaction->customer_id ?? ($transaction->source_type === 'installation' ? $transaction->source_id : null),
                    ];
                    if ($oldOnt) {
                        if (empty($repOnt->pppoe_user) && !empty($oldOnt->pppoe_user)) {
                            $repUpdates['pppoe_user'] = $oldOnt->pppoe_user;
                            $repUpdates['pppoe_password'] = $oldOnt->pppoe_password;
                        }
                        if (empty($repOnt->vlan_id) && !empty($oldOnt->vlan_id)) {
                            $repUpdates['vlan_id'] = $oldOnt->vlan_id;
                            $repUpdates['vlan_mode'] = $oldOnt->vlan_mode;
                        }
                        if (empty($repOnt->access_mode) && !empty($oldOnt->access_mode)) {
                            $repUpdates['access_mode'] = $oldOnt->access_mode;
                        }
                        if (empty($repOnt->ip_login) && !empty($oldOnt->ip_login)) {
                            $repUpdates['ip_login'] = $oldOnt->ip_login;
                        }
                        if (empty($repOnt->login_user) && !empty($oldOnt->login_user)) {
                            $repUpdates['login_user'] = $oldOnt->login_user;
                            $repUpdates['login_password'] = $oldOnt->login_password;
                        }
                    }
                    $repOnt->update($repUpdates);
                }

                if ($oldOnt) {
                    $oldOnt->update([
                        'status' => 'pending_return',
                        'customer_id' => null,
                    ]);
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
        return DB::transaction(function () use ($transaction, $approver, $reason) {
            $lockedTrx = MaterialTransaction::where('id', $transaction->id)->lockForUpdate()->first();
            if (!$lockedTrx) {
                throw new \Exception("Transaksi permintaan tidak ditemukan.");
            }
            if ($lockedTrx->status === 'approved') {
                throw new \Exception("Permintaan sudah disetujui, tidak dapat ditolak.");
            }
            $lockedTrx->update([
                'status' => 'rejected',
                'received_by' => $approver->id,
                'received_at' => now(),
                'received_notes' => $reason,
            ]);
            return $lockedTrx;
        });
    }

    /**
     * Bersihkan & batalkan transaksi retur sisa lama (RTR-...) sebelum membuat retur baru (misal saat re-edit laporan).
     * Jika retur lama sudah berstatus 'approved' (sudah diterima gudang & menambah stok), maka penambahan tersebut dibalik dulu
     * agar tidak terjadi penggelembungan stok ganda.
     */
    public static function replacePendingOrApprovedReturnHandover(
        string $sourceType,
        int $sourceId,
        ?int $areaId = null,
        ?string $reference = null
    ): void {
        DB::transaction(function () use ($sourceType, $sourceId, $areaId, $reference) {
            $oldReturnTrxs = MaterialTransaction::with('items.material')
                ->where('type', 'in')
                ->whereNull('old_ont_id')
                ->where('purpose', '!=', 'Pembelian Toko / Supplier')
                ->where('transaction_number', 'not like', 'IN-AREA-%')
                ->where('transaction_number', 'not like', 'IN-INIT-%')
                ->where('transaction_number', 'not like', 'IN-REPAIR-%')
                ->where('transaction_number', 'not like', 'PO-%')
                ->where('transaction_number', 'not like', 'RET-DISM-%')
                ->where(function ($q) use ($sourceType, $sourceId, $reference) {
                    $q->where(function ($sq) use ($sourceType, $sourceId) {
                        $sq->where('source_type', $sourceType)
                           ->where('source_id', $sourceId);
                    });

                    if (in_array(strtolower($sourceType), ['instalasi', 'installation', 'customer'], true)) {
                        $q->orWhere(function ($cq) use ($sourceId) {
                            $cq->where('customer_id', $sourceId)
                               ->where(function ($rq) {
                                   $rq->where('transaction_number', 'like', 'RTR-%')
                                      ->orWhere('request_type', 'return_handover');
                               });
                        });
                    }

                    if ($reference) {
                        $q->orWhere('source_reference', $reference)
                          ->orWhere('purpose', 'like', "%[{$reference}]%")
                          ->orWhere('purpose', 'like', "%({$reference})%")
                          ->orWhere('purpose', 'like', "%#{$reference}%");
                    }
                })
                ->get();

            foreach ($oldReturnTrxs as $ort) {
                $targetAreaId = $ort->area_id ?: $areaId;
                if ($ort->status === 'approved') {
                    foreach ($ort->items as $item) {
                        if ($item->material && (float) $item->quantity > 0 && trim((string) $item->condition) === 'Layak Pakai') {
                            MaterialService::deductOperationalStock(
                                $item->material,
                                (float) $item->quantity,
                                $targetAreaId,
                                $item->unit
                            );
                        }
                    }
                }
                $ort->skipStockRestoreOnDelete = true;
                $ort->items()->delete();
                $ort->delete();
            }
        });
    }

    /**
     * 8. Batalkan penugasan & Kembalikan seluruh material ke Gudang & Area (Instalasi, PGS, PGK, PGJ, Rollout ODC/ODP).
     * Otomatis mengembalikan stok area / master secara konsisten, membalikkan retur yang sudah disetujui,
     * serta membersihkan transaksi pengeluaran (OUT) dan pengembalian (IN).
     *
     * @return array<int, float> Peta jumlah kuantitas (base unit) yang dipulihkan per material_id
     */
    public static function restoreDispatchedMaterials(
        string $sourceType,
        int $sourceId,
        ?int $areaId = null,
        ?string $reference = null
    ): array {
        return DB::transaction(function () use ($sourceType, $sourceId, $areaId, $reference) {
            $restoredByMaterialId = [];

            // 1. Ambil transaksi material keluar (OUT) terkait penugasan ini
            $outTransactions = MaterialTransaction::with(['items.material', 'items.onts'])
                ->where('type', 'out')
                ->where('purpose', '!=', 'Pembelian Toko / Supplier')
                ->where('transaction_number', 'not like', 'PO-%')
                ->where('transaction_number', 'not like', 'OUT-RUSAK-%')
                ->where(function ($q) use ($sourceType, $sourceId, $reference) {
                    $q->where(function ($sq) use ($sourceType, $sourceId) {
                        $sq->where('source_type', $sourceType)
                           ->where('source_id', $sourceId);
                    });

                    // Khusus instalasi / customer
                    if (in_array(strtolower($sourceType), ['instalasi', 'installation', 'customer', 'jadwal_instalasi'], true)) {
                        $q->orWhere('customer_id', $sourceId)
                          ->orWhere(function ($cq) use ($sourceId) {
                              $cq->whereIn('source_type', ['instalasi', 'installation', 'customer', 'jadwal_instalasi'])
                                 ->where('source_id', $sourceId);
                          });
                    }

                    // Cocokkan juga dengan reference / nomor tiket / WO secara aman (delimiters)
                    if ($reference) {
                        $q->orWhere('source_reference', $reference)
                          ->orWhere('purpose', 'like', "%[{$reference}]%")
                          ->orWhere('purpose', 'like', "%({$reference})%")
                          ->orWhere('purpose', 'like', "%#{$reference}%");
                    }
                })
                ->get();

            foreach ($outTransactions as $trx) {
                $targetAreaId = $trx->area_id ?: $areaId;
                $shouldRestore = ($trx->status === 'approved') && !$trx->is_migration;

                foreach ($trx->items as $item) {
                    $material = $item->material;
                    $qty = (float) $item->quantity;

                    if ($shouldRestore && $material && $qty > 0) {
                        $res = MaterialService::restoreOperationalStock(
                            $material,
                            $qty,
                            $targetAreaId,
                            $item->unit
                        );
                        $restoredByMaterialId[$material->id] = ($restoredByMaterialId[$material->id] ?? 0.0) + $res['base_qty'];
                    }

                    // Kembalikan ONT yang terhubung ke Gudang
                    if ($item->onts && $item->onts->isNotEmpty()) {
                        foreach ($item->onts as $ont) {
                            $restoredStatus = ($ont->pppoe_user || $ont->hotspot_user || $ont->status === 'Sudah Set')
                                ? 'Sudah Set'
                                : 'Belum Set/Baru Input';
                            $ont->update([
                                'customer_id' => null,
                                'odp_id' => null,
                                'odp_port_id' => null,
                                'port_number' => null,
                                'status' => $restoredStatus,
                                'description' => trim(($ont->description ? $ont->description . ' | ' : '') . 'Kembali ke Gudang (Batal ' . $sourceType . ')'),
                            ]);
                        }
                    }
                }

                if ($trx->replacement_ont_id) {
                    $repOnt = Ont::find($trx->replacement_ont_id);
                    if ($repOnt) {
                        $repOnt->update([
                            'customer_id' => null,
                            'odp_id' => null,
                            'odp_port_id' => null,
                            'port_number' => null,
                            'status' => 'Sudah Set',
                        ]);
                    }
                }

                $trx->skipStockRestoreOnDelete = true;
                $trx->items()->delete();
                $trx->delete();
            }

            // 2. Bersihkan transaksi pengembalian (IN / serah terima) terkait penugasan ini
            // Jika sudah berstatus 'approved' (sudah menambah stok gudang), balikkan penambahannya agar stok tidak terhitung ganda!
            $inTransactions = MaterialTransaction::with('items.material')
                ->where('type', 'in')
                ->where('purpose', '!=', 'Pembelian Toko / Supplier')
                ->where('transaction_number', 'not like', 'IN-AREA-%')
                ->where('transaction_number', 'not like', 'IN-INIT-%')
                ->where('transaction_number', 'not like', 'IN-REPAIR-%')
                ->where('transaction_number', 'not like', 'PO-%')
                ->where(function ($q) use ($sourceType, $sourceId, $reference) {
                    $q->where(function ($sq) use ($sourceType, $sourceId) {
                        $sq->where('source_type', $sourceType)
                           ->where('source_id', $sourceId);
                    });

                    if (in_array(strtolower($sourceType), ['instalasi', 'installation', 'customer', 'jadwal_instalasi'], true)) {
                        $q->orWhere('customer_id', $sourceId);
                    }

                    if ($reference) {
                        $q->orWhere('source_reference', $reference)
                          ->orWhere('purpose', 'like', "%[{$reference}]%")
                          ->orWhere('purpose', 'like', "%({$reference})%")
                          ->orWhere('purpose', 'like', "%#{$reference}%");
                    }
                })
                ->get();

            foreach ($inTransactions as $inTrx) {
                $targetAreaId = $inTrx->area_id ?: $areaId;
                if ($inTrx->status === 'approved' && !$inTrx->is_migration) {
                    $isDismantle = str_starts_with((string) $inTrx->transaction_number, 'RET-DISM-');
                    $shouldReverseIn = true;

                    if ($isDismantle) {
                        $dismantleDisp = \App\Models\CustomerMaterialDismantle::where('material_transaction_id', $inTrx->id)->value('disposition');
                        $shouldReverseIn = ($dismantleDisp === 'restocked');
                    }

                    if ($shouldReverseIn) {
                        foreach ($inTrx->items as $inItem) {
                            $isLayak = $isDismantle || (trim((string) $inItem->condition) === 'Layak Pakai');
                            if ($isLayak && $inItem->material && (float) $inItem->quantity > 0) {
                                MaterialService::deductOperationalStock(
                                    $inItem->material,
                                    (float) $inItem->quantity,
                                    $targetAreaId,
                                    $inItem->unit
                                );
                            }
                        }
                    }
                }

                $inTrx->skipStockRestoreOnDelete = true;
                $inTrx->items()->delete();
                $inTrx->delete();
            }

            return $restoredByMaterialId;
        });
    }
}
