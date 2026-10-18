<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\Area;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetworkMapController extends Controller
{
    /**
     * Tampilan Utama Smart GIS Network & Customer Map
     */
    public function index(Request $request)
    {
        $user = $request->user() ?: auth()->user();

        // 1. Data ODC
        $odcQuery = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status', 'area_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['area:id,name'])
            ->withCount('odps');

        // 2. Data ODP
        $odpQuery = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'area_id', 'total_ports', 'used_ports', 'status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['odc:id,name', 'area:id,name'])
            ->withCount('onts as customer_count');

        // 3. Data Pelanggan
        $customerQuery = Customer::select('id', 'name', 'customer_code', 'phone', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id', 'package_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['package:id,name,speed_mbps', 'areaModel:id,name', 'ont.odp:id,name,odc_id']);

        // 4. Data Area / Wilayah
        $areaQuery = Area::select('id', 'name', 'description', 'address', 'latitude', 'longitude', 'radius_meters')
            ->orderBy('name');

        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin') {
            \App\Services\DataScopeService::applyCustomerScope($customerQuery, $user);

            $scopeLevel = \App\Services\DataScopeService::getScopeLevel($user, 'network_map');
            if ($scopeLevel === 'area') {
                $accessibleAreas = $user->getAccessibleAreaIds();
                $odcQuery->whereIn('area_id', $accessibleAreas);
                $odpQuery->whereIn('area_id', $accessibleAreas);
                $areaQuery->whereIn('id', $accessibleAreas);
            }
        }

        $odcs = $odcQuery->get()
            ->filter(fn($item) => (float)$item->latitude != 0 && (float)$item->longitude != 0)
            ->values();

        $odps = $odpQuery->get()
            ->filter(fn($item) => (float)$item->latitude != 0 && (float)$item->longitude != 0)
            ->values();

        $customers = $customerQuery->get()
            ->filter(fn($item) => (float)$item->latitude != 0 && (float)$item->longitude != 0)
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_code' => $customer->customer_code,
                    'phone' => $customer->phone,
                    'latitude' => (float)$customer->latitude,
                    'longitude' => (float)$customer->longitude,
                    'address' => $customer->address,
                    'status' => $customer->status,
                    'service_status' => $customer->service_status,
                    'is_reseller' => (bool)$customer->is_reseller,
                    'area_id' => $customer->area_id,
                    'area_name' => $customer->areaModel?->name ?? ($customer->area ?: 'Umum'),
                    'package_name' => $customer->package?->name,
                    'package_speed' => $customer->package?->speed_mbps ? $customer->package->speed_mbps . ' Mbps' : null,
                    'odp_id' => $customer->ont?->odp?->id,
                    'odp_name' => $customer->ont?->odp?->name,
                    'odc_id' => $customer->ont?->odp?->odc_id,
                ];
            })
            ->values();

        $rawAreas = $areaQuery->get();

        $areas = $rawAreas->map(function ($area) use ($odps, $customers) {
            $lat = (float)($area->latitude ?: 0);
            $lng = (float)($area->longitude ?: 0);

            // Jika area belum punya koordinat, hitung rata-rata titik ODP atau pelanggan di area tersebut
            if ($lat == 0 || $lng == 0) {
                $areaOdps = $odps->where('area_id', $area->id);
                if ($areaOdps->count() > 0) {
                    $lat = (float)$areaOdps->avg('latitude');
                    $lng = (float)$areaOdps->avg('longitude');
                } else {
                    $areaCust = $customers->where('area_id', $area->id);
                    if ($areaCust->count() > 0) {
                        $lat = (float)$areaCust->avg('latitude');
                        $lng = (float)$areaCust->avg('longitude');
                    }
                }
            }

            return [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'address' => $area->address,
                'latitude' => $lat,
                'longitude' => $lng,
                'radius_meters' => $area->radius_meters ?: 2000,
                'total_odp' => $odps->where('area_id', $area->id)->count(),
                'total_customers' => $customers->where('area_id', $area->id)->count(),
            ];
        })->values();

        // 5. Initial Focus jika ada query parameter
        $initialFocus = [
            'type' => $request->query('focus'), // 'customer' | 'odp' | 'odc' | 'area'
            'id' => $request->query('id'),
            'lat' => $request->query('lat'),
            'lng' => $request->query('lng'),
            'zoom' => $request->query('zoom', 16),
        ];

        return Inertia::render('NetworkMap/Index', [
            'odcs' => $odcs,
            'odps' => $odps,
            'customers' => $customers,
            'areas' => $areas,
            'initialFocus' => $initialFocus,
        ]);
    }

    /**
     * Hitung Rute Jalan Turn-by-Turn via OSRM (dengan fallback straight line)
     */
    public function calculateRoute(Request $request)
    {
        $request->validate([
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'dest_lat' => 'required|numeric',
            'dest_lng' => 'required|numeric',
        ]);

        $oLat = (float)$request->origin_lat;
        $oLng = (float)$request->origin_lng;
        $dLat = (float)$request->dest_lat;
        $dLng = (float)$request->dest_lng;

        // Coba OSRM Public Routing Server
        $url = "https://router.project-osrm.org/route/v1/driving/{$oLng},{$oLat};{$dLng},{$dLat}?overview=full&geometries=geojson&steps=true";

        try {
            $response = Http::timeout(6)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['routes'][0])) {
                    $route = $data['routes'][0];
                    return response()->json([
                        'status' => 'success',
                        'source' => 'osrm',
                        'distance' => $route['distance'], // meters
                        'duration' => $route['duration'], // seconds
                        'geometry' => $route['geometry'],
                        'legs' => $route['legs'] ?? [],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OSRM route error: ' . $e->getMessage());
        }

        // Fallback: Haversine straight line jika internet/OSRM down
        $distance = $this->haversineGreatCircleDistance($oLat, $oLng, $dLat, $dLng);
        $estDuration = ($distance / 40000) * 3600; // estimasi 40 km/h

        return response()->json([
            'status' => 'fallback',
            'source' => 'straight_line',
            'distance' => $distance,
            'duration' => $estDuration,
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [$oLng, $oLat],
                    [$dLng, $dLat]
                ]
            ],
            'legs' => [],
        ]);
    }

    /**
     * API: Pencarian Pintar ODP Terdekat (Smart ODP Finder)
     */
    public function findNearbyOdps(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $lat = (float)$request->lat;
        $lng = (float)$request->lng;
        $limit = $request->input('limit', 5);

        $odps = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'total_ports', 'used_ports', 'status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['odc:id,name'])
            ->get()
            ->filter(fn($odp) => (float)$odp->latitude != 0 && (float)$odp->longitude != 0)
            ->map(function ($odp) use ($lat, $lng) {
                $dist = $this->haversineGreatCircleDistance($lat, $lng, (float)$odp->latitude, (float)$odp->longitude);
                $available = max(0, ($odp->total_ports ?: 8) - ($odp->used_ports ?: 0));
                return [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'odc_name' => $odp->odc?->name ?? '-',
                    'latitude' => (float)$odp->latitude,
                    'longitude' => (float)$odp->longitude,
                    'address' => $odp->address,
                    'total_ports' => $odp->total_ports ?: 8,
                    'used_ports' => $odp->used_ports ?: 0,
                    'available_ports' => $available,
                    'status' => $odp->status ?: 'active',
                    'distance_meters' => round($dist),
                    'is_recommended' => $available > 0,
                ];
            })
            ->sortBy('distance_meters')
            ->values()
            ->take($limit);

        return response()->json([
            'success' => true,
            'target' => ['lat' => $lat, 'lng' => $lng],
            'odps' => $odps,
        ]);
    }

    /**
     * Helper: Jarak Haversine (meter)
     */
    private function haversineGreatCircleDistance($latFrom, $lonFrom, $latTo, $lonTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latFrom);
        $lonFrom = deg2rad($lonFrom);
        $latTo = deg2rad($latTo);
        $lonTo = deg2rad($lonTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
