<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;

class NetworkRadarController extends Controller
{
    public function index()
    {
        $odcs = Odc::select('id', 'name', 'latitude', 'longitude', 'location as address')->get();
        
        $odps = Odp::select('id', 'name', 'latitude', 'longitude', 'address', 'odc_id')
            ->with(['odc:id,name'])
            ->get();
            
        $customers = Customer::select('id', 'name', 'customer_code', 'latitude', 'longitude', 'address', 'is_reseller', 'status', 'odp_id')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['odp:id,name'])
            ->get();

        return Inertia::render('NetworkRadar/Index', [
            'odcs' => $odcs,
            'odps' => $odps,
            'customers' => $customers,
        ]);
    }
}
