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
use App\Services\MaterialHandoverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfrastructureWorkOrderService
{
    /**
     * Parse informasi pasangan Split dari teks catatan lama `[Split Unit 1: ...] | [Split Unit 2: ...]`.
     */
    public static function parseSplitUnitsFromNotes(?string $notes): array
    {
        if (empty($notes) || !str_contains($notes, '[Split Unit')) {
            return [];
        }

        $units = [];
        $pattern = '/\[Split Unit \d+:\s*(.+?)\s*\((\d+)\s*Port\)\s*-\s*Lokasi:\s*(.*?),\s*GPS:\s*([^\/,\]]+)\/([^,\]]+)(?:,\s*Kabel:\s*(.*?)\s*->\s*(.+?)\s*\(([^()]+)\))?\]/u';

        if (preg_match_all($pattern, $notes, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $cleanVal = static function ($val): ?string {
                    $v = trim((string) ($val ?? ''));
                    return ($v === '' || $v === '-' || strtolower($v) === 'null') ? null : $v;
                };

                $latRaw = $cleanVal($m[4] ?? null);
                $lngRaw = $cleanVal($m[5] ?? null);

                $units[] = [
                    'name' => $cleanVal($m[1] ?? null),
                    'ratio' => max(1, (int) ($m[2] ?? 4)),
                    'location' => $cleanVal($m[3] ?? null),
                    'latitude' => ($latRaw !== null && is_numeric($latRaw)) ? (float) $latRaw : null,
                    'longitude' => ($lngRaw !== null && is_numeric($lngRaw)) ? (float) $lngRaw : null,
                    'start_point' => $cleanVal($m[6] ?? null),
                    'end_point' => $cleanVal($m[7] ?? null),
                    'cable_pull' => $cleanVal($m[8] ?? null) ?: 'Individu',
                ];
            }
        }

        return $units;
    }

    /**
     * Ambil daftar konfigurasi 2 unit Split dari kolom JSON `split_units` atau fallback dari `notes`.
     */
    public static function resolveSplitUnits(InfrastructureWorkOrder $workOrder): array
    {
        if (is_array($workOrder->split_units) && count($workOrder->split_units) >= 2) {
            return array_values($workOrder->split_units);
        }

        return self::parseSplitUnitsFromNotes($workOrder->notes);
    }

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
    public static function getSuggestedBOM(string $type, int $capacity = 8, int $cableLengthEstimate = 150, ?int $areaId = null, ?string $deviceType = null): array
    {
        $suggestions = [];

        // 1. Box ODC / ODP
        if ($type === 'odc_deployment') {
            $box = Material::where('name', 'like', '%ODC%')->first()
                ?? Material::where('category', 'like', '%Box%')->first();
            if ($box) {
                $odcLabel = $deviceType ? "Box ODC {$deviceType} {$capacity} Core" : "Box ODC Outdoor Kapasitas {$capacity} Core";
                $suggestions[] = [
                    'material_id' => $box->id,
                    'name' => $box->name,
                    'quantity' => ($deviceType === 'Split') ? 2 : 1,
                    'unit' => $box->unit ?? 'pcs',
                    'note' => $odcLabel,
                ];
            }

            // Splitter ODC
            $splitter = Material::where('name', 'like', '%Splitter%')->first();
            if ($splitter) {
                $splitterNote = ($deviceType === 'Split') ? 'Splitter FBT Feeder ODC (Induk & Lanjutan)' : 'PLC Splitter Modular ODC';
                $suggestions[] = [
                    'material_id' => $splitter->id,
                    'name' => $splitter->name,
                    'quantity' => 1,
                    'unit' => $splitter->unit ?? 'pcs',
                    'note' => $splitterNote,
                ];
            }
        } else {
            // ODP
            $box = Material::where('name', 'like', '%ODP%')->first()
                ?? Material::where('category', 'like', '%Box%')->first();
            if ($box) {
                $odpLabel = $deviceType ? "Box ODP {$deviceType} {$capacity} Port" : "Box ODP Solid/Split {$capacity} Port";
                $suggestions[] = [
                    'material_id' => $box->id,
                    'name' => $box->name,
                    'quantity' => ($deviceType === 'Split 2') ? 2 : 1,
                    'unit' => $box->unit ?? 'pcs',
                    'note' => $odpLabel,
                ];
            }

            // Splitter ODP
            $splitter = Material::where('name', 'like', '%Splitter%')->first();
            if ($splitter) {
                $odpSplitterNote = ($deviceType === 'Split 2') ? "PLC Splitter 1:{$capacity} + FBT Split 2 (Induk & Lanjutan)" : "PLC Splitter 1:{$capacity} Mini Box";
                $suggestions[] = [
                    'material_id' => $splitter->id,
                    'name' => $splitter->name,
                    'quantity' => 1,
                    'unit' => $splitter->unit ?? 'pcs',
                    'note' => $odpSplitterNote,
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

        // Cek stok real-time per area dan referensi order toko (restock)
        foreach ($suggestions as &$sug) {
            $stock = 0;
            $trxNumber = null;
            $trxItemId = null;

            if ($areaId) {
                $matStock = MaterialStock::where('material_id', $sug['material_id'])
                    ->where('area_id', $areaId)
                    ->first();
                $stock = $matStock ? (float) $matStock->stock : 0;

                // Ambil referensi transaksi Order Toko terakhir untuk area ini
                $latestTrxItem = \App\Models\MaterialTransactionItem::where('material_id', $sug['material_id'])
                    ->whereHas('materialTransaction', function ($q) use ($areaId) {
                        $q->where(function ($sq) use ($areaId) {
                            $sq->where('area_id', $areaId)
                               ->orWhere('type', 'in');
                        });
                    })
                    ->latest('id')
                    ->first();

                if ($latestTrxItem) {
                    $trxItemId = $latestTrxItem->id;
                    $trxNumber = $latestTrxItem->materialTransaction?->transaction_number;
                }
            } else {
                $mat = Material::find($sug['material_id']);
                $stock = $mat ? (float) $mat->stock : 0;
            }

            $sug['available_stock'] = $stock;
            $sug['is_sufficient'] = $stock >= $sug['quantity'];
            $sug['trx_number'] = $trxNumber;
            $sug['material_transaction_item_id'] = $trxItemId;
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

            $isSplitDevice = ($data['type'] === 'odc_deployment' && ($data['device_type'] ?? '') === 'Split')
                || ($data['type'] === 'odp_deployment' && ($data['device_type'] ?? '') === 'Split 2');

            $workOrder = InfrastructureWorkOrder::create([
                'wo_number' => $woNumber,
                'type' => $data['type'],
                'device_type' => $data['device_type'] ?? 'Normal',
                'target_name' => $data['target_name'],
                'area_id' => $data['area_id'],
                'olt_id' => $data['olt_id'] ?? null,
                'pon_port' => $data['pon_port'] ?? null,
                'odc_id' => $data['odc_id'] ?? null,
                'odc_splitter_port' => $data['odc_splitter_port'] ?? null,
                'capacity' => $data['capacity'] ?? 8,
                'core_capacity' => $data['core_capacity'] ?? ($data['type'] === 'odc_deployment' ? ($data['capacity'] ?? null) : null),
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
                'cable_pull' => $data['cable_pull'] ?? null,
                'notes' => $data['notes'] ?? null,
                'split_units' => ($isSplitDevice && !empty($data['split_units']) && is_array($data['split_units']))
                    ? array_values($data['split_units'])
                    : null,
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
                        'material_transaction_item_id' => $mat['material_transaction_item_id'] ?? null,
                        'trx_number' => $mat['trx_number'] ?? null,
                        'requested_qty' => (float) $mat['quantity'],
                        'unit' => $mat['unit'] ?? 'pcs',
                        'status' => 'allocated',
                        'notes' => $mat['notes'] ?? ($mat['note'] ?? null),
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
            $userId = $warehouseUser?->id ?? auth()->id() ?? User::value('id');

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
                'source_type' => 'rollout_odc_odp',
                'source_id' => $workOrder->id,
                'source_reference' => $workOrder->wo_number,
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
                if ($qty <= 0) continue;

                $unit = $woMat->unit ?? $material->unit ?? 'pcs';
                $stockRes = MaterialService::deductOperationalStock($material, $qty, $workOrder->area_id, $unit);

                // Hitung estimasi biaya
                $unitPrice = (float) ($material->price_per_unit ?? 0);
                if (strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                    $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                    $unitPrice = $unitPrice / $mpr;
                }
                $cost = $qty * $unitPrice;
                $totalCost += $cost;

                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'price_per_unit' => $unitPrice,
                    'total_price' => $cost,
                    'stock_before' => $stockRes['stock_before'],
                    'stock_after' => $stockRes['stock_after'],
                    'condition' => 'Layak Pakai',
                ]);

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
     * Cerdas & Otomatis: Begitu laporan pengerjaan disubmit, sistem langsung otomatis menerbitkan
     * dan memasukkan perangkat baru ke master data ODC / ODP serta peta GIS!
     */
    public static function submitFieldReport(
        InfrastructureWorkOrder $workOrder,
        array $reportData,
        array $photos = [],
        array $opticalChecks = [],
        array $reconciliations = [],
        ?User $submittingUser = null
    ): void {
        DB::transaction(function () use ($workOrder, $reportData, $photos, $opticalChecks, $reconciliations, $submittingUser) {
            // Upload Foto-foto Bukti Lapangan
            $photoOdp = $workOrder->photo_odp;
            if (!empty($photos['photo_odp'])) {
                $dir = $workOrder->type === 'odc_deployment' ? 'infrastructure/odc' : 'infrastructure/odp';
                $photoOdp = $photos['photo_odp']->store($dir, 'public');
            }

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
                'photo_odp' => $photoOdp,
                'photo_pole' => $photoPole,
                'photo_splicing' => $photoSplicing,
                'photo_opm' => $photoOpm,
                'notes' => $reportData['notes'] ?? $workOrder->notes,
            ]);

            // Auto-update ke data ODC / ODP jika foto ODP / ODC terpasang
            if ($photoOdp) {
                self::syncPhotoToMaster($workOrder, $photoOdp);
            }

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

            // ⚡ AUTO-COMMISSIONING CERDAS: Langsung daftarkan dan aktifkan perangkat ke master data ODC / ODP & GIS
            $commissioner = $submittingUser ?? auth()->user();
            self::approveAndCommission($workOrder, $commissioner);
        });
    }

    /**
     * Persetujuan / Komisioning Otomatis ke Master Database ODC / ODP & GIS.
     * Mendukung penuh komisioning 2 unit saling terhubung (Unit Induk & Unit Lanjutan) untuk tipe Split / Split 2!
     */
    public static function approveAndCommission(InfrastructureWorkOrder $workOrder, ?User $approver = null): void
    {
        $approverUser = $approver ?? auth()->user();

        DB::transaction(function () use ($workOrder, $approverUser) {
            $createdOdcId = null;
            $createdOdpId = null;

            $lat = $workOrder->actual_latitude ?: $workOrder->planned_latitude;
            $lng = $workOrder->actual_longitude ?: $workOrder->planned_longitude;
            $photo = $workOrder->photo_odp ?: ($workOrder->photo_pole ?: $workOrder->photo_splicing);
            $defaultCablePull = $workOrder->cable_pull ?: ($workOrder->cable_length_used ? "{$workOrder->cable_length_used}m" : 'Individu');
            $techName = $workOrder->technician?->name ?? 'Teknisi';

            if ($workOrder->type === 'odc_deployment') {
                // 1. Buat / Perbarui Record ODC Baru (Mendukung Mode Split: Induk + Lanjutan)
                $odcType = $workOrder->device_type ?: ($workOrder->splitter_ratio === 'Split' ? 'Split' : 'Normal');
                $isSplit = ($odcType === 'Split') || str_contains(strtolower($workOrder->splitter_ratio ?? ''), 'split');
                $splitUnits = $isSplit ? self::resolveSplitUnits($workOrder) : [];

                if ($isSplit && count($splitUnits) >= 2) {
                    $halfCap = max(1, intdiv((int) ($workOrder->capacity ?: 8), 2));
                    $u0 = $splitUnits[0] ?? [];
                    $u1 = $splitUnits[1] ?? [];

                    $indukName = trim((string) ($u0['name'] ?? '')) ?: "{$workOrder->target_name}-A";
                    $lanjutanName = trim((string) ($u1['name'] ?? '')) ?: "{$workOrder->target_name}-B";
                    if (strcasecmp($indukName, $lanjutanName) === 0) {
                        $indukName = "{$indukName}-A";
                        $lanjutanName = "{$lanjutanName}-B";
                    }

                    $u0Lat = (isset($u0['latitude']) && $u0['latitude'] !== '' && is_numeric($u0['latitude'])) ? $u0['latitude'] : $lat;
                    $u0Lng = (isset($u0['longitude']) && $u0['longitude'] !== '' && is_numeric($u0['longitude'])) ? $u0['longitude'] : $lng;
                    $u1Lat = (isset($u1['latitude']) && $u1['latitude'] !== '' && is_numeric($u1['latitude'])) ? $u1['latitude'] : null;
                    $u1Lng = (isset($u1['longitude']) && $u1['longitude'] !== '' && is_numeric($u1['longitude'])) ? $u1['longitude'] : null;

                    $indukPayload = [
                        'olt_id' => $workOrder->olt_id,
                        'pon_port' => $workOrder->pon_port,
                        'area_id' => $workOrder->area_id,
                        'name' => $indukName,
                        'type' => 'Split',
                        'photo' => $photo,
                        'location' => !empty($u0['location']) ? $u0['location'] : $workOrder->location,
                        'latitude' => $u0Lat,
                        'longitude' => $u0Lng,
                        'capacity' => max(1, (int) ($u0['ratio'] ?? $halfCap)),
                        'splitter_ratio' => $workOrder->splitter_ratio ?: 'Split 1:2 FBT',
                        'description' => "Dibangun otomatis (Unit Induk Split, pasangan: {$lanjutanName}) via Rollout {$workOrder->wo_number} oleh {$techName}",
                        'status' => 'active',
                        'start_point' => !empty($u0['start_point']) ? $u0['start_point'] : $workOrder->start_point,
                        'end_point' => !empty($u0['end_point']) ? $u0['end_point'] : $indukName,
                        'cable_pull' => !empty($u0['cable_pull']) ? $u0['cable_pull'] : $defaultCablePull,
                        'is_split' => true,
                        'parent_odc_id' => null,
                    ];

                    $existingInduk = ($workOrder->created_odc_id ? Odc::find($workOrder->created_odc_id) : null)
                        ?? Odc::where('name', $indukName)->where('area_id', $workOrder->area_id)->first();

                    if ($existingInduk) {
                        $existingInduk->update($indukPayload);
                        $indukOdc = $existingInduk;
                    } else {
                        $indukOdc = Odc::create($indukPayload);
                    }

                    $lanjutanPayload = [
                        'olt_id' => $workOrder->olt_id,
                        'pon_port' => $workOrder->pon_port,
                        'area_id' => $workOrder->area_id,
                        'name' => $lanjutanName,
                        'type' => 'Split',
                        'photo' => $photo,
                        'location' => !empty($u1['location']) ? $u1['location'] : $workOrder->location,
                        'latitude' => $u1Lat,
                        'longitude' => $u1Lng,
                        'capacity' => max(1, (int) ($u1['ratio'] ?? $halfCap)),
                        'splitter_ratio' => $workOrder->splitter_ratio ?: 'Split 1:2 FBT',
                        'description' => "Dibangun otomatis (Unit Lanjutan Split dari Induk: {$indukOdc->name}) via Rollout {$workOrder->wo_number} oleh {$techName}",
                        'status' => 'active',
                        'start_point' => !empty($u1['start_point']) ? $u1['start_point'] : $indukOdc->name,
                        'end_point' => !empty($u1['end_point']) ? $u1['end_point'] : $lanjutanName,
                        'cable_pull' => !empty($u1['cable_pull']) ? $u1['cable_pull'] : $defaultCablePull,
                        'is_split' => true,
                        'parent_odc_id' => $indukOdc->id,
                    ];

                    $existingLanjutan = Odc::where('parent_odc_id', $indukOdc->id)->first()
                        ?? Odc::where('name', $lanjutanName)->where('area_id', $workOrder->area_id)->where('id', '!=', $indukOdc->id)->first();

                    if ($existingLanjutan) {
                        $existingLanjutan->update($lanjutanPayload);
                    } else {
                        Odc::create($lanjutanPayload);
                    }

                    $createdOdcId = $indukOdc->id;
                } else {
                    $odcPayload = [
                        'olt_id' => $workOrder->olt_id,
                        'pon_port' => $workOrder->pon_port,
                        'area_id' => $workOrder->area_id,
                        'name' => $workOrder->target_name,
                        'type' => $odcType,
                        'photo' => $photo,
                        'location' => $workOrder->location,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'capacity' => $workOrder->capacity ?: 8,
                        'splitter_ratio' => $workOrder->splitter_ratio,
                        'description' => "Dibangun otomatis via Rollout {$workOrder->wo_number} oleh {$techName}",
                        'status' => 'active',
                        'start_point' => $workOrder->start_point,
                        'end_point' => $workOrder->end_point,
                        'cable_pull' => $defaultCablePull,
                        'is_split' => $isSplit,
                    ];

                    $existingOdc = ($workOrder->created_odc_id ? Odc::find($workOrder->created_odc_id) : null)
                        ?? Odc::where('name', $workOrder->target_name)->first();
                    if ($existingOdc) {
                        $existingOdc->update($odcPayload);
                        $odc = $existingOdc;
                    } else {
                        $odc = Odc::create($odcPayload);
                    }
                    $createdOdcId = $odc->id;
                }
            } else {
                // 2. Buat / Perbarui Record ODP Baru (Mendukung Mode Split 2: Induk + Lanjutan)
                $odpType = $workOrder->device_type ?: ($workOrder->splitter_ratio === 'Split 2' ? 'Split 2' : 'Normal');
                $isSplit = ($odpType === 'Split 2') || str_contains(strtolower($workOrder->splitter_ratio ?? ''), 'split');
                $splitUnits = $isSplit ? self::resolveSplitUnits($workOrder) : [];

                if ($isSplit && count($splitUnits) >= 2) {
                    $halfPorts = max(1, intdiv((int) ($workOrder->capacity ?: 8), 2));
                    $u0 = $splitUnits[0] ?? [];
                    $u1 = $splitUnits[1] ?? [];

                    $indukName = trim((string) ($u0['name'] ?? '')) ?: "{$workOrder->target_name}-A";
                    $lanjutanName = trim((string) ($u1['name'] ?? '')) ?: "{$workOrder->target_name}-B";
                    if (strcasecmp($indukName, $lanjutanName) === 0) {
                        $indukName = "{$indukName}-A";
                        $lanjutanName = "{$lanjutanName}-B";
                    }

                    $u0Lat = (isset($u0['latitude']) && $u0['latitude'] !== '' && is_numeric($u0['latitude'])) ? $u0['latitude'] : $lat;
                    $u0Lng = (isset($u0['longitude']) && $u0['longitude'] !== '' && is_numeric($u0['longitude'])) ? $u0['longitude'] : $lng;
                    $u1Lat = (isset($u1['latitude']) && $u1['latitude'] !== '' && is_numeric($u1['latitude'])) ? $u1['latitude'] : null;
                    $u1Lng = (isset($u1['longitude']) && $u1['longitude'] !== '' && is_numeric($u1['longitude'])) ? $u1['longitude'] : null;

                    $existingInduk = ($workOrder->created_odp_id ? Odp::find($workOrder->created_odp_id) : null)
                        ?? Odp::where('name', $indukName)->where('area_id', $workOrder->area_id)->first();

                    $indukPayload = [
                        'odc_id' => $workOrder->odc_id,
                        'area_id' => $workOrder->area_id,
                        'name' => $indukName,
                        'type' => 'Split 2',
                        'address' => !empty($u0['location']) ? $u0['location'] : (!empty($u0['address']) ? $u0['address'] : $workOrder->location),
                        'latitude' => $u0Lat,
                        'longitude' => $u0Lng,
                        'start_point' => !empty($u0['start_point']) ? $u0['start_point'] : $workOrder->start_point,
                        'end_point' => !empty($u0['end_point']) ? $u0['end_point'] : $indukName,
                        'cable_pull' => !empty($u0['cable_pull']) ? $u0['cable_pull'] : $defaultCablePull,
                        'photo' => $photo,
                        'is_split' => true,
                        'parent_odp_id' => null,
                        'total_ports' => max(1, (int) ($u0['ratio'] ?? $halfPorts)),
                        'core_capacity' => $workOrder->core_capacity ?: null,
                        'used_ports' => $existingInduk ? $existingInduk->used_ports : 0,
                        'status' => $existingInduk ? $existingInduk->status : 'active',
                        'description' => "Dibangun otomatis (Unit Induk Split 2, pasangan: {$lanjutanName}) via Rollout {$workOrder->wo_number} oleh {$techName}",
                    ];

                    if ($existingInduk) {
                        $existingInduk->update($indukPayload);
                        $indukOdp = $existingInduk;
                    } else {
                        $indukOdp = Odp::create($indukPayload);
                    }

                    $existingLanjutan = Odp::where('parent_odp_id', $indukOdp->id)->first()
                        ?? Odp::where('name', $lanjutanName)->where('area_id', $workOrder->area_id)->where('id', '!=', $indukOdp->id)->first();

                    $lanjutanPayload = [
                        'odc_id' => $workOrder->odc_id,
                        'area_id' => $workOrder->area_id,
                        'name' => $lanjutanName,
                        'type' => 'Split 2',
                        'address' => !empty($u1['location']) ? $u1['location'] : (!empty($u1['address']) ? $u1['address'] : $workOrder->location),
                        'latitude' => $u1Lat,
                        'longitude' => $u1Lng,
                        'start_point' => !empty($u1['start_point']) ? $u1['start_point'] : $indukOdp->name,
                        'end_point' => !empty($u1['end_point']) ? $u1['end_point'] : $lanjutanName,
                        'cable_pull' => !empty($u1['cable_pull']) ? $u1['cable_pull'] : $defaultCablePull,
                        'photo' => $photo,
                        'is_split' => true,
                        'parent_odp_id' => $indukOdp->id,
                        'total_ports' => max(1, (int) ($u1['ratio'] ?? $halfPorts)),
                        'core_capacity' => $workOrder->core_capacity ?: null,
                        'used_ports' => $existingLanjutan ? $existingLanjutan->used_ports : 0,
                        'status' => $existingLanjutan ? $existingLanjutan->status : 'active',
                        'description' => "Dibangun otomatis (Unit Lanjutan Split 2 dari Induk: {$indukOdp->name}) via Rollout {$workOrder->wo_number} oleh {$techName}",
                    ];

                    if ($existingLanjutan) {
                        $existingLanjutan->update($lanjutanPayload);
                    } else {
                        Odp::create($lanjutanPayload);
                    }

                    $createdOdpId = $indukOdp->id;
                } else {
                    $existingOdp = ($workOrder->created_odp_id ? Odp::find($workOrder->created_odp_id) : null)
                        ?? Odp::where('name', $workOrder->target_name)->first();

                    $odpPayload = [
                        'odc_id' => $workOrder->odc_id,
                        'area_id' => $workOrder->area_id,
                        'name' => $workOrder->target_name,
                        'type' => $odpType,
                        'address' => $workOrder->location,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'start_point' => $workOrder->start_point,
                        'end_point' => $workOrder->end_point,
                        'cable_pull' => $defaultCablePull,
                        'photo' => $photo,
                        'is_split' => $isSplit,
                        'total_ports' => $workOrder->capacity ?: 8,
                        'core_capacity' => $workOrder->core_capacity ?: null,
                        'used_ports' => $existingOdp ? $existingOdp->used_ports : 0,
                        'status' => $existingOdp ? $existingOdp->status : 'active',
                        'description' => "Dibangun otomatis via Rollout {$workOrder->wo_number} oleh {$techName}",
                    ];

                    if ($existingOdp) {
                        $existingOdp->update($odpPayload);
                        $odp = $existingOdp;
                    } else {
                        $odp = Odp::create($odpPayload);
                    }
                    $createdOdpId = $odp->id;
                }
            }

            // 3. Kembalikan material retur ke Inventory Gudang melalui Serah Terima Pengembalian
            MaterialHandoverService::replacePendingOrApprovedReturnHandover(
                'rollout_odc_odp',
                $workOrder->id,
                $workOrder->area_id,
                $workOrder->wo_number
            );
            $returnedMats = $workOrder->materials->filter(fn($m) => (float) $m->returned_qty > 0);
            if ($returnedMats->isNotEmpty()) {
                $itemsToReturn = $returnedMats->map(function ($woMat) {
                    return [
                        'material_id' => $woMat->material_id,
                        'quantity' => (float) $woMat->returned_qty,
                        'unit' => $woMat->unit ?? 'pcs',
                        'condition' => 'Layak Pakai',
                    ];
                })->values()->toArray();

                MaterialHandoverService::submitReturnHandover(
                    sourceType: 'rollout_odc_odp',
                    sourceId: $workOrder->id,
                    sourceRef: $workOrder->wo_number,
                    items: $itemsToReturn,
                    technicianName: $workOrder->technician?->name ?: ($approverUser?->name ?? 'Teknisi'),
                    areaId: $workOrder->area_id,
                    notes: 'Pengembalian Sisa Material Rollout ' . ($workOrder->type === 'odc_deployment' ? 'ODC' : 'ODP') . ' ' . $workOrder->wo_number
                );
            }

            $workOrder->update([
                'status' => 'completed',
                'approved_by' => $approverUser?->id,
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

    /**
     * Sinkronkan foto fisik ODP / ODC langsung ke Master Database ODP/ODC dan Peta GIS.
     */
    public static function syncPhotoToMaster(InfrastructureWorkOrder $workOrder, string $photoPath): void
    {
        if ($workOrder->type === 'odc_deployment') {
            if ($workOrder->created_odc_id) {
                Odc::where('id', $workOrder->created_odc_id)->update(['photo' => $photoPath]);
                Odc::where('parent_odc_id', $workOrder->created_odc_id)->whereNull('photo')->update(['photo' => $photoPath]);
            } else {
                Odc::where('name', $workOrder->target_name)
                    ->where('area_id', $workOrder->area_id)
                    ->update(['photo' => $photoPath]);
            }
        } else {
            if ($workOrder->created_odp_id) {
                Odp::where('id', $workOrder->created_odp_id)->update(['photo' => $photoPath]);
                Odp::where('parent_odp_id', $workOrder->created_odp_id)->whereNull('photo')->update(['photo' => $photoPath]);
            } else {
                Odp::where('name', $workOrder->target_name)
                    ->where('area_id', $workOrder->area_id)
                    ->update(['photo' => $photoPath]);
            }
        }
    }
}
