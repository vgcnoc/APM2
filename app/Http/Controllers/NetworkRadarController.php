<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\Ont;
use App\Services\DataScopeService;

class NetworkRadarController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin() && !$user->can('menu_network_radar') && !$user->can('menu_network_map')) {
            abort(403, 'Anda tidak memiliki hak akses ke menu Radar Tikor Jaringan.');
        }

        $odcQuery = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status', 'area_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['area:id,name'])
            ->withCount('odps');
        $odcs = $odcQuery->get();

        $odpQuery = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'area_id', 'total_ports', 'used_ports', 'status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['odc:id,name', 'area:id,name']);
        $odps = $odpQuery->get();

        $customerQuery = Customer::select('id', 'name', 'customer_code', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id', 'area')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['ont.odp:id,name,odc_id', 'areaModel:id,name']);

        $customers = $customerQuery->get()
            ->map(function ($customer) {
                $arr = $customer->toArray();
                $arr['odp'] = $customer->ont ? $customer->ont->odp : null;
                $arr['odp_id'] = $customer->ont && $customer->ont->odp ? $customer->ont->odp->id : null;
                $arr['odc_id'] = $customer->ont && $customer->ont->odp ? $customer->ont->odp->odc_id : null;
                $arr['area_name'] = $customer->areaModel ? $customer->areaModel->name : ($customer->area ?: null);
                unset($arr['ont']);
                return $arr;
            });

        $areas = \App\Models\Area::select('id', 'name')->orderBy('name')->get();

        // Aktivitas Karyawan (Absensi GPS & Tugas Luar Hari Ini / Terkini)
        $employeeActivities = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('attendances')) {
                $attendances = \App\Models\Attendance::with(['employee:id,name,position,branch'])
                    ->whereDate('date', now()->toDateString())
                    ->where(function ($q) {
                        $q->whereNotNull('lat_in')->whereNotNull('lng_in')
                          ->orWhereNotNull('lat_out')->whereNotNull('lng_out');
                    })
                    ->latest()
                    ->get()
                    ->map(function ($att) {
                        return [
                            'id' => 'att-' . $att->id,
                            'name' => $att->employee?->name ?? 'Karyawan',
                            'position' => $att->employee?->position ?? '-',
                            'branch' => $att->employee?->branch ?? '-',
                            'time' => $att->clock_in ?? $att->clock_out,
                            'latitude' => (float) ($att->lat_in ?? $att->lat_out),
                            'longitude' => (float) ($att->lng_in ?? $att->lng_out),
                            'status' => $att->status,
                            'photo' => $att->photo_in ?? $att->photo_out,
                            'type' => 'attendance',
                            'notes' => $att->notes ?? 'Absensi Reguler'
                        ];
                    });
                $employeeActivities = $employeeActivities->concat($attendances);
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('field_duties')) {
                $fieldDuties = \App\Models\FieldDuty::with(['employee:id,name,position,branch'])
                    ->whereDate('date', now()->toDateString())
                    ->where(function ($q) {
                        $q->whereNotNull('lat_in')->whereNotNull('lng_in')
                          ->orWhereNotNull('lat_out')->whereNotNull('lng_out');
                    })
                    ->latest()
                    ->get()
                    ->map(function ($duty) {
                        return [
                            'id' => 'duty-' . $duty->id,
                            'name' => $duty->employee?->name ?? 'Karyawan',
                            'position' => $duty->employee?->position ?? '-',
                            'branch' => $duty->employee?->branch ?? '-',
                            'time' => $duty->check_in_time ?? $duty->check_out_time ?? $duty->scheduled_start_time,
                            'latitude' => (float) ($duty->lat_in ?? $duty->lat_out),
                            'longitude' => (float) ($duty->lng_in ?? $duty->lng_out),
                            'status' => $duty->status,
                            'photo' => $duty->check_in_photo ?? $duty->check_out_photo,
                            'type' => 'field_duty',
                            'notes' => $duty->purpose ?? $duty->destination_location ?? 'Tugas Lapangan'
                        ];
                    });
                $employeeActivities = $employeeActivities->concat($fieldDuties);
            }
        } catch (\Throwable $e) {
            // fallback gracefully
        }

        return Inertia::render('NetworkRadar/Index', [
            'odcs' => $odcs,
            'odps' => $odps,
            'customers' => $customers,
            'areas' => $areas,
            'employeeActivities' => $employeeActivities->values(),
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
            'radius' => 'nullable|numeric|min:1|max:500000', // meter
            'type' => 'nullable|in:odp,odc,customer,all',
            'area_id' => 'nullable|numeric',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $rawRadius = (float) ($request->radius ?? 5000);
        // Radius dikirim dalam satuan meter dari frontend
        $radiusKm = max(0.01, $rawRadius / 1000);
        $type = $request->type ?? 'all';
        $areaId = $request->area_id ? (int) $request->area_id : null;

        $results = [];
        $odpsList = [];
        $odcsList = [];
        $customersList = [];

        // Haversine formula in raw SQL (km) dengan pengaman LEAST/GREATEST agar tidak menghasilkan acos NaN/NULL
        $haversine = "(6371 * acos(LEAST(1.0, GREATEST(-1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))";

        if ($type === 'all' || $type === 'odp') {
            $odpQuery = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'area_id', 'total_ports', 'used_ports', 'status')
                ->selectRaw("$haversine AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->limit(100)
                ->with(['odc:id,name', 'area:id,name']);

            if ($areaId) {
                $odpQuery->where('area_id', $areaId);
            }

            $odps = $odpQuery->get()->map(function ($item) {
                $arr = $item->toArray();
                $arr['_type'] = 'odp';
                $arr['distance_meters'] = round($item->distance_km * 1000);
                $total = (int) ($item->total_ports ?? 8);
                $used = (int) ($item->used_ports ?? 0);
                $arr['total_ports'] = $total;
                $arr['used_ports'] = $used;
                $arr['available_ports'] = max(0, $total - $used);
                $arr['odc_name'] = $item->odc?->name ?? '-';
                $arr['area_name'] = $item->area?->name ?? '-';
                return $arr;
            });
            $odpsList = $odps->values()->toArray();
            $results = array_merge($results, $odpsList);
        }

        if ($type === 'all' || $type === 'odc') {
            $odcQuery = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status', 'area_id')
                ->selectRaw("$haversine AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->limit(60)
                ->with(['area:id,name'])
                ->withCount('odps');

            if ($areaId) {
                $odcQuery->where('area_id', $areaId);
            }

            $odcs = $odcQuery->get()->map(function ($item) {
                $arr = $item->toArray();
                $arr['_type'] = 'odc';
                $arr['distance_meters'] = round($item->distance_km * 1000);
                $arr['area_name'] = $item->area?->name ?? '-';
                return $arr;
            });
            $odcsList = $odcs->values()->toArray();
            $results = array_merge($results, $odcsList);
        }

        if ($type === 'all' || $type === 'customer') {
            $customerQuery = Customer::select('id', 'name', 'customer_code', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id', 'area')
                ->selectRaw("$haversine AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->limit(100)
                ->with(['areaModel:id,name', 'ont.odp:id,name,odc_id']);

            if ($areaId) {
                $customerQuery->where('area_id', $areaId);
            }

            $customers = $customerQuery->get()->map(function ($item) {
                $subType = 'customer';
                if ($item->is_reseller) $subType = 'reseller';
                elseif ($item->status === 'booking') $subType = 'booking';
                $arr = $item->toArray();
                $arr['_type'] = $subType;
                $arr['distance_meters'] = round($item->distance_km * 1000);
                $odp = $item->ont ? $item->ont->odp : null;
                $arr['odp'] = $odp ? ['id' => $odp->id, 'name' => $odp->name, 'odc_id' => $odp->odc_id] : null;
                $arr['odp_id'] = $odp ? $odp->id : null;
                $arr['odc_id'] = $odp ? $odp->odc_id : null;
                $arr['odp_name'] = $odp ? $odp->name : '-';
                $arr['area_name'] = $item->areaModel ? $item->areaModel->name : ($item->area ?: '-');
                unset($arr['ont']);
                return $arr;
            });
            $customersList = $customers->values()->toArray();
            $results = array_merge($results, $customersList);
        }

        // Sort all combined results by distance
        usort($results, fn($a, $b) => ($a['distance_km'] ?? 0) <=> ($b['distance_km'] ?? 0));

        $closestOdp = !empty($odpsList) ? $odpsList[0] : null;
        $closestOdc = !empty($odcsList) ? $odcsList[0] : null;
        $closestCustomer = !empty($customersList) ? $customersList[0] : null;

        return response()->json([
            'results' => array_slice($results, 0, 100),
            'odps' => $odpsList,
            'odcs' => $odcsList,
            'customers' => $customersList,
            'closest_odp' => $closestOdp,
            'closest_odc' => $closestOdc,
            'closest_customer' => $closestCustomer,
            'center' => ['lat' => $lat, 'lng' => $lng],
            'radius_meters' => round($radiusKm * 1000),
            'radius_km' => $radiusKm,
            'stats' => [
                'odc' => count($odcsList),
                'odp' => count($odpsList),
                'pelanggan' => count($customersList),
                'total' => count($results),
            ],
        ]);
    }
}
