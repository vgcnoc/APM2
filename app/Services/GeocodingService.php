<?php

namespace App\Services;

use App\Models\Area;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Lakukan reverse geocoding dari latitude & longitude menjadi nama daerah lengkap di Indonesia.
     * Menggunakan cache 7 hari per ~100m koordinat agar super cepat dan hemat kuota/API.
     */
    public static function reverseGeocode($latitude, $longitude): string
    {
        if (empty($latitude) || empty($longitude)) {
            return 'Lokasi belum terdeteksi';
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        // Bulatkan koordinat ke 3 desimal (~100 meter) untuk cache key
        $latRound = round($lat, 3);
        $lngRound = round($lng, 3);
        $cacheKey = "reverse_geo_{$latRound}_{$lngRound}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
            try {
                $response = Http::timeout(3)
                    ->withHeaders([
                        'User-Agent' => 'APM2-ISP-Management/1.0 (internal-location-monitor)',
                        'Accept-Language' => 'id,en;q=0.9',
                    ])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'jsonv2',
                        'lat' => $lat,
                        'lon' => $lng,
                        'zoom' => 18,
                        'addressdetails' => 1,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $addr = $data['address'] ?? [];

                    $parts = [];

                    // 1. Jalan / Kampung / Dusun
                    $road = $addr['road'] ?? $addr['hamlet'] ?? $addr['suburb'] ?? $addr['neighbourhood'] ?? null;
                    if ($road && !str_starts_with(strtolower($road), 'rt')) {
                        $parts[] = $road;
                    }

                    // 2. Desa / Kelurahan
                    $village = $addr['village'] ?? $addr['quarter'] ?? $addr['residential'] ?? null;
                    if ($village) {
                        $parts[] = (str_contains(strtolower($village), 'desa') || str_contains(strtolower($village), 'kelurahan'))
                            ? $village
                            : 'Desa ' . $village;
                    }

                    // 3. Kecamatan
                    $subdistrict = $addr['subdistrict'] ?? $addr['municipality'] ?? $addr['city_district'] ?? null;
                    if ($subdistrict) {
                        $parts[] = str_contains(strtolower($subdistrict), 'kec')
                            ? $subdistrict
                            : 'Kec. ' . $subdistrict;
                    }

                    // 4. Kabupaten / Kota
                    $city = $addr['county'] ?? $addr['city'] ?? $addr['regency'] ?? null;
                    if ($city) {
                        $parts[] = $city;
                    }

                    if (!empty($parts)) {
                        return implode(', ', array_unique($parts));
                    }

                    if (!empty($data['display_name'])) {
                        // Ambil 3-4 komponen pertama dari display_name
                        $displayParts = explode(',', $data['display_name']);
                        return implode(',', array_slice($displayParts, 0, 4));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('[GeocodingService] Reverse geocode request failed: ' . $e->getMessage());
            }

            // Fallback: Cari Area terdekat di database (BC 1, BC 2, BC 3, BC 4)
            return self::findNearestAreaName($lat, $lng);
        });
    }

    /**
     * Temukan nama area operasional terdekat berdasarkan koordinat
     */
    public static function findNearestAreaName(float $lat, float $lng): string
    {
        try {
            $areas = Area::whereNotNull('latitude')->whereNotNull('longitude')->get(['id', 'name', 'latitude', 'longitude', 'address']);
            $nearestArea = null;
            $minDistanceKm = 999999;

            foreach ($areas as $area) {
                $dist = self::haversineDistance($lat, $lng, (float) $area->latitude, (float) $area->longitude);
                if ($dist < $minDistanceKm) {
                    $minDistanceKm = $dist;
                    $nearestArea = $area;
                }
            }

            if ($nearestArea && $minDistanceKm <= 10.0) {
                $desc = $nearestArea->address ? " ({$nearestArea->address})" : '';
                return "Wilayah {$nearestArea->name}{$desc} [±" . round($minDistanceKm, 1) . " km]";
            }
        } catch (\Throwable $e) {
            // Ignore error
        }

        return 'Koordinat (' . round($lat, 5) . ', ' . round($lng, 5) . ')';
    }

    /**
     * Hitung jarak Haversine dalam kilometer
     */
    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

