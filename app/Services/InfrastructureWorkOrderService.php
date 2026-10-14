<?php

namespace App\Services;

use App\Models\Area;
use App\Models\InfrastructureWorkOrder;
use App\Models\InfrastructureWorkOrderMaterial;
use App\Models\InfrastructureWorkOrderOpticalCheck;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Olt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfrastructureWorkOrderService
{
    /**
     * Generate Nomor Work Order Unik (WO-ODC-YYYYMM-XXXX / WO-ODP-YYYYMM-XXXX).
     */
    public static function generateWoNumber(string $type): string
    {
        $prefix = ($type === 'odc_deployment') ? 'WO-ODC' : 'WO-ODP';
        $yearMonth = now()->format('Ym');

        $lastWo = InfrastructureWorkOrder::where('wo_number', 'like', "{$prefix}-{$yearMonth}-%")
            ->orderByDesc('id')
            ->value('wo_number');

        $nextSeq = 1;
        if ($lastWo) {
            $parts = explode('-', $lastWo);
            $nextSeq = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $yearMonth, $nextSeq);
    }

    /**
     * Auto-BOM (Bill of Materials) Generator cerdas sesuai tipe & kapasitas ODC/ODP.
     */
    public static function getSuggestedBOM(string $type, int $capacity = 8, int $cableLengthEstimate = 150, ?int $areaId = null): array
    {
        $suggestions = [];

        // 1. Box ODC / ODP
        if ($type === 'odc_deployment') {
            $box = Material::where('name', 'like', '%ODC%')->first()
                ?? Material::where('category', 'like', '%Box%')->first();
            if ($box) {
                $suggestions[] = [
                    'material_id' => $box->id,
                    'name' => $box->name,
                    'quantity' => 1,
                    'unit' => $box->unit ?? 'pcs',
                    'note' => 'Box ODC Outdoor Kapasitas ' . $capacity . ' Core',
                ];
            }

            // Splitter ODC
            $splitter = Material::where('name', 'like', '%Splitter%')->first();
            if ($splitter) {
                $suggestions[] = [
                    'material_id' => $splitter->id,
                    'name' => $splitter->name,
                    'quantity' => 1,
                    'unit' => $splitter->unit ?? 'pcs',
                    'note' => 'PLC Splitter Modular ODC',
                ];
            }
        } else {
            // ODP
            $box = Material::where('name', 'like', '%ODP%')->first()
                ?? Material::where('category', 'like', '%Box%')->first();
            if ($box) {
                $suggestions[] = [
                    'material_id' => $box->id,
                    'name' => $box->name,
                    'quantity' => 1,
                    'unit' => $box->unit ?? 'pcs',
                    'note' => "Box ODP Solid/Split {$capacity} Port",
                ];
            }

            // Splitter ODP
            $splitter = Material::where('name', 'like', '%Splitter%')->first();
            if ($splitter) {
                $suggestions[] = [
                    'material_id' => $splitter->id,
                    'name' => $splitter->name,
                    'quantity' => 1,
                    'unit' => $splitter->unit ?? 'pcs',
                    'note' => "PLC Splitter 1:{$capacity} Mini Box",
                ];
            }
        }

        // 2. Kabel Fiber Optik (Feeder / Distribusi)
        $cable = Material::where('name', 'like', '%Kabel%')
            ->orWhere('name', 'like', '%Drop%')
            ->orWhere('category', 'like', '%Kabel%')
            ->first();
        if ($cable) {
            $suggestions[] = [
                'material_id' => $cable->id,
                'name' => $cable->name,
                'quantity' => $cableLengthEstimate > 0 ? $cableLengthEstimate : 100,
                'unit' => 'meter',
                'note' => 'Kabel Tarikan Rute Fiber Optik',
            ];
        }

        // 3. Aksesoris Tiang & Splicing
        $sleeve = Material::where('name', 'like', '%Sleeve%')->first();
        if ($sleeve) {
            $suggestions[] = [
                'material_id' => $sleeve->id,
                'name' => $sleeve->name,
                'quantity' => ($type === 'odc_deployment') ? 24 : 10,
                'unit' => 'pcs',
                'note' => 'Protection Sleeve Sambungan Core',
            ];
        }

        $klem = Material::where('name', 'like', '%Klem%')
            ->orWhere('name', 'like', '%Clamp%')
            ->orWhere('name', 'like', '%Bracket%')
            ->first();
        if ($klem) {
            $suggestions[] = [
                'material_id' => $klem->id,
                'name' => $klem->name,
                'quantity' => 2,
                'unit' => 'pcs',
                'note' => 'Klem Bracket Tiang & Penambat',
            ];
        }

        // Cek stok real-time per area
        foreach ($suggestions as &$sug) {
            $stock = 0;
            if ($areaId) {
                $matStock = MaterialStock::where('material_id', $sug['material_id'])
                    ->where('area_id', $areaId)
                    ->first();
                $stock = $matStock ? (float) $matStock->stock : 0;
            } else {
                $mat = Material::find($sug['material_id']);
                $stock = $mat ? (float) $mat->stock : 0;
            }
            $sug['available_stock'] = $stock;
            $sug['is_sufficient'] = $stock >= $sug['quantity'];
        }

        return $suggestions;
    }

    /**
     * Buat Work Order Penugasan Baru & Alokasikan Material.
     */
    public static function createWorkOrder(array $data, array $materials, User $creator): InfrastructureWorkOrder
    {
        return DB::transaction(function () use ($data, $materials, $creator) {
            $woNumber = self::generateWoNumber($data['type']);

            $technicianIds = $data['technician_ids'] ?? [];
            if (empty($technicianIds) && !empty($data['technician_id'])) {
                $technicianIds = [(int) $data['technician_id']];
            }
            $leadTechId = $data['technician_id'] ?? ($technicianIds[0] ?? null);

            $workOrder = InfrastructureWorkOrder::create([
                'wo_number' => $woNumber,
                'type' => $data['type'],
                'target_name' => $data['target_name'],
                'area_id' => $data['area_id'],
                'olt_id' => $data['olt_id'] ?? null,
                'pon_port' => $data['pon_port'] ?? null,
                'odc_id' => $data['odc_id'] ?? null,
                'odc_splitter_port' => $data['odc_splitter_port'] ?? null,
                'capacity' => $data['capacity'] ?? 8,
                'splitter_ratio' => $data['splitter_ratio'] ?? null,
                'location' => $data['location'] ?? null,
                'planned_latitude' => $data['planned_latitude'] ?? null,
                'planned_longitude' => $data['planned_longitude'] ?? null,
                'technician_id' => $leadTechId,
                'created_by' => $creator->id,
                'scheduled_date' => $data['scheduled_date'] ?? now()->toDateString(),
                'status' => 'pending_material',
                'start_point' => $data['start_point'] ?? null,
                'end_point' => $data['end_point'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Hubungkan semua teknisi / tim pelaksana yang dipilih
            if (!empty($technicianIds)) {
                $attachData = [];
                foreach (array_unique($technicianIds) as $techId) {
                    $role = ($techId == $leadTechId) ? 'lead_splicer' : 'technician';
                    $attachData[$techId] = ['role' => $role];
                }
                $workOrder->technicians()->sync($attachData);
            }

            foreach ($materials as $mat) {
                if (!empty($mat['material_id']) && !empty($mat['quantity'])) {
                    InfrastructureWorkOrderMaterial::create([
                        'work_order_id' => $workOrder->id,
                        'material_id' => $mat['material_id'],
                        'requested_qty' => (float) $mat['quantity'],
                        'unit' => $mat['unit'] ?? 'pcs',
                        'status' => 'allocated',
                    ]);
                }
            }

            return $workOrder;
        });
    }

    /**
     * Konfirmasi Persiapan & Pengeluaran Material oleh Gudang (Surat Jalan Outbound).
     */
    public static function approveMaterialDispatch(InfrastructureWorkOrder $workOrder, ?User $warehouseUser = null): void
    {
        DB::transaction(function () use ($workOrder, $warehouseUser) {
            $userId = $warehouseUser?->id ?? Auth::id() ?? User::value('id');

            // Potong stok gudang
            $area = Area::find($workOrder->area_id);
            $areaName = $area?->name ?? 'Gudang Utama';

            $transaction = MaterialTransaction::create([
                'transaction_number' => 'TRX-WO-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                'type' => 'out',
                'status' => 'approved',
                'date' => now()->toDateString(),
                'technician_name' => $workOrder->technician?->name ?? 'Tim Teknisi',
                'purpose' => "Material Rollout {$workOrder->target_name} ({$workOrder->wo_number})",
                'area' => $areaName,
                'area_id' => $workOrder->area_id,
                'user_id' => $userId,
                'notes' => "Pengeluaran material resmi penugasan {$workOrder->wo_number}",
                'total_cost' => 0,
            ]);

            $totalCost = 0;

            foreach ($workOrder->materials as $woMat) {
                $material = $woMat->material ?? Material::find($woMat->material_id);
                if (!$material) continue;

                $qty = (float) $woMat->requested_qty;

                // Hitung estimasi biaya
                $unitPrice = (float) ($material->price_per_unit ?? 0);
                $cost = $qty * $unitPrice;
                $totalCost += $cost;

                $stockBefore = (float) ($material->stock ?? 0);
                $stockAfter = max(0, $stockBefore - $qty);

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $woMat->unit ?? $material->unit ?? 'pcs',
                    'price_per_unit' => $unitPrice,
                    'total_price' => $cost,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'condition' => 'Layak Pakai',
                ]);

                // Kurangi stok di MaterialStock area jika ada
                if ($workOrder->area_id) {
                    $matStock = MaterialStock::where('material_id', $material->id)
                        ->where('area_id', $workOrder->area_id)
                        ->first();
                    if ($matStock) {
                        $matStock->decrement('stock', $qty);
                    }
                }

                // Kurangi stok master
                $material->decrement('stock', $qty);

                $woMat->update([
                    'status' => 'taken',
                    'used_qty' => $qty, // default awal sama dengan yang diambil
                    'returned_qty' => 0,
                ]);
            }

            $transaction->update(['total_cost' => $totalCost]);

            $workOrder->update([
                'status' => 'material_ready',
                'material_status' => 'released',
            ]);
        });
    }

    /**
     * Submit Laporan Pelaksanaan Lapangan oleh Teknisi (QC Redaman & Bukti Foto).
     */
    public static function submitFieldReport(
        InfrastructureWorkOrder $workOrder,
        array $reportData,
        array $photos = [],
        array $opticalChecks = [],
        array $reconciliations = []
    ): void {
        DB::transaction(function () use ($workOrder, $reportData, $photos, $opticalChecks, $reconciliations) {
            // Upload Foto-foto Bukti Lapangan
            $photoPole = $workOrder->photo_pole;
            if (!empty($photos['photo_pole'])) {
                $photoPole = $photos['photo_pole']->store('infrastructure/poles', 'public');
            }

            $photoSplicing = $workOrder->photo_splicing;
            if (!empty($photos['photo_splicing'])) {
                $photoSplicing = $photos['photo_splicing']->store('infrastructure/splicing', 'public');
            }

            $photoOpm = $workOrder->photo_opm;
            if (!empty($photos['photo_opm'])) {
                $photoOpm = $photos['photo_opm']->store('infrastructure/opm', 'public');
            }

            // Hitung meteran kabel aktual
            $meterStart = isset($reportData['cable_meter_start']) ? (int) $reportData['cable_meter_start'] : $workOrder->cable_meter_start;
            $meterEnd = isset($reportData['cable_meter_end']) ? (int) $reportData['cable_meter_end'] : $workOrder->cable_meter_end;
            $cableLength = ($meterStart !== null && $meterEnd !== null && $meterEnd > $meterStart)
                ? ($meterEnd - $meterStart)
                : ($reportData['cable_length_used'] ?? $workOrder->cable_length_used);

            $workOrder->update([
                'actual_latitude' => $reportData['actual_latitude'] ?? $workOrder->actual_latitude,
                'actual_longitude' => $reportData['actual_longitude'] ?? $workOrder->actual_longitude,
                'start_point' => $reportData['start_point'] ?? $workOrder->start_point,
                'end_point' => $reportData['end_point'] ?? $workOrder->end_point,
                'cable_meter_start' => $meterStart,
                'cable_meter_end' => $meterEnd,
                'cable_length_used' => $cableLength,
                'photo_pole' => $photoPole,
                'photo_splicing' => $photoSplicing,
                'photo_opm' => $photoOpm,
                'notes' => $reportData['notes'] ?? $workOrder->notes,
                'status' => 'under_review',
            ]);

            // Simpan Uji Redaman Optik (OPM)
            if (!empty($opticalChecks)) {
                $workOrder->opticalChecks()->delete();
                foreach ($opticalChecks as $check) {
                    $rx = (float) ($check['rx_power_dbm'] ?? 0);
                    
                    // Standar QC Cerdas
                    // Pass: -16.0 s/d -23.5 dBm
                    // Warning: -23.6 s/d -25.0 dBm
                    // Fail: > -15 dBm (terlalu silau/salah panjang gelombang) atau < -25 dBm
                    $status = 'pass';
                    if ($rx < -25.0 || $rx > -15.0) {
                        $status = 'fail';
                    } elseif ($rx < -23.5) {
                        $status = 'warning';
                    }

                    InfrastructureWorkOrderOpticalCheck::create([
                        'work_order_id' => $workOrder->id,
                        'port_number' => $check['port_number'],
                        'wavelength' => $check['wavelength'] ?? '1490nm',
                        'rx_power_dbm' => $rx,
                        'status' => $status,
                        'notes' => $check['notes'] ?? null,
                    ]);
                }
            }

            // Simpan Rekonsiliasi Material (Terpakai vs Sisa/Retur)
            if (!empty($reconciliations)) {
                foreach ($reconciliations as $item) {
                    $woMat = InfrastructureWorkOrderMaterial::find($item['id'] ?? 0);
                    if ($woMat && $woMat->work_order_id === $workOrder->id) {
                        $used = (float) ($item['used_qty'] ?? $woMat->requested_qty);
                        $ret = max(0, (float) ($woMat->requested_qty - $used));
                        $woMat->update([
                            'used_qty' => $used,
                            'returned_qty' => $ret,
                            'status' => 'reconciled',
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Persetujuan NOC / Supervisor & Komisioning Otomatis ke Master Database & GIS.
     */
    public static function approveAndCommission(InfrastructureWorkOrder $workOrder, User $approver): void
    {
        DB::transaction(function () use ($workOrder, $approver) {
            $createdOdcId = null;
            $createdOdpId = null;

            $lat = $workOrder->actual_latitude ?: $workOrder->planned_latitude;
            $lng = $workOrder->actual_longitude ?: $workOrder->planned_longitude;
            $photo = $workOrder->photo_pole ?: $workOrder->photo_splicing;

            if ($workOrder->type === 'odc_deployment') {
                // 1. Buat Record ODC Baru
                $odc = Odc::create([
                    'olt_id' => $workOrder->olt_id,
                    'pon_port' => $workOrder->pon_port,
                    'area_id' => $workOrder->area_id,
                    'name' => $workOrder->target_name,
                    'type' => $workOrder->splitter_ratio === 'Split' ? 'Split' : 'Normal',
                    'photo' => $photo,
                    'location' => $workOrder->location,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'capacity' => $workOrder->capacity ?: 8,
                    'splitter_ratio' => $workOrder->splitter_ratio,
                    'description' => "Dibangun via {$workOrder->wo_number} oleh {$workOrder->technician?->name}",
                    'status' => 'active',
                    'start_point' => $workOrder->start_point,
                    'end_point' => $workOrder->end_point,
                    'cable_pull' => $workOrder->cable_length_used ? "{$workOrder->cable_length_used}m" : null,
                    'is_split' => $workOrder->splitter_ratio === 'Split',
                ]);
                $createdOdcId = $odc->id;
            } else {
                // 2. Buat Record ODP Baru
                $odp = Odp::create([
                    'odc_id' => $workOrder->odc_id,
                    'area_id' => $workOrder->area_id,
                    'name' => $workOrder->target_name,
                    'type' => $workOrder->splitter_ratio === 'Split 2' ? 'Split 2' : 'Normal',
                    'address' => $workOrder->location,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'start_point' => $workOrder->start_point,
                    'end_point' => $workOrder->end_point,
                    'cable_pull' => $workOrder->cable_length_used ? "{$workOrder->cable_length_used}m" : null,
                    'photo' => $photo,
                    'is_split' => $workOrder->splitter_ratio === 'Split 2',
                    'total_ports' => $workOrder->capacity ?: 8,
                    'used_ports' => 0,
                    'status' => 'active',
                    'description' => "Dibangun via {$workOrder->wo_number} oleh {$workOrder->technician?->name}",
                ]);
                $createdOdpId = $odp->id;
            }

            // 3. Kembalikan material retur ke gudang secara otomatis
            foreach ($workOrder->materials as $woMat) {
                $returnQty = (float) $woMat->returned_qty;
                if ($returnQty > 0) {
                    $material = $woMat->material;
                    if ($workOrder->area_id) {
                        $matStock = MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $workOrder->area_id)
                            ->first();
                        if ($matStock) {
                            $matStock->increment('stock', $returnQty);
                        }
                    }
                    $material->increment('stock', $returnQty);
                }
            }

            $workOrder->update([
                'status' => 'completed',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'created_odc_id' => $createdOdcId,
                'created_odp_id' => $createdOdpId,
            ]);
        });
    }

    /**
     * Tolak Laporan / Minta Revisi.
     */
    public static function rejectWorkOrder(InfrastructureWorkOrder $workOrder, string $reason, User $rejector): void
    {
        $workOrder->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }
}
