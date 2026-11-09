<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Ont;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SurveyNetworkService
{
    private const INVENTORY_STATUSES = ['in_stock', 'damaged', 'maintenance', 'returned'];

    /**
     * Save a survey and its physical assignment together. Changing ODP metadata
     * must not activate the customer again or disconnect their RADIUS session.
     */
    public function save(Customer $customer, Survey $survey, array $attributes): Survey
    {
        return DB::transaction(function () use ($customer, $survey, $attributes) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($survey->exists) {
                $survey = $customer->surveys()->whereKey($survey->id)->lockForUpdate()->firstOrFail();
            } else {
                $survey->customer_id = $customer->id;
            }

            $onts = $customer->onts()->orderBy('id')->lockForUpdate()->get();
            $targetOdpId = array_key_exists('odp_id', $attributes) ? $attributes['odp_id'] : $survey->odp_id;
            $targetPortNumber = array_key_exists('port_number', $attributes) ? $attributes['port_number'] : $survey->port_number;
            $feasible = ($attributes['feasibility'] ?? $survey->feasibility) === 'feasible';
            if (!$feasible && in_array($customer->status, ['active', 'suspended'], true)) {
                throw ValidationException::withMessages([
                    'feasibility' => 'Pelanggan sudah diaktivasi. Koreksi ODP harus tetap Layak (Feasible); penghentian layanan dilakukan melalui aksi pembatalan/stop pelanggan.',
                ]);
            }
            $oldAssignments = [[$survey->odp_id, $survey->port_number]];
            foreach ($onts as $ont) {
                $oldAssignments[] = [$ont->odp_id, $ont->port_number];
            }

            $odpIds = collect($oldAssignments)->pluck(0)->push($targetOdpId)->filter()->unique()->sort()->values();
            $odps = Odp::whereIn('id', $odpIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($feasible) {
                $odp = $odps->get($targetOdpId);
                $customerAreaId = $customer->area_id ?: ($customer->area ? \App\Models\Area::where('name', $customer->area)->value('id') : null);
                if (!$odp || ($customerAreaId && (int) $odp->area_id !== (int) $customerAreaId)) {
                    throw ValidationException::withMessages(['odp_id' => 'ODP tidak sesuai dengan Area/Wilayah pelanggan.']);
                }
                if (empty($customer->area_id) && $odp && $odp->area_id) {
                    $customer->update(['area_id' => $odp->area_id]);
                }
                if (!$targetPortNumber || (int) $targetPortNumber < 1 || (int) $targetPortNumber > (int) $odp->total_ports) {
                    throw ValidationException::withMessages(['port_number' => 'Port ODP harus berada dalam kapasitas ODP yang dipilih.']);
                }
                if ($onts->count() > 1) {
                    throw ValidationException::withMessages(['odp_id' => 'Pelanggan memiliki lebih dari satu ONT. Periksa relasi ONT sebelum mengubah ODP.']);
                }

                $port = OdpPort::firstOrCreate(
                    ['odp_id' => $odp->id, 'port_number' => $targetPortNumber],
                    ['status' => 'available']
                );
                $ownOnt = $onts->first();
                $ownPort = $ownOnt && (int) $ownOnt->odp_id === (int) $odp->id
                    && (int) $ownOnt->port_number === (int) $targetPortNumber;
                $ownReservation = $survey->exists && $survey->feasibility === 'feasible' && (int) $survey->odp_id === (int) $odp->id
                    && (int) $survey->port_number === (int) $targetPortNumber;
                $otherOnt = Ont::where('odp_id', $odp->id)->where('port_number', $targetPortNumber)
                    ->whereNotIn('status', self::INVENTORY_STATUSES)
                    ->when($ownOnt, fn ($q) => $q->where('id', '!=', $ownOnt->id))->exists();
                $otherSurvey = $this->reservations($odp->id, (int) $targetPortNumber)
                    ->where('customer_id', '!=', $customer->id)->exists();

                if ($otherOnt || $otherSurvey || in_array($port->status, ['fault', 'stop'], true)
                    || (in_array($port->status, ['used', 'reserved'], true) && !$ownPort && !$ownReservation)) {
                    throw ValidationException::withMessages(['port_number' => 'Port '.$targetPortNumber.' sudah digunakan/dipesan pelanggan lain atau tidak tersedia.']);
                }
            }

            $survey->fill($attributes)->save();
            if ($feasible) {
                if ($onts->isNotEmpty()) {
                    $ont = $onts->first();
                    $ontFillData = [
                        'odp_id' => $targetOdpId,
                        'port_number' => $targetPortNumber,
                        'odp_port_id' => $port->id,
                    ];
                    if ($odp && $odp->area_id && Schema::hasColumn('onts', 'area_id')) {
                        $ontFillData['area_id'] = $odp->area_id;
                    }
                    $ont->fill($ontFillData);
                    // Port flags and counters are reconciled below, without RADIUS side effects.
                    $ont->saveQuietly();
                } elseif (in_array($customer->status, ['active', 'suspended', 'installing'], true)) {
                    // Jika pelanggan berstatus pasang/aktif tapi belum punya ONT record, sinkronkan ONT
                    $newOntData = [
                        'customer_id' => $customer->id,
                        'odp_id' => $targetOdpId,
                        'port_number' => $targetPortNumber,
                        'odp_port_id' => $port->id,
                        'serial_number' => 'SN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'status' => 'active',
                    ];
                    if ($odp && $odp->area_id && Schema::hasColumn('onts', 'area_id')) {
                        $newOntData['area_id'] = $odp->area_id;
                    }
                    $createdOnt = Ont::create($newOntData);
                    $onts = collect([$createdOnt]);
                }
            }

            $assignments = collect($oldAssignments)->push([$survey->odp_id, $survey->port_number])
                ->filter(fn ($pair) => $pair[0] && $pair[1])
                ->unique(fn ($pair) => $pair[0].':'.$pair[1]);
            foreach ($assignments as [$odpId, $portNumber]) {
                $port = OdpPort::where('odp_id', $odpId)->where('port_number', $portNumber)->first();
                if (!$port || in_array($port->status, ['fault', 'stop'], true)) {
                    continue;
                }
                $used = Ont::where('odp_id', $odpId)->where('port_number', $portNumber)
                    ->whereNotIn('status', self::INVENTORY_STATUSES)->exists();
                $reserved = $this->reservations((int) $odpId, (int) $portNumber)->exists()
                    || ($feasible && !$onts->contains(fn ($ont) => !in_array($ont->status, self::INVENTORY_STATUSES, true))
                        && (int) $odpId === (int) $survey->odp_id
                        && (int) $portNumber === (int) $survey->port_number);
                $state = $used ? 'used' : ($reserved ? 'reserved' : 'available');
                $port->update($state === 'available' ? ['status' => $state, 'description' => null] : ['status' => $state]);
            }

            foreach ($odps as $odp) {
                $used = Ont::where('odp_id', $odp->id)->whereNotIn('status', self::INVENTORY_STATUSES)->count();
                $status = $used >= $odp->total_ports && $odp->total_ports > 0
                    ? 'full' : ($odp->status === 'full' ? 'active' : $odp->status);
                Odp::whereKey($odp->id)->update(['used_ports' => $used, 'status' => $status]);
            }

            return $survey->fresh();
        });
    }

    /**
     * Synchronize installation & ONT to ODP and port, keeping Survey, OdpPort, and ODP seamlessly in sync.
     */
    public function syncInstallation(Customer $customer, array $attributes): Ont
    {
        return DB::transaction(function () use ($customer, $attributes) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $targetOdpId = (int) $attributes['odp_id'];
            $targetPortNumber = (int) $attributes['port_number'];

            $odp = Odp::whereKey($targetOdpId)->lockForUpdate()->first();
            if (!$odp) {
                throw ValidationException::withMessages(['odp_id' => 'ODP yang dipilih tidak ditemukan.']);
            }

            $customerAreaId = $customer->area_id ?: ($customer->area ? \App\Models\Area::where('name', $customer->area)->value('id') : null);
            if ($customerAreaId && (int) $odp->area_id !== (int) $customerAreaId) {
                throw ValidationException::withMessages(['odp_id' => 'ODP tidak sesuai dengan Area/Wilayah pelanggan.']);
            }
            if (empty($customer->area_id) && $odp->area_id) {
                $customer->update(['area_id' => $odp->area_id]);
            }

            if ($targetPortNumber < 1 || $targetPortNumber > (int) $odp->total_ports) {
                throw ValidationException::withMessages(['port_number' => 'Port ODP harus berada dalam kapasitas ODP (1 - ' . $odp->total_ports . ').']);
            }

            // Existing ONT and Survey
            $ont = $customer->ont ?: $customer->onts()->orderBy('id')->lockForUpdate()->first();
            if (!$ont && !empty($attributes['serial_number'])) {
                $ont = Ont::where('serial_number', $attributes['serial_number'])
                    ->where(function ($q) use ($customer) {
                        $q->whereNull('customer_id')->orWhere('customer_id', $customer->id);
                    })
                    ->lockForUpdate()
                    ->first();
                if ($ont) {
                    $ont->customer_id = $customer->id;
                }
            } elseif ($ont && !empty($attributes['serial_number'])) {
                // Jika ada stub ONT gudang (belum terhubung ke pelanggan) dengan SN yang sama, gabungkan agar tidak duplikat
                $inventoryStub = Ont::where('serial_number', $attributes['serial_number'])
                    ->where('id', '!=', $ont->id)
                    ->whereNull('customer_id')
                    ->lockForUpdate()
                    ->first();
                if ($inventoryStub) {
                    if (empty($ont->brand) && !empty($inventoryStub->brand)) {
                        $ont->brand = $inventoryStub->brand;
                    }
                    if (empty($ont->material_transaction_item_id) && !empty($inventoryStub->material_transaction_item_id)) {
                        $ont->material_transaction_item_id = $inventoryStub->material_transaction_item_id;
                    }
                    $inventoryStub->delete();
                }
            }

            $survey = $customer->surveys()->latest()->lockForUpdate()->first();

            $oldAssignments = [];
            if ($ont && $ont->odp_id && $ont->port_number) {
                $oldAssignments[] = [(int) $ont->odp_id, (int) $ont->port_number];
            }
            if ($survey && $survey->odp_id && $survey->port_number) {
                $oldAssignments[] = [(int) $survey->odp_id, (int) $survey->port_number];
            }

            $targetPort = OdpPort::firstOrCreate(
                ['odp_id' => $odp->id, 'port_number' => $targetPortNumber],
                ['status' => 'available']
            );

            if (in_array($targetPort->status, ['fault', 'stop'], true)) {
                throw ValidationException::withMessages([
                    'port_number' => "Port {$targetPortNumber} pada ODP {$odp->name} dalam status {$targetPort->status} (tidak dapat digunakan).",
                ]);
            }

            $otherOnt = Ont::where('odp_id', $odp->id)
                ->where('port_number', $targetPortNumber)
                ->whereNotIn('status', self::INVENTORY_STATUSES)
                ->where('customer_id', '!=', $customer->id)
                ->when($ont, fn ($q) => $q->where('id', '!=', $ont->id))
                ->with('customer:id,name,customer_code')
                ->first();
            if ($otherOnt) {
                $holderName = $otherOnt->customer?->name ?? 'Pelanggan Lain';
                $holderCode = $otherOnt->customer?->customer_code ? " ({$otherOnt->customer->customer_code})" : '';
                throw ValidationException::withMessages([
                    'port_number' => "Port {$targetPortNumber} pada ODP {$odp->name} sudah digunakan oleh {$holderName}{$holderCode}.",
                ]);
            }

            $otherSurvey = $this->reservations((int) $odp->id, (int) $targetPortNumber)
                ->where('customer_id', '!=', $customer->id)
                ->with('customer:id,name,customer_code')
                ->first();
            if ($otherSurvey) {
                $holderName = $otherSurvey->customer?->name ?? 'Pelanggan Lain';
                $holderCode = $otherSurvey->customer?->customer_code ? " ({$otherSurvey->customer->customer_code})" : '';
                throw ValidationException::withMessages([
                    'port_number' => "Port {$targetPortNumber} pada ODP {$odp->name} sudah direservasi untuk {$holderName}{$holderCode}.",
                ]);
            }

            // Update or create ONT
            $ontData = array_merge($attributes, [
                'customer_id' => $customer->id,
                'odp_id' => $odp->id,
                'port_number' => $targetPortNumber,
                'odp_port_id' => $targetPort->id,
                'status' => 'active',
            ]);
            if ($odp->area_id && Schema::hasColumn('onts', 'area_id')) {
                $ontData['area_id'] = $odp->area_id;
            }

            if ($ont) {
                $ont->fill($ontData)->save();
            } else {
                if (empty($ontData['serial_number'])) {
                    $ontData['serial_number'] = 'SN-' . strtoupper(\Illuminate\Support\Str::random(8));
                }
                $ont = Ont::create($ontData);
            }

            // Sync Survey dua arah
            if ($survey) {
                $survey->update([
                    'odp_id' => $odp->id,
                    'port_number' => $targetPortNumber,
                    'feasibility' => 'feasible',
                ]);
            } else {
                $survey = Survey::create([
                    'customer_id' => $customer->id,
                    'odp_id' => $odp->id,
                    'port_number' => $targetPortNumber,
                    'feasibility' => 'feasible',
                    'survey_date' => now()->toDateString(),
                    'notes' => 'Otomatis tersinkron dari laporan instalasi ODP ' . $odp->name . ' Port ' . $targetPortNumber,
                ]);
            }

            // Rekonsiliasi port odp_ports
            $assignments = collect($oldAssignments)
                ->push([(int) $odp->id, (int) $targetPortNumber])
                ->filter(fn ($pair) => $pair[0] && $pair[1])
                ->unique(fn ($pair) => $pair[0].':'.$pair[1]);

            foreach ($assignments as [$odpId, $portNumber]) {
                $port = OdpPort::where('odp_id', $odpId)->where('port_number', $portNumber)->first();
                if (!$port || in_array($port->status, ['fault', 'stop'], true)) {
                    continue;
                }
                $used = Ont::where('odp_id', $odpId)->where('port_number', $portNumber)
                    ->whereNotIn('status', self::INVENTORY_STATUSES)->exists();
                $reserved = $this->reservations((int) $odpId, (int) $portNumber)->exists();
                $state = $used ? 'used' : ($reserved ? 'reserved' : 'available');
                $port->update($state === 'available' ? ['status' => $state, 'description' => null] : ['status' => $state]);
            }

            // Rekonsiliasi ODP used_ports & status
            $odpIds = collect($oldAssignments)->pluck(0)->push($odp->id)->filter()->unique();
            $odpsToReconcile = Odp::whereIn('id', $odpIds)->get();
            foreach ($odpsToReconcile as $odpItem) {
                $used = Ont::where('odp_id', $odpItem->id)->whereNotIn('status', self::INVENTORY_STATUSES)->count();
                $status = ($used >= $odpItem->total_ports && $odpItem->total_ports > 0)
                    ? 'full'
                    : ($odpItem->status === 'full' ? 'active' : $odpItem->status);
                Odp::whereKey($odpItem->id)->update(['used_ports' => $used, 'status' => $status]);
            }

            return $ont->fresh();
        });
    }

    private function reservations(int $odpId, int $portNumber)
    {
        return Survey::where('odp_id', $odpId)->where('port_number', $portNumber)
            ->whereNull('rolled_back_at')->where('feasibility', 'feasible')
            ->whereHas('customer', fn ($q) => $q->whereIn('status', ['booking', 'survey', 'installing']));
    }
}
