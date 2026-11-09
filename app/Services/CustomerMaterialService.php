<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerInstalledMaterial;
use App\Models\CustomerMaterialDismantle;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerMaterialService
{
    /**
     * Dapatkan daftar material terpasang di pelanggan.
     * Jika pelanggan belum memiliki data material terpasang, lakukan auto-seed cerdas
     * berbasis perangkat ONT terhubung & paket standar instalasi FTTH.
     */
    public static function getOrSeedInstalledMaterials(Customer $customer): Collection
    {
        $existing = CustomerInstalledMaterial::where('customer_id', $customer->id)
            ->whereIn('status', ['installed', 'partially_replaced'])
            ->get();

        // Pastikan perangkat ONT terpasang selalu tercatat di CustomerInstalledMaterial & sinkron dengan data ONT riil
        $existingOnt = $existing->first(function ($item) {
            $name = strtolower($item->item_name ?? '');
            return str_contains($name, 'ont') || str_contains($name, 'modem');
        });

        $customer->loadMissing('ont');
        if ($customer->ont) {
            $ontBrand = $customer->ont->brand ?: 'ONT';
            $ontModel = $customer->ont->model ?: 'Fiber Modem';
            $ontName = "ONT {$ontBrand} {$ontModel}";

            if (!$existingOnt) {
                $matOnt = Material::where(function ($mq) use ($ontBrand) {
                    $mq->where('category', 'like', '%ont%')
                       ->orWhere('category', 'like', '%modem%')
                       ->orWhere('name', 'like', "%{$ontBrand}%");
                })->first();

                $ontRecord = CustomerInstalledMaterial::create([
                    'customer_id' => $customer->id,
                    'material_id' => $matOnt?->id,
                    'item_name' => $ontName,
                    'quantity' => 1,
                    'unit' => 'unit',
                    'serial_number' => $customer->ont->serial_number,
                    'installed_at' => $customer->activation_date ?: $customer->registration_date ?: now(),
                    'installation_source' => $customer->is_migrated ? 'migration' : 'psb_installation',
                    'status' => 'installed',
                    'notes' => 'Unit ONT aktif pelanggan (SN: ' . $customer->ont->serial_number . ')',
                ]);
                $existing->push($ontRecord);
            } elseif (empty($existingOnt->serial_number) && !empty($customer->ont->serial_number)) {
                $existingOnt->update([
                    'item_name' => $ontName,
                    'serial_number' => $customer->ont->serial_number,
                    'notes' => 'Unit ONT aktif pelanggan (SN: ' . $customer->ont->serial_number . ')',
                ]);
            }
        }

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        // Auto-seed cerdas jika belum ada data sama sekali
        return DB::transaction(function () use ($customer) {
            $created = collect();

            // 1. Ambil data ONT yang terhubung dengan pelanggan
            $customer->loadMissing('ont');
            if ($customer->ont) {
                $ontBrand = $customer->ont->brand ?: 'ONT';
                $ontModel = $customer->ont->model ?: 'Fiber Modem';
                $ontName = "ONT {$ontBrand} {$ontModel}";
                
                // Cari material ONT yang cocok jika ada
                $matOnt = Material::where(function ($mq) use ($ontBrand) {
                    $mq->where('category', 'like', '%ont%')
                       ->orWhere('category', 'like', '%modem%')
                       ->orWhere('name', 'like', "%{$ontBrand}%");
                })->first();

                $ontRecord = CustomerInstalledMaterial::create([
                    'customer_id' => $customer->id,
                    'material_id' => $matOnt?->id,
                    'item_name' => $ontName,
                    'quantity' => 1,
                    'unit' => 'unit',
                    'serial_number' => $customer->ont->serial_number,
                    'installed_at' => $customer->activation_date ?: $customer->registration_date ?: now(),
                    'installation_source' => 'psb_installation',
                    'status' => 'installed',
                    'notes' => 'Unit ONT aktif pelanggan (SN: ' . $customer->ont->serial_number . ')',
                ]);
                $created->push($ontRecord);
            } else {
                $matOnt = Material::where('category', 'like', '%ont%')->orWhere('category', 'like', '%modem%')->first();
                $ontRecord = CustomerInstalledMaterial::create([
                    'customer_id' => $customer->id,
                    'material_id' => $matOnt?->id,
                    'item_name' => 'Unit ONT Modem FTTH Standar',
                    'quantity' => 1,
                    'unit' => 'unit',
                    'serial_number' => null,
                    'installed_at' => $customer->activation_date ?: $customer->registration_date ?: now(),
                    'installation_source' => 'psb_installation',
                    'status' => 'installed',
                    'notes' => 'Modem ONT terpasang pada instalasi awal pelanggan.',
                ]);
                $created->push($ontRecord);
            }

            // 2. Cek apakah ada riwayat pemakaian material aktual dari MaterialTransaction (OUT-INSTALASI, OUT-PGS, OUT-PGK)
            $outInstalasi = MaterialTransaction::with('items.material')
                ->where(function ($q) use ($customer) {
                    $q->where('customer_id', $customer->id)
                      ->orWhere(function ($sub) use ($customer) {
                          $sub->where('source_type', 'instalasi')->where('source_id', $customer->id);
                      });
                })
                ->where('transaction_number', 'like', 'OUT-INSTALASI-%')
                ->latest()
                ->first();

            if ($outInstalasi && $outInstalasi->items->isNotEmpty()) {
                foreach ($outInstalasi->items as $trxItem) {
                    $mat = $trxItem->material;
                    $isOnt = $mat && (
                        str_contains(strtolower($mat->category ?? ''), 'ont') ||
                        str_contains(strtolower($mat->category ?? ''), 'modem') ||
                        str_contains(strtolower($mat->name ?? ''), 'ont') ||
                        str_contains(strtolower($mat->name ?? ''), 'modem')
                    );

                    // Lewati ONT jika sudah dicatat di atas
                    if ($isOnt && $created->contains(fn($c) => str_contains(strtolower($c->item_name), 'ont') || str_contains(strtolower($c->item_name), 'modem'))) {
                        continue;
                    }

                    $item = CustomerInstalledMaterial::create([
                        'customer_id' => $customer->id,
                        'material_id' => $mat?->id,
                        'item_name' => $mat?->name ?: ($trxItem->notes ?: 'Material Terpasang'),
                        'quantity' => (float) $trxItem->quantity,
                        'unit' => $trxItem->unit ?: ($mat?->unit ?? 'pcs'),
                        'installed_at' => $outInstalasi->date ?: ($customer->activation_date ?: now()),
                        'installation_source' => 'psb_installation',
                        'status' => 'installed',
                        'notes' => 'Material aktual dari transaksi instalasi ' . $outInstalasi->transaction_number,
                    ]);
                    $created->push($item);
                }

                if ($created->isNotEmpty()) {
                    return $created;
                }
            }

            // 3. Jangan mengarang/auto-seed material fiktif jika tidak ada transaksi material aktual
            return $created;
        });
    }

    /**
     * Catat proses pergantian material lapangan (Seluruhnya vs Sebagian).
     * Material bekas yang dibongkar masuk antrean serah terima gudang / Ka. Teknisi.
     */
    public static function processMaterialReplacement(
        string $ticketType,
        int $ticketId,
        string $ticketNumber,
        Customer $customer,
        User $technician,
        array $replacementData
    ): CustomerMaterialDismantle {
        return DB::transaction(function () use (
            $ticketType,
            $ticketId,
            $ticketNumber,
            $customer,
            $technician,
            $replacementData
        ) {
            $installedMatId = $replacementData['installed_material_id'] ?? null;
            $installedMat = is_numeric($installedMatId) ? CustomerInstalledMaterial::find($installedMatId) : null;
            if (!$installedMat && !empty($replacementData['item_name'])) {
                $installedMat = CustomerInstalledMaterial::where('customer_id', $customer->id)
                    ->where(function ($q) use ($replacementData) {
                        $q->where('item_name', $replacementData['item_name'])
                          ->orWhere('item_name', 'like', '%' . explode(' ', $replacementData['item_name'])[0] . '%');
                    })
                    ->whereIn('status', ['installed', 'partially_replaced'])
                    ->first();
            }

            $itemName = $replacementData['item_name'] ?? $installedMat?->item_name ?? 'Material Bekas';
            $materialId = $replacementData['material_id'] ?? $installedMat?->material_id;
            $replacementType = $replacementData['replacement_type'] ?? 'full'; // 'full' | 'partial'
            $dismantledQty = (float) ($replacementData['quantity'] ?? 1);
            $unit = $replacementData['unit'] ?? $installedMat?->unit ?? 'pcs';
            $condition = $replacementData['condition'] ?? 'rusak_scrap'; // 'rusak_scrap', 'normal_reusable', 'perlu_cek'
            $damageReason = $replacementData['damage_reason'] ?? 'Kerusakan / kendala di lapangan';

            // 0. Cegah duplikasi serah terima bongkaran untuk tiket dan material yang sama (Idempotency)
            $existingDismantle = CustomerMaterialDismantle::where('ticket_type', $ticketType)
                ->where('ticket_id', $ticketId)
                ->where(function ($q) use ($materialId, $itemName, $installedMatId) {
                    if ($installedMatId) {
                        $q->where('customer_installed_material_id', $installedMatId);
                    } elseif ($materialId) {
                        $q->where('material_id', $materialId);
                    } else {
                        $q->where('item_name', $itemName);
                    }
                })
                ->first();

            if ($existingDismantle) {
                // Jika sudah diverifikasi Ka. Teknisi / Gudang, kembalikan record tanpa membuat duplikat
                if (in_array($existingDismantle->handover_status, ['received_lead', 'received_warehouse'], true)) {
                    return $existingDismantle;
                }

                $conditionLabel = match ($condition) {
                    'rusak_scrap' => 'Rusak / Afkir',
                    'normal_reusable' => 'Layak Pakai',
                    default => 'Perlu Pengecekan Lab',
                };

                $existingDismantle->update([
                    'quantity' => $dismantledQty,
                    'unit' => $unit,
                    'condition' => $condition,
                    'damage_reason' => $damageReason,
                    'replacement_type' => $replacementType,
                ]);

                if ($existingDismantle->material_transaction_id) {
                    $existingTrans = MaterialTransaction::find($existingDismantle->material_transaction_id);
                    if ($existingTrans && $existingTrans->handover_status !== 'received' && $existingTrans->status !== 'approved') {
                        $existingTrans->update([
                            'notes' => "Pergantian {$replacementType} material terpasang. Kondisi bekas: {$conditionLabel}. Alasan: {$damageReason}",
                        ]);
                        $existingItem = MaterialTransactionItem::where('material_transaction_id', $existingTrans->id)->first();
                        if ($existingItem) {
                            $existingItem->update([
                                'quantity' => $dismantledQty,
                                'unit' => $unit,
                                'condition' => $conditionLabel,
                            ]);
                        }
                    }
                }

                return $existingDismantle;
            }

            // 1. Update status pada CustomerInstalledMaterial
            if ($installedMat) {
                if ($replacementType === 'full' || $dismantledQty >= (float) $installedMat->quantity) {
                    $installedMat->update([
                        'status' => 'fully_replaced',
                        'notes' => trim(($installedMat->notes ? $installedMat->notes . ' | ' : '') . "Diganti seluruhnya pada {$ticketType} #{$ticketNumber}"),
                    ]);
                } else {
                    $newQty = max(0, (float) $installedMat->quantity - $dismantledQty);
                    $installedMat->update([
                        'quantity' => $newQty,
                        'status' => 'partially_replaced',
                        'notes' => trim(($installedMat->notes ? $installedMat->notes . ' | ' : '') . "Diganti sebagian ({$dismantledQty} {$unit}) pada {$ticketType} #{$ticketNumber}"),
                    ]);
                }
            }

            // 2. Hubungkan perangkat ONT lama jika material yang dibongkar adalah ONT
            $oldOnt = null;
            if (!empty($replacementData['ont_id'])) {
                $oldOnt = \App\Models\Ont::find($replacementData['ont_id']);
            }
            if (!$oldOnt && ($materialId || $itemName)) {
                $checkMat = $materialId ? Material::find($materialId) : null;
                $isOntItem = ($checkMat && stripos($checkMat->category ?? '', 'ont') !== false)
                    || stripos($itemName, 'ont') !== false
                    || stripos($itemName, 'modem') !== false;
                if ($isOntItem) {
                    if ($ticketType === 'pgk') {
                        $pgkObj = \App\Models\PgkTicket::find($ticketId);
                        if ($pgkObj?->old_ont_id) {
                            $oldOnt = \App\Models\Ont::find($pgkObj->old_ont_id);
                        }
                    } elseif ($ticketType === 'pgs') {
                        $pgsObj = \App\Models\PgsTicket::find($ticketId);
                        if ($pgsObj?->old_ont_id) {
                            $oldOnt = \App\Models\Ont::find($pgsObj->old_ont_id);
                        }
                    }
                    if (!$oldOnt) {
                        $oldOnt = \App\Models\Ont::where('customer_id', $customer->id)->latest('id')->first();
                    }
                }
            }

            // 3. Buat Transaksi Serah Terima (MaterialTransaction pending_handover)
            $transNumber = MaterialService::generateTransactionNumber('RET-DISM-' . strtoupper(substr($ticketType, 0, 3)));
            $areaId = $customer->area_id;

            $conditionLabel = match ($condition) {
                'rusak_scrap' => 'Rusak / Afkir',
                'normal_reusable' => 'Layak Pakai',
                default => 'Perlu Pengecekan Lab',
            };

            $trans = MaterialTransaction::create([
                'transaction_number' => $transNumber,
                'type' => 'in',
                'status' => 'pending',
                'handover_status' => 'pending_handover',
                'request_type' => 'return_handover',
                'date' => now()->toDateString(),
                'technician_name' => $technician->name,
                'purpose' => "Serah Terima Bongkaran {$ticketType} #{$ticketNumber} ({$customer->name}) - [{$replacementType}]",
                'source_type' => $ticketType,
                'source_id' => $ticketId,
                'source_reference' => $ticketNumber,
                'area' => $customer->areaModel?->name ?? 'Gudang Utama',
                'area_id' => $areaId,
                'user_id' => $technician->id,
                'customer_id' => $customer->id,
                'old_ont_id' => $oldOnt?->id,
                'notes' => "Pergantian {$replacementType} material terpasang. Kondisi bekas: {$conditionLabel}. Alasan: {$damageReason}",
                'total_cost' => 0,
            ]);

            if ($materialId) {
                $material = Material::find($materialId);
                $ppu = $material ? MaterialService::calculateUnitPrice($material, $unit) : 0;
                $matStock = ($areaId && $material)
                    ? MaterialStock::where('material_id', $material->id)->where('area_id', $areaId)->first()
                    : null;
                $currentStock = $matStock ? (float) $matStock->stock : ($material ? (float) $material->stock : 0);
                
                $createdTrxItem = MaterialTransactionItem::create([
                    'material_transaction_id' => $trans->id,
                    'material_id' => $materialId,
                    'quantity' => $dismantledQty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $ppu * $dismantledQty,
                    'stock_before' => $currentStock,
                    'stock_after' => $currentStock, // Belum bertambah sebelum disetujui gudang
                    'condition' => $conditionLabel,
                ]);

                if ($oldOnt && $createdTrxItem) {
                    $oldOnt->update(['material_transaction_item_id' => $createdTrxItem->id]);
                }
            }

            // 3. Catat di tabel customer_material_dismantles
            $dismantle = CustomerMaterialDismantle::create([
                'ticket_type' => $ticketType,
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'customer_id' => $customer->id,
                'customer_installed_material_id' => $installedMat?->id,
                'material_id' => $materialId,
                'item_name' => $itemName,
                'replacement_type' => $replacementType,
                'quantity' => $dismantledQty,
                'unit' => $unit,
                'condition' => $condition,
                'damage_reason' => $damageReason,
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'handover_status' => 'pending_handover',
                'material_transaction_id' => $trans->id,
            ]);

            // 4. Catat material baru yang dipasangkan ke pelanggan (jika ada pengganti yang ditentukan)
            $replacementMatId = $replacementData['replacement_material_id'] ?? $materialId;
            $newInstalledQty = (float) ($replacementData['replacement_quantity'] ?? $dismantledQty);
            if ($newInstalledQty > 0) {
                $newMat = $replacementMatId ? Material::find($replacementMatId) : null;
                CustomerInstalledMaterial::create([
                    'customer_id' => $customer->id,
                    'material_id' => $newMat?->id,
                    'item_name' => $newMat?->name ?: $itemName,
                    'quantity' => $newInstalledQty,
                    'unit' => $unit,
                    'installed_at' => now(),
                    'installation_source' => "{$ticketType}_maintenance",
                    'status' => 'installed',
                    'notes' => "Unit/material baru hasil pergantian {$replacementType} dari tiket {$ticketType} #{$ticketNumber}",
                ]);
            }

            return $dismantle;
        });
    }

    /**
     * Konfirmasi penerimaan serah terima material bongkaran oleh Orang Gudang atau Kepala Teknisi.
     */
    public static function confirmHandover(
        CustomerMaterialDismantle $dismantle,
        User $receiver,
        string $disposition, // 'scrap' (rusak) | 'restocked' (layak pakai/tambah stok) | 'reconditioned'
        ?string $notes = null,
        bool $isLeadTechnician = false
    ): CustomerMaterialDismantle {
        return DB::transaction(function () use ($dismantle, $receiver, $disposition, $notes, $isLeadTechnician) {
            $lockedDismantle = CustomerMaterialDismantle::where('id', $dismantle->id)->lockForUpdate()->first();
            if (!$lockedDismantle) {
                return $dismantle;
            }
            if (in_array($lockedDismantle->handover_status, ['received_lead', 'received_warehouse'], true)) {
                return $lockedDismantle; // Sudah pernah diverifikasi, cegah penambahan stok ganda!
            }
            $dismantle = $lockedDismantle;
            $status = $isLeadTechnician ? 'received_lead' : 'received_warehouse';

            $dismantle->update([
                'handover_status' => $status,
                'received_by' => $receiver->id,
                'received_at' => now(),
                'received_notes' => $notes,
                'disposition' => $disposition,
            ]);

            // Cek transaksi terkait terlebih dahulu
            $transAlreadyReceived = false;
            if ($dismantle->material_transaction_id) {
                $trans = MaterialTransaction::where('id', $dismantle->material_transaction_id)->lockForUpdate()->first();
                if ($trans) {
                    $transAlreadyReceived = ($trans->handover_status === 'received' || $trans->status === 'approved');
                    $trans->update([
                        'status' => 'approved',
                        'handover_status' => 'received',
                        'received_by' => $receiver->id,
                        'received_at' => now(),
                        'received_notes' => $notes ?: ('Diterima: Disposisi ' . $disposition),
                    ]);

                    // Perbarui kondisi item pada transaksi agar serasi dengan disposisi
                    $condLabel = ($disposition === 'restocked') ? 'Layak Pakai' : 'Rusak / Afkir';
                    foreach ($trans->items as $ti) {
                        $ti->update(['condition' => $condLabel]);
                    }
                }
            }

            // Jika barang layak pakai dan di-restock ke gudang, serta belum pernah di-restock oleh receiveHandover transaksi
            if ($disposition === 'restocked' && $dismantle->material_id && $dismantle->quantity > 0 && !$transAlreadyReceived) {
                $material = Material::find($dismantle->material_id);
                if ($material) {
                    $customer = $dismantle->customer;
                    $areaId = $customer?->area_id;
                    MaterialService::restoreOperationalStock(
                        $material,
                        (float) $dismantle->quantity,
                        $areaId,
                        $dismantle->unit
                    );
                }
            }

            return $dismantle;
        });
    }

    /**
     * Kembalikan kuantitas material ke gudang / inventaris (Area Stock & Global Stock).
     */
    public static function returnMaterialToWarehouse(
        int|Material $material,
        float $quantity,
        ?int $areaId = null,
        ?string $unit = null
    ): void {
        if ($quantity <= 0) {
            return;
        }

        $mat = $material instanceof Material ? $material : Material::find($material);
        if (!$mat) {
            return;
        }

        $res = MaterialService::restoreOperationalStock($mat, $quantity, $areaId, $unit);

        \Illuminate\Support\Facades\Log::info("[CustomerMaterialService] Berhasil mengembalikan {$res['base_qty']} {$mat->unit} material {$mat->name} (ID: {$mat->id}) ke gudang (Area: {$areaId}).");
    }
}

