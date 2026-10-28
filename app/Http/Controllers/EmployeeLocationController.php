<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\EmployeeLocationLog;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeLocationController extends Controller
{
    /**
     * Terima ping GPS lokasi real-time dari karyawan yang sedang membuka aplikasi.
     */
    public function ping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->role === 'customer') {
            return response()->json(['success' => false, 'message' => 'Customer tidak dilacak.'], 403);
        }

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $accuracy = isset($validated['accuracy']) ? (float) $validated['accuracy'] : null;

        // Dapatkan nama daerah lengkap via reverse geocoding cerdas
        $locationName = GeocodingService::reverseGeocode($lat, $lng);

        // Update lokasi & keaktifan user
        $user->last_latitude = $lat;
        $user->last_longitude = $lng;
        $user->last_location_name = $locationName;
        $user->last_location_accuracy = $accuracy;
        $user->last_gps_updated_at = now();
        $user->last_seen_at = now();
        $user->is_gps_enabled = true;
        $user->save();

        // Catat riwayat log perpindahan (hanya jika jarak > 100m atau log terakhir > 5 menit lalu)
        $lastLog = EmployeeLocationLog::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        $shouldLog = true;
        if ($lastLog && $lastLog->created_at && $lastLog->created_at->diffInMinutes(now()) < 5) {
            $distKm = GeocodingService::haversineDistance($lat, $lng, (float) $lastLog->latitude, (float) $lastLog->longitude);
            if ($distKm < 0.1) { // kurang dari 100 meter
                $shouldLog = false;
            }
        }

        if ($shouldLog) {
            EmployeeLocationLog::create([
                'user_id' => $user->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'location_name' => $locationName,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lokasi GPS berhasil diperbarui.',
            'location_name' => $locationName,
            'accuracy' => $accuracy,
            'is_online' => true,
            'last_gps_updated_at' => $user->last_gps_updated_at->toIso8601String(),
        ]);
    }

    /**
     * Catat keaktifan jika GPS tidak aktif / izin ditolak browser
     */
    public function pingOffline(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->update([
                'last_seen_at' => now(),
                'is_gps_enabled' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'is_gps_enabled' => false,
            'last_seen_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Halaman Pantauan Lokasi Karyawan (Maps & Deteksi Real-Time)
     */
    public function index(Request $request): Response
    {
        if ($request->user() && $request->user()->role === 'customer') {
            abort(403, 'Akses terbatas untuk staf dan manajemen.');
        }

        $data = $this->buildEmployeeTrackingData($request);

        return Inertia::render('EmployeeLocations/Index', $data);
    }

    /**
     * Endpoint API untuk live polling peta tanpa reload halaman
     */
    public function liveData(Request $request): JsonResponse
    {
        if ($request->user() && $request->user()->role === 'customer') {
            return response()->json(['error' => 'Akses terbatas untuk staf dan manajemen.'], 403);
        }

        $data = $this->buildEmployeeTrackingData($request);

        return response()->json($data);
    }

    /**
     * Susun data karyawan, statistik & peta
     */
    private function buildEmployeeTrackingData(Request $request): array
    {
        $onlineThreshold = now()->subMinutes(5);
        $idleThreshold = now()->subMinutes(30);

        // Ambil semua karyawan aktif (bukan customer)
        $employees = User::where('is_active', true)
            ->whereNotIn('role', ['customer'])
            ->with(['employee:id,email,phone,name,position,photo'])
            ->get();

        $stats = [
            'total' => $employees->count(),
            'online' => 0,
            'idle' => 0,
            'offline' => 0,
            'with_gps' => 0,
            'technicians_online' => 0,
            'sales_online' => 0,
        ];

        $areas = Area::orderBy('name')->get(['id', 'name', 'latitude', 'longitude', 'address']);
        $areaMap = $areas->keyBy('id');

        $employeeList = $employees->map(function ($u) use ($onlineThreshold, $idleThreshold, $areaMap, &$stats) {
            $lastSeen = $u->last_seen_at;
            $isOnline = $lastSeen && $lastSeen >= $onlineThreshold;
            $isIdle = !$isOnline && $lastSeen && $lastSeen >= $idleThreshold;
            $status = $isOnline ? 'online' : ($isIdle ? 'idle' : 'offline');

            if ($status === 'online') {
                $stats['online']++;
                $posLower = strtolower($u->job_position);
                if (str_contains($posLower, 'teknisi')) {
                    $stats['technicians_online']++;
                }
                if (str_contains($posLower, 'sales') || str_contains($posLower, 'marketing')) {
                    $stats['sales_online']++;
                }
            } elseif ($status === 'idle') {
                $stats['idle']++;
            } else {
                $stats['offline']++;
            }

            $hasGps = !empty($u->last_latitude) && !empty($u->last_longitude);
            if ($hasGps && $u->is_gps_enabled) {
                $stats['with_gps']++;
            }

            $areaName = $u->area_id && isset($areaMap[$u->area_id])
                ? $areaMap[$u->area_id]->name
                : ($u->area ?: '-');

            $empPhoto = $u->employee?->photo;
            $photoUrl = $empPhoto
                ? (str_starts_with($empPhoto, 'http') ? $empPhoto : asset('storage/' . $empPhoto))
                : null;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone ?: ($u->employee?->phone ?: '-'),
                'role' => $u->role,
                'job_position' => $u->job_position,
                'area_id' => $u->area_id,
                'area_name' => $areaName,
                'is_on_duty' => (bool) $u->is_on_duty,
                'photo_url' => $photoUrl,
                'latitude' => $hasGps ? (float) $u->last_latitude : null,
                'longitude' => $hasGps ? (float) $u->last_longitude : null,
                'location_name' => $u->last_location_name ?: 'Lokasi GPS belum tercatat',
                'accuracy' => $u->last_location_accuracy ? round((float) $u->last_location_accuracy, 1) : null,
                'is_gps_enabled' => (bool) $u->is_gps_enabled,
                'is_online' => $isOnline,
                'status' => $status,
                'last_seen_at' => $u->last_seen_at?->toIso8601String(),
                'last_seen_human' => $u->last_seen_at ? $u->last_seen_at->diffForHumans() : 'Belum pernah aktif',
                'last_gps_updated_at' => $u->last_gps_updated_at?->toIso8601String(),
                'last_gps_human' => $u->last_gps_updated_at ? $u->last_gps_updated_at->diffForHumans() : 'Belum ada data',
            ];
        });

        // Urutkan: Online di atas, lalu Idle, lalu Offline, lalu Nama A-Z
        $sortedEmployees = $employeeList->sortBy([
            fn ($a, $b) => ($a['status'] === 'online' ? 0 : ($a['status'] === 'idle' ? 1 : 2)) <=> ($b['status'] === 'online' ? 0 : ($b['status'] === 'idle' ? 1 : 2)),
            fn ($a, $b) => strcmp($a['name'], $b['name']),
        ])->values()->all();

        // Kumpulkan daftar jabatan unik untuk filter
        $positions = $employeeList->pluck('job_position')->unique()->filter()->sort()->values()->all();

        return [
            'employees' => $sortedEmployees,
            'stats' => $stats,
            'areas' => $areas,
            'positions' => $positions,
            'server_time' => now()->format('H:i:s d/m/Y'),
        ];
    }
}

