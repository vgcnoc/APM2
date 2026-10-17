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
        $odcs = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status', 'area_id')
            ->with(['area:id,name'])
            ->withCount('odps')
            ->get();

        $odps = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'area_id', 'total_ports', 'used_ports', 'status')
            ->with(['odc:id,name', 'area:id,name'])
            ->get();

        $customers = Customer::select('id', 'name', 'customer_code', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['ont.odp:id,name,odc_id', 'areaModel:id,name'])
            ->get()
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
            'radius' => 'nullable|numeric|min:0.1|max:50000', // meter or km
            'type' => 'nullable|in:odp,odc,customer,all',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;
        $rawRadius = $request->radius ?? 5;
        $radius = $rawRadius > 50 ? ($rawRadius / 1000) : $rawRadius; // convert meter to km if > 50
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
