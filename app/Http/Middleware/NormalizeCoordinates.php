<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeCoordinates
{
    /**
     * Handle an incoming request.
     * Auto-converts comma (,) to dot (.) for latitude and longitude fields
     * and auto-splits if full coordinate pair is pasted into latitude.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $data = $request->all();
        $modified = false;

        $normalizeValue = function ($val) {
            if (is_string($val)) {
                $trimmed = trim($val);
                if ($trimmed === '') {
                    return null;
                }
                // Convert comma to dot if string contains numbers with comma (e.g. -6,123456 -> -6.123456)
                if (preg_match('/^-?\d+,\d+$/', $trimmed)) {
                    return str_replace(',', '.', $trimmed);
                }
            }
            return $val;
        };

        // Standard coordinate field names
        $coordPairs = [
            ['lat' => 'latitude', 'lng' => 'longitude'],
            ['lat' => 'lat', 'lng' => 'lng'],
            ['lat' => 'lat_in', 'lng' => 'lng_in'],
            ['lat' => 'lat_out', 'lng' => 'lng_out'],
        ];

        foreach ($coordPairs as $pair) {
            $latKey = $pair['lat'];
            $lngKey = $pair['lng'];

            if (isset($data[$latKey]) && is_string($data[$latKey])) {
                $trimmedLat = trim($data[$latKey]);

                // Check if user pasted both coordinates into the latitude field (e.g. "-6.123, 106.123" or "-6,123, 106,123")
                if (empty($data[$lngKey])) {
                    if (preg_match('/^(-?\d+(?:[.,]\d+)?)\s*[,/ ]\s*(-?\d+(?:[.,]\d+)?)$/', $trimmedLat, $matches)) {
                        $data[$latKey] = str_replace(',', '.', $matches[1]);
                        $data[$lngKey] = str_replace(',', '.', $matches[2]);
                        $modified = true;
                        continue;
                    }
                }

                $normalizedLat = $normalizeValue($trimmedLat);
                if ($normalizedLat !== $data[$latKey]) {
                    $data[$latKey] = $normalizedLat;
                    $modified = true;
                }
            }

            if (isset($data[$lngKey]) && is_string($data[$lngKey])) {
                $normalizedLng = $normalizeValue(trim($data[$lngKey]));
                if ($normalizedLng !== $data[$lngKey]) {
                    $data[$lngKey] = $normalizedLng;
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $request->merge($data);
        }

        return $next($request);
    }
}
