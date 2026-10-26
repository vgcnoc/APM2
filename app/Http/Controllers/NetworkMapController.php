<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\Area;
use App\Models\User;
use App\Services\CustomerConnectionService;
use App\Services\DataScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NetworkMapController extends Controller
{
    private function authorizeMap(User $user): void
    {
        abort_unless(in_array($user->role, ['admin', 'super admin', 'superadmin', 'super-admin'], true)
            || $user->hasAnyRole(['admin', 'super admin']) || $user->can('menu_network_map'), 403);
    }

    private function mapCustomers(User $user): Builder
    {
        return DataScopeService::applyCustomerScope(Customer::query(), $user)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', 0)->where('longitude', '!=', 0);
    }

    public function connections(Request $request, CustomerConnectionService $connections)
    {
        $this->authorizeMap($request->user());
        $customers = $this->mapCustomers($request->user())->select('id', 'area_id')
            ->with('ont:id,customer_id,pppoe_user,hotspot_user')->get();

        return response()->json($connections->snapshot($customers))->header('Cache-Control', 'private, no-store');
    }

    /**
     * Tampilan Utama Smart GIS Network & Customer Map
     */
    public function index(Request $request)
    {
        $user = $request->user() ?: auth()->user();
        $this->authorizeMap($user);

        // 1. Data ODC
        $odcQuery = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status', 'area_id')
            ->with(['area:id,name'])
            ->withCount('odps');

        // 2. Data ODP
        $odpQuery = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'area_id', 'total_ports', 'used_ports', 'status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['odc:id,name', 'area:id,name'])
            ->withCount('onts as customer_count');

        // 3. Data Pelanggan
        $customerQuery = $this->mapCustomers($user)->select('id', 'name', 'customer_code', 'phone', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id', 'package_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['package:id,name,speed_mbps', 'areaModel:id,name', 'ont.odp:id,name,odc_id']);

        // 4. Data Area / Wilayah
        $areaQuery = Area::select('id', 'name', 'description', 'address', 'latitude', 'longitude', 'radius_meters')
            ->orderBy('name');

        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin') {
            // Wilayah relevan untuk user (wilayah kerja tugas + wilayah pelanggan yang ditugaskan)
            $accessibleAreas = (array)$user->getAccessibleAreaIds();
            $customerAreaIds = (clone $customerQuery)->pluck('area_id')->filter()->unique()->toArray();
            $relevantAreaIds = array_values(array_unique(array_merge($accessibleAreas, $customerAreaIds)));

            if (!empty($relevantAreaIds)) {
                $odcQuery->whereIn('area_id', $relevantAreaIds);
                $odpQuery->whereIn('area_id', $relevantAreaIds);
                $areaQuery->whereIn('id', $relevantAreaIds);
            }
        }

        $odps = $odpQuery->get()
            ->filter(fn($item) => (float)$item->latitude != 0 && (float)$item->longitude != 0)
            ->values();

        $odcs = $odcQuery->get()
            ->map(function ($odc) use ($odps) {
                $lat = (float)($odc->latitude ?: 0);
                $lng = (float)($odc->longitude ?: 0);

                // Auto-fallback: Jika ODC belum punya koordinat, hitung centroid rata-rata dari ODP terkait
                if ($lat == 0 || $lng == 0) {
                    $connectedOdps = $odps->where('odc_id', $odc->id);
                    if ($connectedOdps->count() > 0) {
                        $lat = (float)$connectedOdps->avg('latitude');
                        $lng = (float)$connectedOdps->avg('longitude');
                    }
                }

                if ($lat == 0 || $lng == 0) {
                    return null;
                }

                return (object)[
                    'id' => $odc->id,
                    'name' => $odc->name,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => $odc->address ?: ($odc->location ?: 'Pusat Distribusi Jaringan'),
                    'capacity' => $odc->capacity ?: 48,
                    'status' => $odc->status ?: 'active',
                    'area_id' => $odc->area_id,
                    'area' => $odc->area,
                    'odps_count' => $odc->odps_count ?: 0,
                ];
            })
            ->filter()
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
        $this->authorizeMap($request->user());
        $request->validate([
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'dest_lat' => 'required|numeric|between:-90,90',
            'dest_lng' => 'required|numeric|between:-180,180',
            'purpose' => 'nullable|in:navigation,fiber',
        ]);

        $oLat = (float)$request->origin_lat;
        $oLng = (float)$request->origin_lng;
        $dLat = (float)$request->dest_lat;
        $dLng = (float)$request->dest_lng;

        $fiber = $request->input('purpose') === 'fiber';
        $endpoint = rtrim(config('services.osrm.url', 'https://router.project-osrm.org'), '/');
        $cacheKey = 'gis-fiber-route:v1:' . hash('sha256', $endpoint . ':' . implode(',', array_map(
            fn ($value) => sprintf('%.6F', $value), [$oLat, $oLng, $dLat, $dLng]
        )));
        if ($fiber && ($cached = Cache::get($cacheKey))) {
            return response()->json(array_merge($cached, ['cached' => true]));
        }
        // Uncached cable routes share one request/second across all GIS users.
        if ($fiber && !Cache::add('gis-fiber-routing:slot', true, 1)) {
            return response()->json(['message' => 'Perhitungan rute sedang antre.'], 429)
                ->header('Retry-After', 1);
        }
        $steps = $fiber ? 'false' : 'true';
        $url = "{$endpoint}/route/v1/driving/{$oLng},{$oLat};{$dLng},{$dLat}?overview=full&geometries=geojson&steps={$steps}";

        try {
            $response = Http::withHeaders(['User-Agent' => 'VIZBILL-GIS/1.0', 'Referer' => config('app.url')])->timeout(6)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                $coordinates = $data['routes'][0]['geometry']['coordinates'] ?? [];
                $validGeometry = ($data['routes'][0]['geometry']['type'] ?? null) === 'LineString'
                    && is_array($coordinates) && count($coordinates) >= 2;
                if ($validGeometry) foreach ($coordinates as $point) {
                    if (!is_array($point) || count($point) < 2 || !is_numeric($point[0]) || !is_numeric($point[1])
                        || abs((float)$point[0]) > 180 || abs((float)$point[1]) > 90) {
                        $validGeometry = false;
                        break;
                    }
                }
                if ($validGeometry && isset($data['routes'][0]['distance'], $data['routes'][0]['duration'])) {
                    $route = $data['routes'][0];
                    $result = [
                        'success' => true,
                        'status' => 'success',
                        'source' => 'osrm',
                        'distance' => $route['distance'], // meters
                        'duration' => $route['duration'], // seconds
                        'geometry' => $route['geometry'],
                        'legs' => $route['legs'] ?? [],
                    ];
                    if ($fiber) Cache::put($cacheKey, $result, now()->addDays(7));
                    return response()->json($result);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OSRM route error: ' . $e->getMessage());
        }

        // Fallback: Haversine straight line jika internet/OSRM down
        $distance = $this->haversineGreatCircleDistance($oLat, $oLng, $dLat, $dLng);
        $estDuration = ($distance / 40000) * 3600; // estimasi 40 km/h

        return response()->json([
            'success' => true,
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
            'status' => 'success',
            'target' => ['lat' => $lat, 'lng' => $lng],
            'odps' => $odps,
            'nearby_odps' => $odps,
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
