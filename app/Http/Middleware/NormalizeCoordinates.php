<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeCoordinates
{
    /**
     * Handle an incoming request.
     * Auto-converts comma (,) to dot (.) for latitude and longitude fields,
     * auto-splits if full coordinate pair or Google Maps URL is pasted,
     * and auto-corrects swapped or sign-inverted Indonesian coordinates.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $data = $request->all();
        $modified = false;

        $normalizeSingle = function ($val) {
            if (!is_string($val)) return $val;
            $trimmed = trim($val);
            if ($trimmed === '') return null;
            if (preg_match('/^-?\d+,\d+$/', $trimmed)) {
                return str_replace(',', '.', $trimmed);
            }
            return $trimmed;
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

            $rawLat = isset($data[$latKey]) ? trim((string) $data[$latKey]) : '';
            $rawLng = isset($data[$lngKey]) ? trim((string) $data[$lngKey]) : '';

            // 1. Check if user pasted a Google Maps URL into latitude
            if ($rawLat !== '' && (str_contains($rawLat, 'google.com/maps') || str_contains($rawLat, 'goo.gl') || str_contains($rawLat, '@') || str_contains($rawLat, 'q='))) {
                if (preg_match('/@(-?\d+(?:[.,]\d+)?),(-?\d+(?:[.,]\d+)?)/', $rawLat, $matches) ||
                    preg_match('/[?&]q=(-?\d+(?:[.,]\d+)?),(-?\d+(?:[.,]\d+)?)/', $rawLat, $matches)) {
                    $rawLat = str_replace(',', '.', $matches[1]);
                    $rawLng = str_replace(',', '.', $matches[2]);
                    $data[$latKey] = $rawLat;
                    $data[$lngKey] = $rawLng;
                    $modified = true;
                }
            }

            // 2. Check if user pasted coordinate pair into latitude (e.g. "-6.123, 106.123" or "-6,123, 106,123")
            if ($rawLat !== '' && (empty($rawLng) || $rawLat === $rawLng || !is_numeric($rawLng))) {
                if (preg_match('/^(-?\d+(?:[.,]\d+)?)\s*[,/ ;|]\s*(-?\d+(?:[.,]\d+)?)$/', $rawLat, $matches)) {
                    $rawLat = str_replace(',', '.', $matches[1]);
                    $rawLng = str_replace(',', '.', $matches[2]);
                    $data[$latKey] = $rawLat;
                    $data[$lngKey] = $rawLng;
                    $modified = true;
                }
            }

            // 3. Normalize single numbers with commas
            if ($rawLat !== '') {
                $normLat = $normalizeSingle($rawLat);
                if ($normLat !== $rawLat) {
                    $rawLat = $normLat;
                    $data[$latKey] = $rawLat;
                    $modified = true;
                }
            }
            if ($rawLng !== '') {
                $normLng = $normalizeSingle($rawLng);
                if ($normLng !== $rawLng) {
                    $rawLng = $normLng;
                    $data[$lngKey] = $rawLng;
                    $modified = true;
                }
            }

            // 4. Numeric Bounds & Sanity Auto-Corrections
            if (is_numeric($rawLat) && is_numeric($rawLng)) {
                $numLat = (float) $rawLat;
                $numLng = (float) $rawLng;
                $pairModified = false;

                // Swap if swapped (Latitude cannot exceed 90; in Indonesia lng is ~106, lat is ~-7)
                if (abs($numLat) > 90 || ($numLat > 20 && $numLng <= 20)) {
                    $tmp = $numLat;
                    $numLat = $numLng;
                    $numLng = $tmp;
                    $pairModified = true;
                }

                // In Indonesia (Java, Bali, etc.), latitudes between 5 and 12 should be negative
                if ($numLat > 5.0 && $numLat < 12.0 && $numLng >= 95.0 && $numLng <= 145.0) {
                    $numLat = -$numLat;
                    $pairModified = true;
                }

                // Shifted decimal point e.g. 69.89492 -> -6.989492
                if ($numLat > 50.0 && $numLat < 90.0) {
                    $numLat = -($numLat / 10.0);
                    $pairModified = true;
                } elseif ($numLat < -50.0 && $numLat > -90.0) {
                    $numLat = $numLat / 10.0;
                    $pairModified = true;
                }

                // Invert negative longitude in Indonesia
                if ($numLng < 0 && abs($numLng) >= 95.0 && abs($numLng) <= 145.0) {
                    $numLng = abs($numLng);
                    $pairModified = true;
                }

                if ($pairModified) {
                    $data[$latKey] = (string) $numLat;
                    $data[$lngKey] = (string) $numLng;
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
