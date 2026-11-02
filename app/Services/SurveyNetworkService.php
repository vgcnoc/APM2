<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Ont;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;
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
                if (!$odp || (int) $odp->area_id !== (int) $customer->area_id) {
                    throw ValidationException::withMessages(['odp_id' => 'ODP tidak sesuai dengan Area/Wilayah pelanggan.']);
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
            if ($feasible && $onts->isNotEmpty()) {
                $ont = $onts->first();
                $ont->fill([
                    'odp_id' => $targetOdpId,
                    'port_number' => $targetPortNumber,
                    'odp_port_id' => $port->id,
                ]);
                // Port flags and counters are reconciled below, without RADIUS side effects.
                $ont->saveQuietly();
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

    private function reservations(int $odpId, int $portNumber)
    {
        return Survey::where('odp_id', $odpId)->where('port_number', $portNumber)
            ->whereNull('rolled_back_at')->where('feasibility', 'feasible')
            ->whereHas('customer', fn ($q) => $q->whereIn('status', ['booking', 'survey', 'installing']));
    }
}
