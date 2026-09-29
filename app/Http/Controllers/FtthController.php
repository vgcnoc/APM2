<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Customer;
use App\Models\FtthCableRoute;
use App\Models\FtthDesign;
use App\Models\FtthDesignDevice;
use App\Models\FtthDesignMaterial;
use App\Models\Material;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FtthController extends Controller
{
    public function index(Request $request)
    {
        $areas = Area::orderBy('name')->get();

        // Summary stats
        $summary = [
            'olts' => Olt::count(),
            'odcs' => Odc::count(),
            'odps' => Odp::count(),
            'customers' => Customer::where('status', 'active')->count(),
            'total_cable_km' => round(FtthCableRoute::sum('distance') / 1000, 1),
            'materials' => Material::count(),
            'areas' => Area::count(),
            'designs' => FtthDesign::count(),
        ];

        // Designs list
        $designs = FtthDesign::with(['area', 'creator'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Ftth/Index', [
            'areas' => $areas,
            'summary' => $summary,
            'designs' => $designs,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * API: Get map data for a specific area (or all)
     */
    public function mapData(Request $request)
    {
        $areaId = $request->area_id;

        $oltsQuery = Olt::with('area')->whereNotNull('latitude')->whereNotNull('longitude');
        $odcsQuery = Odc::with(['area', 'olt'])->whereNotNull('latitude')->whereNotNull('longitude');
        $odpsQuery = Odp::with(['area', 'odc'])->whereNotNull('latitude')->whereNotNull('longitude');
        $customersQuery = Customer::where('status', 'active')
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->select('id', 'customer_code', 'name', 'address', 'latitude', 'longitude', 'area_id', 'status');

        if ($areaId) {
            $oltsQuery->where('area_id', $areaId);
            $odcsQuery->where('area_id', $areaId);
            $odpsQuery->where('area_id', $areaId);
            $customersQuery->where('area_id', $areaId);
        }

        // Cable routes
        $routesQuery = FtthCableRoute::query();
        if ($request->design_id) {
            $routesQuery->where('design_id', $request->design_id);
        }

        return response()->json([
            'olts' => $oltsQuery->get(),
            'odcs' => $odcsQuery->get(),
            'odps' => $odpsQuery->get(),
            'customers' => $customersQuery->get(),
            'routes' => $routesQuery->get(),
        ]);
    }

    /**
     * Show design workspace
     */
    public function show(FtthDesign $design)
    {
        $design->load('area');
        $devices = FtthDesignDevice::where('design_id', $design->id)->get();
        $routes = FtthCableRoute::where('design_id', $design->id)->get();
        $materials = \App\Models\Material::orderBy('name')->get();

        return Inertia::render('Ftth/Show', [
            'design' => $design,
            'devices' => $devices,
            'routes' => $routes,
            'materials' => $materials,
        ]);
    }

    /**
     * Store a new FTTH design
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'description' => 'nullable|string',
            'pic' => 'nullable|string|max:255',
            'slack_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['status'] = 'draft';
        $validated['created_by'] = auth()->id();

        $design = FtthDesign::create($validated);

        return redirect()->route('ftth.index')->with('success', 'Perancangan FTTH "' . $design->name . '" berhasil dibuat.');
    }

    /**
     * Update design
     */
    public function update(Request $request, FtthDesign $design)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'description' => 'nullable|string',
            'pic' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'slack_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $design->update($validated);

        return back()->with('success', 'Perancangan berhasil diperbarui.');
    }

    /**
     * Delete design
     */
    public function destroy(FtthDesign $design)
    {
        $design->delete();
        return redirect()->route('ftth.index')->with('success', 'Perancangan berhasil dihapus.');
    }

    /**
     * Submit Review (Update status & materials)
     */
    public function submitReview(Request $request, FtthDesign $design)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'materials' => 'array',
            'materials.*.id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0',
        ]);

        $design->update(['status' => $validated['status']]);

        // Sync materials
        FtthDesignMaterial::where('design_id', $design->id)->delete();
        
        if (!empty($validated['materials'])) {
            $insertData = collect($validated['materials'])->filter(function ($item) {
                return $item['quantity'] > 0;
            })->map(function ($item) use ($design) {
                return [
                    'design_id' => $design->id,
                    'material_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            if (!empty($insertData)) {
                FtthDesignMaterial::insert($insertData);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Store cable route
     */
    public function storeCableRoute(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:ftth_designs,id',
            'start_type' => 'nullable|string',
            'start_id' => 'nullable|integer',
            'end_type' => 'nullable|string',
            'end_id' => 'nullable|integer',
            'cable_type' => 'nullable|string',
            'core_count' => 'nullable|integer',
            'distance' => 'nullable|numeric',
            'route_points' => 'nullable|array',
            'route_type' => 'nullable|string',
        ]);

        $route = FtthCableRoute::create($validated);

        // Update design total distance
        $design = FtthDesign::find($validated['design_id']);
        $design->update([
            'total_distance' => $design->cableRoutes->sum('distance'),
        ]);

        return response()->json(['success' => true, 'route' => $route]);
    }

    /**
     * API: Store design device placement
     */
    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:ftth_designs,id',
            'device_type' => 'required|string',
            'device_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'meta' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        $device = FtthDesignDevice::create($validated);

        return response()->json(['success' => true, 'device' => $device]);
    }

    /**
     * API: Update device position (drag & drop)
     */
    public function updateDevicePosition(Request $request, FtthDesignDevice $device)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $device->update($validated);

        return response()->json(['success' => true]);
    }
}
