<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\Ont;

class NetworkRadarController extends Controller
{
    public function index()
    {
        $odcs = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status')
            ->withCount('odps')
            ->get();

        $odps = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'total_ports', 'used_ports', 'status')
            ->with(['odc:id,name'])
            ->get();

        $customers = Customer::select('id', 'name', 'customer_code', 'latitude', 'longitude', 'address', 'is_reseller', 'status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['ont.odp:id,name'])
            ->get()
            ->map(function ($customer) {
                $arr = $customer->toArray();
                $arr['odp'] = $customer->ont ? $customer->ont->odp : null;
                unset($arr['ont']);
                return $arr;
            });

        return Inertia::render('NetworkRadar/Index', [
            'odcs' => $odcs,
            'odps' => $odps,
            'customers' => $customers,
            'initLat' => request()->query('lat'),
            'initLng' => request()->query('lng'),
        ]);
    }

    /**
     * API: Cari perangkat terdekat dari koordinat GPS
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:0.1|max:50', // km
            'type' => 'nullable|in:odp,odc,customer,all',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5; // default 5km
        $type = $request->type ?? 'all';

        $results = [];

        // Haversine formula in raw SQL (km)
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        if ($type === 'all' || $type === 'odp') {
            $odps = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'total_ports', 'used_ports', 'status')
                ->selectRaw("$haversine AS distance", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->limit(20)
                ->with(['odc:id,name'])
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['_type' => 'odp']));
            $results = array_merge($results, $odps->toArray());
        }

        if ($type === 'all' || $type === 'odc') {
            $odcs = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status')
                ->selectRaw("$haversine AS distance", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->limit(20)
                ->withCount('odps')
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['_type' => 'odc']));
            $results = array_merge($results, $odcs->toArray());
        }

        if ($type === 'all' || $type === 'customer') {
            $customers = Customer::select('id', 'name', 'customer_code', 'latitude', 'longitude', 'address', 'is_reseller', 'status')
                ->selectRaw("$haversine AS distance", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->limit(20)
                ->get()
                ->map(function($item) {
                    $type = 'customer';
                    if ($item->is_reseller) $type = 'reseller';
                    else if ($item->status === 'booking') $type = 'booking';
                    return array_merge($item->toArray(), ['_type' => $type]);
                });
            $results = array_merge($results, $customers->toArray());
        }

        // Sort semua hasil gabungan by distance
        usort($results, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return response()->json([
            'results' => array_slice($results, 0, 30),
            'center' => ['lat' => $lat, 'lng' => $lng],
            'radius' => $radius,
        ]);
    }
}
