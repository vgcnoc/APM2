<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Olt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NetworkTopologyController extends Controller
{
    public function index(Request $request)
    {
        $areaId = $request->input('area_id');

        $query = Olt::with([
            'area',
            'pons.odcs.odps.ports.ont.customer',
            'pons.odcs.odps.area',
            'pons.odcs.area'
        ]);

        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        $olts = $query->get();
        $areas = Area::all();

        return Inertia::render('Infrastructure/Topology/Index', [
            'olts' => $olts,
            'areas' => $areas,
            'filters' => $request->only('area_id'),
        ]);
    }
}
