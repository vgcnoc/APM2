<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\Ont;
use App\Services\DataScopeService;
use Carbon\Carbon;

class NetworkRadarController extends Controller
{
    /**
     * Helper seragam untuk memformat data pelanggan beserta status tagihan & paket
     */
    private function formatCustomerRadarData($item, string $today, $distanceMeters = null): array
    {
        $subType = 'customer';
        if ($item->is_reseller) {
            $subType = 'reseller';
        } elseif ($item->status === 'booking') {
            $subType = 'booking';
        }

        $odp = $item->ont ? $item->ont->odp : null;
        $areaName = $item->areaModel ? $item->areaModel->name : ($item->area ?: '-');

        // Kalkulasi tagihan belum lunas
        $unpaidInvoices = $item->invoices ?? collect();
        $totalUnpaid = 0;
        $unpaidCount = 0;
        $earliestDueDate = null;
        $latestPromiseDate = null;
        $isOverdue = false;

        foreach ($unpaidInvoices as $inv) {
            $paid = $inv->payments ? (float) $inv->payments->where('status', 'verified')->sum('amount') : 0;
            $remaining = max(0, (float) $inv->amount - $paid);
            if ($remaining > 0) {
                $totalUnpaid += $remaining;
                $unpaidCount++;

                if ($inv->due_date) {
                    $dueDateStr = is_string($inv->due_date) ? substr($inv->due_date, 0, 10) : $inv->due_date->format('Y-m-d');
                    if (!$earliestDueDate || $dueDateStr < $earliestDueDate) {
                        $earliestDueDate = $dueDateStr;
                    }
                    if ($dueDateStr < $today) {
                        $isOverdue = true;
                    }
                }

                if ($inv->promise_date) {
                    $promiseDateStr = is_string($inv->promise_date) ? substr($inv->promise_date, 0, 10) : $inv->promise_date->format('Y-m-d');
                    if (!$latestPromiseDate || $promiseDateStr > $latestPromiseDate) {
                        $latestPromiseDate = $promiseDateStr;
                    }
                }
            }
        }

        $isUnpaid = $totalUnpaid > 0;

        return [
            'id' => $item->id,
            'name' => $item->name,
            'customer_code' => $item->customer_code,
            'phone' => $item->phone,
            'latitude' => (float) $item->latitude,
            'longitude' => (float) $item->longitude,
            'address' => $item->address ?: 'Tanpa Alamat',
            'status' => $item->status,
            'service_status' => $item->service_status,
            'is_reseller' => (bool) $item->is_reseller,
            '_type' => $subType,
            'package_id' => $item->package_id,
            'package_name' => $item->package?->name ?? '-',
            'package_price' => (int) ($item->package?->price ?? 0),
            'is_unpaid' => $isUnpaid,
            'status_tagihan' => $isUnpaid ? 'belum_lunas' : 'lunas',
            'total_unpaid' => (int) round($totalUnpaid),
            'unpaid_invoices_count' => $unpaidCount,
            'due_date' => $earliestDueDate,
            'promise_date' => $latestPromiseDate,
            'is_overdue' => $isOverdue,
            'odp' => $odp ? ['id' => $odp->id, 'name' => $odp->name, 'odc_id' => $odp->odc_id] : null,
            'odp_id' => $odp ? $odp->id : null,
            'odc_id' => $odp ? $odp->odc_id : null,
            'odp_name' => $odp ? $odp->name : '-',
            'area_id' => $item->area_id,
            'area_name' => $areaName,
            'distance_meters' => $distanceMeters !== null ? (int) $distanceMeters : null,
            'distance_km' => $distanceMeters !== null ? round($distanceMeters / 1000, 3) : null,
        ];
    }

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

        $cycleEnd = Carbon::now()->endOfMonth();
        $today = Carbon::today()->toDateString();

        $customerQuery = Customer::select('id', 'name', 'customer_code', 'phone', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id', 'area', 'package_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with([
                'ont.odp:id,name,odc_id',
                'areaModel:id,name',
                'package:id,name,price',
                'invoices' => function($q) use ($cycleEnd) {
                    $q->whereIn('status', ['unpaid', 'partial', 'overdue'])
                      ->where('invoice_number', 'not like', 'INV-MIG-%')
                      ->where('is_reseller_balance', false)
                      ->where(function($sq) use ($cycleEnd) {
                          $sq->whereNull('issued_date')
                             ->orWhereDate('issued_date', '<=', $cycleEnd);
                      })
                      ->with(['payments' => function($pq) {
                          $pq->where('status', 'verified')->select('id', 'invoice_id', 'amount', 'status');
                      }])
                      ->orderBy('due_date', 'asc');
                }
            ]);

        $customers = $customerQuery->get()
            ->map(function ($customer) use ($today) {
                return $this->formatCustomerRadarData($customer, $today);
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
            'type' => 'nullable|string',
            'types' => 'nullable|array',
            'area_id' => 'nullable|numeric',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $rawRadius = (float) ($request->radius ?? 5000);
        // Radius dikirim dalam satuan meter dari frontend
        $radiusKm = max(0.01, $rawRadius / 1000);
        $areaId = $request->area_id ? (int) $request->area_id : null;

        // Tentukan jenis target yang ingin dicek (ODP, ODC, Pelanggan)
        $typesInput = $request->input('types');
        if (is_array($typesInput) && !empty($typesInput)) {
            $checkOdp = in_array('odp', $typesInput) || in_array('all', $typesInput);
            $checkOdc = in_array('odc', $typesInput) || in_array('all', $typesInput);
            $checkCustomer = in_array('customer', $typesInput) || in_array('all', $typesInput);
        } else {
            $type = $request->input('type', 'all');
            $checkOdp = in_array($type, ['all', 'odp']) || str_contains($type, 'odp');
            $checkOdc = in_array($type, ['all', 'odc']) || str_contains($type, 'odc');
            $checkCustomer = in_array($type, ['all', 'customer']) || str_contains($type, 'customer');
        }

        $results = [];
        $odpsList = [];
        $odcsList = [];
        $customersList = [];

        // Haversine formula in raw SQL (km) dengan pengaman LEAST/GREATEST agar tidak menghasilkan acos NaN/NULL
        $haversine = "(6371 * acos(LEAST(1.0, GREATEST(-1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))";

        if ($checkOdp) {
            $odpQuery = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id', 'area_id', 'total_ports', 'used_ports', 'status')
                ->selectRaw("$haversine AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->limit(1000)
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

        if ($checkOdc) {
            $odcQuery = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address', 'capacity', 'status', 'area_id')
                ->selectRaw("$haversine AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->limit(500)
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

        if ($checkCustomer) {
            $cycleEnd = Carbon::now()->endOfMonth();
            $today = Carbon::today()->toDateString();

            $customerQuery = Customer::select('id', 'name', 'customer_code', 'phone', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'service_status', 'area_id', 'area', 'package_id')
                ->selectRaw("$haversine AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->limit(2500)
                ->with([
                    'ont.odp:id,name,odc_id',
                    'areaModel:id,name',
                    'package:id,name,price',
                    'invoices' => function($q) use ($cycleEnd) {
                        $q->whereIn('status', ['unpaid', 'partial', 'overdue'])
                          ->where('invoice_number', 'not like', 'INV-MIG-%')
                          ->where('is_reseller_balance', false)
                          ->where(function($sq) use ($cycleEnd) {
                              $sq->whereNull('issued_date')
                                 ->orWhereDate('issued_date', '<=', $cycleEnd);
                          })
                          ->with(['payments' => function($pq) {
                              $pq->where('status', 'verified')->select('id', 'invoice_id', 'amount', 'status');
                          }])
                          ->orderBy('due_date', 'asc');
                    }
                ]);

            if ($areaId) {
                $customerQuery->where('area_id', $areaId);
            }

            $customers = $customerQuery->get()->map(function ($item) use ($today) {
                $distMeters = round($item->distance_km * 1000);
                return $this->formatCustomerRadarData($item, $today, $distMeters);
            });
            $customersList = $customers->values()->toArray();
            $results = array_merge($results, $customersList);
        }

        // Sort all combined results by distance
        usort($results, fn($a, $b) => ($a['distance_km'] ?? 0) <=> ($b['distance_km'] ?? 0));

        $closestOdp = !empty($odpsList) ? $odpsList[0] : null;
        $closestOdc = !empty($odcsList) ? $odcsList[0] : null;
        $closestCustomer = !empty($customersList) ? $customersList[0] : null;

        $unpaidCustomersCount = count(array_filter($customersList, fn($c) => !empty($c['is_unpaid'])));
        $totalNominalUnpaid = (int) array_sum(array_column($customersList, 'total_unpaid'));

        return response()->json([
            'results' => array_slice($results, 0, 3000),
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
                'pelanggan_belum_lunas' => $unpaidCustomersCount,
                'total_nominal_belum_lunas' => $totalNominalUnpaid,
            ],
        ]);
    }
}
