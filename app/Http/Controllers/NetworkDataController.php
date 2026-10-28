<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\Area;
use App\Models\Customer;
use App\Services\DataScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NetworkDataController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin() && !$user->can('menu_network_data')) {
            abort(403, 'Anda tidak memiliki hak akses ke menu Data Jaringan.');
        }

        // Get filter inputs
        $oltId = $request->input('olt_id');
        $odcId = $request->input('odc_id');
        $odpId = $request->input('odp_id');
        $areaId = $request->input('area_id');
        $status = $request->input('status');
        $search = $request->input('search');

        // Base ODP query with relations
        $odpQuery = Odp::with(['odc.olt', 'area', 'onts.customer.latestCbpRequest.technicians', 'surveys.customer']);
        DataScopeService::applyOdpScope($odpQuery, $user);

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
            $odpQuery->where(function ($q) use ($areaId) {
                if (is_numeric($areaId)) {
                    $q->where('area_id', $areaId)
                      ->orWhereHas('odc', fn($odcq) => $odcq->where('area_id', $areaId))
                      ->orWhereHas('area', fn($aq) => $aq->where('id', $areaId));
                } else {
                    $q->whereHas('area', fn($aq) => $aq->where('name', $areaId))
                      ->orWhereHas('odc.area', fn($aq) => $aq->where('name', $areaId));
                }
            });
        }
        if ($status) {
            $odpQuery->where('status', $status);
        }
        if ($search) {
            $odpQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('start_point', 'like', "%{$search}%")
                  ->orWhere('end_point', 'like', "%{$search}%")
                  ->orWhereHas('onts', function ($oq) use ($search) {
                      $oq->where('vlan_id', 'like', "%{$search}%")
                         ->orWhere('hotspot_vlan_id', 'like', "%{$search}%")
                         ->orWhereHas('customer', function ($cq) use ($search) {
                             $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%")
                                ->when(stripos($search, 'gratis') !== false, fn($sq) => $sq->orWhere('service_status', 'gratis'));
                         });
                  })
                  ->orWhereHas('odc.olt', function ($olq) use ($search) {
                      $olq->where('pon_vlans', 'like', "%{$search}%");
                  });
            });
        }

        $odps = $odpQuery->get();

        // Analytics/Stats with data scope
        $baseOdcQuery = Odc::query();
        DataScopeService::applyOdcScope($baseOdcQuery, $user);
        if ($oltId) {
            $baseOdcQuery->where('olt_id', $oltId);
        }
        if ($areaId) {
            $baseOdcQuery->where(function ($q) use ($areaId) {
                if (is_numeric($areaId)) $q->where('area_id', $areaId);
                else $q->whereHas('area', fn($aq) => $aq->where('name', $areaId));
            });
        }
        $totalOdc = $baseOdcQuery->count();

        $baseOdpQuery = Odp::query();
        DataScopeService::applyOdpScope($baseOdpQuery, $user);
        if ($oltId) {
            $baseOdpQuery->whereHas('odc', fn($q) => $q->where('olt_id', $oltId));
        }
        if ($odcId) {
            $baseOdpQuery->where('odc_id', $odcId);
        }
        if ($areaId) {
            $baseOdpQuery->where(function ($q) use ($areaId) {
                if (is_numeric($areaId)) {
                    $q->where('area_id', $areaId)->orWhereHas('odc', fn($odcq) => $odcq->where('area_id', $areaId));
                } else {
                    $q->whereHas('area', fn($aq) => $aq->where('name', $areaId))->orWhereHas('odc.area', fn($aq) => $aq->where('name', $areaId));
                }
            });
        }
        $totalOdp = (clone $baseOdpQuery)->count();
        $totalPort = (clone $baseOdpQuery)->sum('total_ports');
        $portTerpakai = (clone $baseOdpQuery)->sum('used_ports');
        $portTersedia = max(0, $totalPort - $portTerpakai);

        $baseCustomerQuery = Customer::query();
        DataScopeService::applyCustomerScope($baseCustomerQuery, $user, 'network_data');
        if ($areaId) {
            $baseCustomerQuery->where(function ($q) use ($areaId) {
                if (is_numeric($areaId)) {
                    $q->where('area_id', $areaId)->orWhere('area', \App\Models\Area::where('id', $areaId)->value('name'));
                } else {
                    $q->where('area', $areaId)->orWhereHas('areaModel', fn($aq) => $aq->where('name', $areaId));
                }
            });
        }

        $pelangganAktif = (clone $baseCustomerQuery)->where('status', 'active')->count();
        $pelangganGratis = (clone $baseCustomerQuery)->where('service_status', 'gratis')->where('status', 'active')->count();
        $pelangganOffline = (clone $baseCustomerQuery)->whereIn('status', ['suspended', 'terminated'])->count();
        $stopPermanen = (clone $baseCustomerQuery)->where('status', 'terminated')->count();

        $baseCbpQuery = \App\Models\CbpRequest::whereIn('status', ['pending', 'assigned']);
        DataScopeService::applyCbpScope($baseCbpQuery, $user);
        $totalProsesCbp = (clone $baseCbpQuery)->count();
        
        $termCustQuery = Customer::where('status', 'terminated')->with(['ont.odp', 'latestCbpRequest.technicians']);
        DataScopeService::applyCustomerScope($termCustQuery, $user, 'network_data');
        $terminatedCustomers = $termCustQuery->get();

        $cbpQuery = \App\Models\CbpRequest::whereIn('status', ['pending', 'assigned'])->with(['customer', 'technicians']);
        DataScopeService::applyCbpScope($cbpQuery, $user);
        $cbpRequests = $cbpQuery->get();

        $oltFilterQuery = Olt::query();
        DataScopeService::applyOltScope($oltFilterQuery, $user);

        $odcFilterQuery = Odc::query();
        DataScopeService::applyOdcScope($odcFilterQuery, $user);

        $odpFilterQuery = Odp::query();
        DataScopeService::applyOdpScope($odpFilterQuery, $user);

        return Inertia::render('NetworkData/Index', [
            'olts' => $oltFilterQuery->get(),
            'odcs' => $odcFilterQuery->get(),
            'odps_filter' => $odpFilterQuery->get(),
            'areas' => DataScopeService::getScopedAreas($user, 'network_data'),
            'odps' => $odps,
            'stats' => [
                'total_odc' => $totalOdc,
                'total_odp' => $totalOdp,
                'total_port' => $totalPort,
                'port_terpakai' => $portTerpakai,
                'port_tersedia' => $portTersedia,
                'pelanggan_aktif' => $pelangganAktif,
                'pelanggan_gratis' => $pelangganGratis,
                'pelanggan_offline' => $pelangganOffline,
                'stop_permanen' => $stopPermanen,
                'proses_cbp' => $totalProsesCbp,
            ],
            'terminated_customers' => $terminatedCustomers,
            'cbp_requests' => $cbpRequests,
            'filters' => $request->only(['olt_id', 'odc_id', 'odp_id', 'area_id', 'status', 'search']),
        ]);
    }

    public function terminated(Request $request): Response
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin() && !$user->can('menu_network_data_terminated') && !$user->can('menu_cbp')) {
            abort(403, 'Anda tidak memiliki hak akses ke menu Cabut Perangkat.');
        }

        $search = $request->input('search');
        
        $query = Customer::where('status', 'terminated')->with(['ont.odp.area', 'ont.odp.odc.olt', 'latestCbpRequest.technicians']);
        
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
