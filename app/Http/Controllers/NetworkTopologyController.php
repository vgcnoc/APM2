<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Olt;
use App\Services\DataScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NetworkTopologyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $areaId = $request->input('area_id');

        $query = Olt::with([
            'area',
            'areas',
            'pons' => fn ($pq) => $pq->orderBy('port_number'),
            'pons.odcs' => fn ($oq) => $oq->orderByRaw('COALESCE(parent_odc_id, id) ASC, CASE WHEN parent_odc_id IS NULL THEN 0 ELSE 1 END ASC, id ASC'),
            'pons.odcs.parent:id,name,parent_odc_id',
            'pons.odcs.children:id,name,parent_odc_id',
            'pons.odcs.area',
            'pons.odcs.odps' => fn ($odpq) => $odpq->orderByRaw('COALESCE(parent_odp_id, id) ASC, CASE WHEN parent_odp_id IS NULL THEN 0 ELSE 1 END ASC, id ASC'),
            'pons.odcs.odps.parent:id,name,parent_odp_id',
            'pons.odcs.odps.children:id,name,parent_odp_id',
            'pons.odcs.odps.area',
            'pons.odcs.odps.ports' => fn ($portq) => $portq->orderBy('port_number'),
            'pons.odcs.odps.ports.ont:id,serial_number,brand,model,rx_power,tx_power,status,wifi_ssid,ip_login,customer_id,odp_port_id',
            'pons.odcs.odps.ports.ont.customer' => fn ($cq) => $cq->select('id', 'name', 'customer_code', 'phone', 'address', 'status', 'package_id'),
            'pons.odcs.odps.ports.ont.customer.package:id,name',
        ]);

        DataScopeService::applyOltScope($query, $user);

        if ($areaId) {
            $query->where(function ($areaQuery) use ($areaId) {
                $areaQuery->where('area_id', $areaId)
                    ->orWhereHas('areas', fn ($areasQuery) => $areasQuery->where('areas.id', $areaId));
            });
        }

        $olts = $query->get();
        $areas = DataScopeService::getScopedAreas($user, 'topology');

        $totalOlts = $olts->count();
        $activeOlts = $olts->where('status', 'active')->count();
        $totalPons = 0;
        $totalOdcs = 0;
        $splitIndukOdcs = 0;
        $splitLanjutanOdcs = 0;
        $totalOdps = 0;
        $splitIndukOdps = 0;
        $splitLanjutanOdps = 0;
        $totalPorts = 0;
        $usedPorts = 0;
        $connectedCustomers = 0;

        foreach ($olts as $olt) {
            if ($olt->pons) {
                $totalPons += $olt->pons->count();
                foreach ($olt->pons as $pon) {
                    if ($pon->odcs) {
                        $totalOdcs += $pon->odcs->count();
                        foreach ($pon->odcs as $odc) {
                            if (!empty($odc->parent_odc_id)) {
                                $splitLanjutanOdcs++;
                            } elseif ($odc->children && $odc->children->isNotEmpty()) {
                                $splitIndukOdcs++;
                            }

                            if ($odc->odps) {
                                $totalOdps += $odc->odps->count();
                                foreach ($odc->odps as $odp) {
                                    if (!empty($odp->parent_odp_id)) {
                                        $splitLanjutanOdps++;
                                    } elseif ($odp->children && $odp->children->isNotEmpty()) {
                                        $splitIndukOdps++;
                                    }

                                    $totalPorts += (int) ($odp->total_ports ?? 0);
                                    $usedPorts += (int) ($odp->used_ports ?? 0);

                                    if ($odp->ports) {
                                        foreach ($odp->ports as $port) {
                                            if ($port->ont && $port->ont->customer) {
                                                $connectedCustomers++;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $availablePorts = max(0, $totalPorts - $usedPorts);
        $utilizationRate = $totalPorts > 0 ? round(($usedPorts / $totalPorts) * 100, 1) : 0;

        $stats = [
            'total_olts' => $totalOlts,
            'active_olts' => $activeOlts,
            'total_pons' => $totalPons,
            'total_odcs' => $totalOdcs,
            'split_induk_odcs' => $splitIndukOdcs,
            'split_lanjutan_odcs' => $splitLanjutanOdcs,
            'total_odps' => $totalOdps,
            'split_induk_odps' => $splitIndukOdps,
            'split_lanjutan_odps' => $splitLanjutanOdps,
            'total_ports' => $totalPorts,
            'used_ports' => $usedPorts,
            'available_ports' => $availablePorts,
            'utilization_rate' => $utilizationRate,
            'connected_customers' => $connectedCustomers,
        ];

        return Inertia::render('Infrastructure/Topology/Index', [
            'olts' => $olts,
            'areas' => $areas,
            'filters' => $request->only('area_id'),
            'stats' => $stats,
        ]);
    }
}
