<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\Area;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NetworkDataController extends Controller
{
    public function index(Request $request): Response
    {
        // Get filter inputs
        $oltId = $request->input('olt_id');
        $odcId = $request->input('odc_id');
        $odpId = $request->input('odp_id');
        $areaId = $request->input('area_id');
        $status = $request->input('status');
        $search = $request->input('search');

        // Base ODP query with relations
        $odpQuery = Odp::with(['odc.olt', 'area', 'onts.customer', 'surveys.customer']);

        // Apply filters
        if ($oltId) {
            $odpQuery->whereHas('odc', function ($q) use ($oltId) {
                $q->where('olt_id', $oltId);
            });
        }
        if ($odcId) {
            $odpQuery->where('odc_id', $odcId);
        }
        if ($odpId) {
            $odpQuery->where('id', $odpId);
        }
        if ($areaId) {
            $odpQuery->where('area_id', $areaId);
        }
        if ($status) {
            $odpQuery->where('status', $status);
        }
        if ($search) {
            $odpQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('kode_odp', 'like', "%{$search}%")
                  ->orWhereHas('onts', function ($oq) use ($search) {
                      $oq->whereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('customer_code', 'like', "%{$search}%");
                      });
                  });
            });
        }

        $odps = $odpQuery->get();

        // Analytics/Stats
        $totalOdc = Odc::count();
        $totalOdp = Odp::count();
        $totalPort = Odp::sum('total_ports');
        $portTerpakai = Odp::sum('used_ports');
        $portTersedia = max(0, $totalPort - $portTerpakai);

        $pelangganAktif = Customer::where('status', 'active')->count();
        $pelangganOffline = Customer::where('status', 'suspended')->count() + Customer::where('status', 'terminated')->count();
        $stopPermanen = Customer::where('status', 'terminated')->count();
        
        $terminatedCustomers = Customer::where('status', 'terminated')->with('ont.odp')->get();
        $cbpRequests = \App\Models\CbpRequest::whereIn('status', ['pending', 'assigned'])->with('customer')->get();

        return Inertia::render('NetworkData/Index', [
            'olts' => Olt::all(),
            'odcs' => Odc::all(),
            'odps_filter' => Odp::all(),
            'areas' => Area::all(),
            'odps' => $odps,
            'stats' => [
                'total_odc' => $totalOdc,
                'total_odp' => $totalOdp,
                'total_port' => $totalPort,
                'port_terpakai' => $portTerpakai,
                'port_tersedia' => $portTersedia,
                'pelanggan_aktif' => $pelangganAktif,
                'pelanggan_offline' => $pelangganOffline,
                'stop_permanen' => $stopPermanen,
            ],
            'terminated_customers' => $terminatedCustomers,
            'cbp_requests' => $cbpRequests,
            'filters' => $request->only(['olt_id', 'odc_id', 'odp_id', 'area_id', 'status', 'search']),
        ]);
    }

    public function terminated(Request $request): Response
    {
        $search = $request->input('search');
        
        $query = Customer::where('status', 'terminated')->with('ont.odp.area', 'ont.odp.odc.olt');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        $customers = $query->latest('updated_at')->paginate(20)->withQueryString();

        return Inertia::render('NetworkData/Terminated', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }
}
