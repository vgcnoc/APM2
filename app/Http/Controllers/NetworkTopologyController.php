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
            'pons.odcs.odps.ports.ont.customer',
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

        return Inertia::render('Infrastructure/Topology/Index', [
            'olts' => $olts,
            'areas' => $areas,
            'filters' => $request->only('area_id'),
        ]);
    }
}
