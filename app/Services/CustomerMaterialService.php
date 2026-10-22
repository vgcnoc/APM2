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
                $matOnt = Material::where('category', 'like', '%ont%')
                    ->orWhere('category', 'like', '%modem%')
                    ->orWhere('name', 'like', "%{$ontBrand}%")
                    ->first();

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

            // 2. Standard FTTH accessories
            $standardAccessories = [
                ['name' => 'Kabel Dropcore 1 Core', 'qty' => 150, 'unit' => 'meter', 'category' => 'Kabel'],
                ['name' => 'Fast Connector SC/UPC', 'qty' => 2, 'unit' => 'pcs', 'category' => 'Aksesoris'],
                ['name' => 'Roset Optik 1 Core', 'qty' => 1, 'unit' => 'pcs', 'category' => 'Aksesoris'],
                ['name' => 'Patchcord SC-SC 3M', 'qty' => 1, 'unit' => 'pcs', 'category' => 'Aksesoris'],
            ];

            foreach ($standardAccessories as $acc) {
                $matchedMat = Material::where('name', 'like', '%' . $acc['name'] . '%')
                    ->orWhere('category', 'like', '%' . $acc['category'] . '%')
                    ->first();

                $item = CustomerInstalledMaterial::create([
                    'customer_id' => $customer->id,
                    'material_id' => $matchedMat?->id,
                    'item_name' => $matchedMat?->name ?: $acc['name'],
                    'quantity' => $acc['qty'],
                    'unit' => $acc['unit'],
                    'installed_at' => $customer->activation_date ?: $customer->registration_date ?: now(),
                    'installation_source' => 'psb_installation',
                    'status' => 'installed',
                    'notes' => 'Material terpasang standar instalasi awal pelanggan.',
                ]);
                $created->push($item);
            }

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

            // 2. Buat Transaksi Serah Terima (MaterialTransaction pending_handover)
            $transNumber = 'RET-DISM-' . strtoupper(substr($ticketType, 0, 3)) . '-' . date('YmdHis') . '-' . strtoupper(Str::random(3));
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
                'notes' => "Pergantian {$replacementType} material terpasang. Kondisi bekas: {$conditionLabel}. Alasan: {$damageReason}",
                'total_cost' => 0,
            ]);

            if ($materialId) {
                $material = Material::find($materialId);
                $ppu = (float) ($material?->selling_price ?: ($material?->price_per_unit ?? 0));
                
                MaterialTransactionItem::create([
                    'material_transaction_id' => $trans->id,
                    'material_id' => $materialId,
                    'quantity' => $dismantledQty,
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $ppu * $dismantledQty,
                    'stock_before' => $material ? (float) $material->stock : 0,
                    'stock_after' => $material ? (float) $material->stock : 0, // Belum bertambah sebelum disetujui gudang
                    'condition' => $conditionLabel,
                ]);
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
            $status = $isLeadTechnician ? 'received_lead' : 'received_warehouse';

            $dismantle->update([
                'handover_status' => $status,
                'received_by' => $receiver->id,
                'received_at' => now(),
                'received_notes' => $notes,
                'disposition' => $disposition,
            ]);

            // Jika barang layak pakai dan di-restock ke gudang, tambahkan stok inventori
            if ($disposition === 'restocked' && $dismantle->material_id && $dismantle->quantity > 0) {
                $material = Material::find($dismantle->material_id);
                if ($material) {
                    $customer = $dismantle->customer;
                    $areaId = $customer?->area_id;

                    if ($areaId) {
                        $matStock = MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $areaId],
                            ['stock' => 0, 'initial_stock' => 0]
                        );
                        $matStock->increment('stock', (float) $dismantle->quantity);
                    }
                    $material->increment('stock', (float) $dismantle->quantity);
                }
            }

            // Update transaksi terkait jika ada
            if ($dismantle->material_transaction_id) {
                $trans = MaterialTransaction::find($dismantle->material_transaction_id);
                if ($trans) {
                    $trans->update([
                        'status' => 'approved',
                        'handover_status' => 'received',
                        'received_by' => $receiver->id,
                        'received_at' => now(),
                        'received_notes' => $notes ?: ('Diterima: Disposisi ' . $disposition),
                    ]);
                }
            }

            return $dismantle;
        });
    }
}
