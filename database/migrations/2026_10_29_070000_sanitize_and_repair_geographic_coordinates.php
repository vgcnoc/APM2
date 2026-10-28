<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sanitize ODPs
        $odps = DB::table('odps')->whereNotNull('latitude')->get();
        foreach ($odps as $odp) {
            $lat = (float) $odp->latitude;
            $lng = (float) $odp->longitude;
            $updated = false;

            // Invert missing negative on Indonesia latitude (South of Equator, e.g. 6.xxx or 7.xxx -> -6.xxx, -7.xxx)
            if ($lat > 5.0 && $lat < 12.0) {
                $lat = -$lat;
                $updated = true;
            }

            // Shifted decimal point e.g. 69.89492 -> -6.989492
            if ($lat > 50.0 && $lat < 90.0) {
                $lat = -($lat / 10.0);
                $updated = true;
            } elseif ($lat < -50.0 && $lat > -90.0) {
                $lat = $lat / 10.0;
                $updated = true;
            }

            // Negative longitude in Indonesia e.g. -106.xxx -> 106.xxx
            if ($lng < 0 && abs($lng) >= 95.0 && abs($lng) <= 145.0) {
                $lng = abs($lng);
                $updated = true;
            }

            // Specific known corrupt ODPs
            if (str_contains($odp->name, 'V-CLD 15') && abs($lat - $lng) < 0.001) {
                $lat = -6.99423300;
                $lng = 106.71750000;
                $updated = true;
            }
            if (str_contains($odp->name, 'V-CLPJK 03') && abs($lng - 106.0) < 0.0001) {
                $lat = -7.00308170;
                $lng = 106.71450000;
                $updated = true;
            }

            if ($updated) {
                DB::table('odps')->where('id', $odp->id)->update([
                    'latitude' => number_format($lat, 8, '.', ''),
                    'longitude' => number_format($lng, 8, '.', ''),
                ]);
            }
        }

        // 2. Sanitize ODCs
        $odcs = DB::table('odcs')->whereNotNull('latitude')->get();
        foreach ($odcs as $odc) {
            $lat = (float) $odc->latitude;
            $lng = (float) $odc->longitude;
            $updated = false;

            if ($lat > 5.0 && $lat < 12.0) {
                $lat = -$lat;
                $updated = true;
            }
            if ($lat > 50.0 && $lat < 90.0) {
                $lat = -($lat / 10.0);
                $updated = true;
            } elseif ($lat < -50.0 && $lat > -90.0) {
                $lat = $lat / 10.0;
                $updated = true;
            }
            if ($lng < 0 && abs($lng) >= 95.0 && abs($lng) <= 145.0) {
                $lng = abs($lng);
                $updated = true;
            }

            if ($updated) {
                DB::table('odcs')->where('id', $odc->id)->update([
                    'latitude' => number_format($lat, 8, '.', ''),
                    'longitude' => number_format($lng, 8, '.', ''),
                ]);
            }
        }

        // 3. Sanitize Customers
        $customers = DB::table('customers')->whereNotNull('latitude')->get();
        foreach ($customers as $c) {
            $lat = (float) $c->latitude;
            $lng = (float) $c->longitude;
            $updated = false;

            // Shifted decimal point e.g. -69.036 -> -6.9036 and 1.067 -> 106.7
            if ($lat < -50.0 && $lat > -90.0) {
                $lat = $lat / 10.0;
                $updated = true;
            }
            if ($lat > 50.0 && $lat < 90.0) {
                $lat = -($lat / 10.0);
                $updated = true;
            }
            if ($lng > 1.0 && $lng < 2.0) {
                $lng = $lng * 100.0;
                $updated = true;
            }

            // Invert missing negative on Indonesia latitude
            if ($lat > 5.0 && $lat < 12.0) {
                $lat = -$lat;
                $updated = true;
            }

            // Negative longitude in Indonesia e.g. -106.xxx -> 106.xxx
            if ($lng < 0 && abs($lng) >= 95.0 && abs($lng) <= 145.0) {
                $lng = abs($lng);
                $updated = true;
            }

            // If longitude is corrupt or identical to latitude, inherit from connected ODP if available
            if (abs($lat - $lng) < 0.001 || $lng < 50.0 || $lng > 150.0) {
                $ont = DB::table('onts')->where('customer_id', $c->id)->first();
                if ($ont && $ont->odp_id) {
                    $parentOdp = DB::table('odps')->where('id', $ont->odp_id)->first();
                    if ($parentOdp && $parentOdp->longitude && (float) $parentOdp->longitude > 95.0) {
                        $lng = (float) $parentOdp->longitude;
                        if ($lat > 0) $lat = (float) $parentOdp->latitude;
                        $updated = true;
                    }
                }
            }

            // If latitude was entered as 10.67xxxx (swapped or mistyped)
            if ($lat > 10.0 && $lat < 11.0 && $lng > 105.0 && $lng < 108.0) {
                $ont = DB::table('onts')->where('customer_id', $c->id)->first();
                if ($ont && $ont->odp_id) {
                    $parentOdp = DB::table('odps')->where('id', $ont->odp_id)->first();
                    if ($parentOdp && $parentOdp->latitude && (float) $parentOdp->latitude < 0) {
                        $lat = (float) $parentOdp->latitude;
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                DB::table('customers')->where('id', $c->id)->update([
                    'latitude' => number_format($lat, 8, '.', ''),
                    'longitude' => number_format($lng, 8, '.', ''),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for coordinate sanitization
    }
};

