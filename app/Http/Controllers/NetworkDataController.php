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
    /**
     * Ekspansi daftar ID ODC agar selalu menyertakan pasangan Split-nya (Induk <-> Lanjutan).
     */
    protected function expandSplitOdcIds(array $odcIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $odcIds))));
        if (empty($ids)) {
            return [];
        }

        $parents = Odc::whereIn('id', $ids)->whereNotNull('parent_odc_id')->pluck('parent_odc_id')->map(fn ($v) => (int) $v)->all();
        $rootIds = array_values(array_unique(array_merge($ids, $parents)));
        $children = Odc::whereIn('parent_odc_id', $rootIds)->pluck('id')->map(fn ($v) => (int) $v)->all();

        return array_values(array_unique(array_merge($rootIds, $children)));
    }

    /**
     * Ekspansi daftar ID ODP agar selalu menyertakan pasangan Split 2-nya (Induk <-> Lanjutan).
     */
    protected function expandSplitOdpIds(array $odpIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $odpIds))));
        if (empty($ids)) {
            return [];
        }

        $parents = Odp::whereIn('id', $ids)->whereNotNull('parent_odp_id')->pluck('parent_odp_id')->map(fn ($v) => (int) $v)->all();
        $rootIds = array_values(array_unique(array_merge($ids, $parents)));
        $children = Odp::whereIn('parent_odp_id', $rootIds)->pluck('id')->map(fn ($v) => (int) $v)->all();

        return array_values(array_unique(array_merge($rootIds, $children)));
    }

    public function index(Request $request): Response
    {
        @ini_set('memory_limit', '512M');

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

        $odcRelations = [
            'olt:id,name,pon_vlans',
            'area:id,name',
            'parent:id,name,capacity,olt_id,pon_port,splitter_ratio,type,is_split,location,parent_odc_id',
            'children:id,name,capacity,olt_id,pon_port,splitter_ratio,type,is_split,location,parent_odc_id',
        ];

        $odpRelations = [
            'odc:id,name,capacity,olt_id,area_id,pon_port,splitter_ratio,type,is_split,location,parent_odc_id,start_point,end_point,cable_pull',
            'odc.olt:id,name,pon_vlans',
            'odc.area:id,name',
            'odc.parent:id,name,capacity,olt_id,pon_port,splitter_ratio,type,is_split,location,parent_odc_id',
            'odc.children:id,name,capacity,olt_id,pon_port,splitter_ratio,type,is_split,location,parent_odc_id',
            'area:id,name',
            'parent:id,name,odc_id,total_ports,used_ports,type,is_split,parent_odp_id',
            'children:id,name,odc_id,total_ports,used_ports,type,is_split,parent_odp_id',
            'onts' => function ($oq) {
                $oq->select(['id', 'odp_id', 'port_number', 'customer_id', 'vlan_id', 'hotspot_vlan_id', 'status'])
                   ->with('customer:id,customer_code,name,status,service_status,activation_date,created_at');
            },
            'surveys' => function ($sq) {
                $sq->select(['id', 'odp_id', 'port_number', 'customer_id', 'created_at'])
                   ->with('customer:id,customer_code,name,status');
            },
        ];

        $expandedOdcFilterIds = $odcId ? $this->expandSplitOdcIds([(int) $odcId]) : [];
        $expandedOdpFilterIds = $odpId ? $this->expandSplitOdpIds([(int) $odpId]) : [];

        if (\App\Services\DataPrivacyMaskingService::isEnabledForUser($user, 'network_data')) {
            $maskedCustomerIds = \App\Services\DataPrivacyMaskingService::getMaskedCustomerIds();
            $odpRelations['onts'] = function($oq) use ($maskedCustomerIds) {
                $oq->select(['id', 'odp_id', 'port_number', 'customer_id', 'vlan_id', 'hotspot_vlan_id', 'status'])
                   ->where(function($q) use ($maskedCustomerIds) {
                       $q->whereNull('customer_id')
                         ->orWhereIn('customer_id', !empty($maskedCustomerIds) ? $maskedCustomerIds : [0]);
                   })->with('customer:id,customer_code,name,status,service_status,activation_date,created_at');
            };
        }

        // Base ODP query with relations
        $odpQuery = Odp::with($odpRelations);
        DataScopeService::applyOdpScope($odpQuery, $user);

        // Apply filters
        if ($oltId) {
            $odpQuery->whereHas('odc', function ($q) use ($oltId) {
                $q->where('olt_id', $oltId);
            });
        }
        if (!empty($expandedOdcFilterIds)) {
            $odpQuery->whereIn('odc_id', $expandedOdcFilterIds);
        }
        if (!empty($expandedOdpFilterIds)) {
            $odpQuery->whereIn('id', $expandedOdpFilterIds);
        }
        if ($areaId) {
            $odpQuery->where(function ($q) use ($areaId) {
                if (is_numeric($areaId)) {
                    $q->where('area_id', (int) $areaId)
                      ->orWhere(function ($fallback) use ($areaId) {
                          $fallback->whereNull('area_id')
                              ->whereHas('odc', fn($odcq) => $odcq->where('area_id', (int) $areaId));
                      });
                } else {
                    $q->whereHas('area', fn($aq) => $aq->where('name', $areaId))
                      ->orWhere(function ($fallback) use ($areaId) {
                          $fallback->whereNull('area_id')
                              ->whereHas('odc.area', fn($aq) => $aq->where('name', $areaId));
                      });
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
                  ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                  ->orWhereHas('children', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                  ->orWhereHas('odc', function ($odcq) use ($search) {
                      $odcq->where('name', 'like', "%{$search}%")
                           ->orWhere('location', 'like', "%{$search}%")
                           ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                           ->orWhereHas('children', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                  })
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
                      $olq->where('name', 'like', "%{$search}%")
                          ->orWhere('pon_vlans', 'like', "%{$search}%");
                  });
            });
        }

        $odps = $odpQuery
            ->orderByRaw('COALESCE(parent_odp_id, id) ASC, CASE WHEN parent_odp_id IS NULL THEN 0 ELSE 1 END ASC, id ASC')
            ->get();

        // Jika hasil pencarian Pelanggan/ONT mengenai salah satu unit dari pasangan ODP Split 2,
        // sertakan juga unit pasangannya (Induk / Lanjutan) agar keduanya tetap tampil berdampingan utuh!
        if ($odps->isNotEmpty() && !$status) {
            $currentOdpIds = $odps->pluck('id')->map(fn ($v) => (int) $v)->all();
            $allSplitPartnerOdpIds = $this->expandSplitOdpIds($currentOdpIds);
            $missingPartnerOdpIds = array_values(array_diff($allSplitPartnerOdpIds, $currentOdpIds));

            if (!empty($missingPartnerOdpIds)) {
                $partnerOdpQuery = Odp::with($odpRelations)->whereIn('id', $missingPartnerOdpIds);
                DataScopeService::applyOdpScope($partnerOdpQuery, $user);
                $partnerOdps = $partnerOdpQuery->get();
                if ($partnerOdps->isNotEmpty()) {
                    $odps = $odps->concat($partnerOdps)->unique('id')->values();
                }
            }
        }

        // Kumpulkan seluruh ODC yang relevan agar pasangan ODC Split (termasuk unit yang belum punya ODP) selalu tampil berdampingan!
        $candidateOdcIds = $odps->pluck('odc_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();

        if (!$odpId && !$status) {
            $extraOdcQuery = Odc::query();
            DataScopeService::applyOdcScope($extraOdcQuery, $user);

            if ($oltId) {
                $extraOdcQuery->where('olt_id', $oltId);
            }
            if ($areaId) {
                $extraOdcQuery->where(function ($q) use ($areaId) {
                    if (is_numeric($areaId)) {
                        $q->where('area_id', (int) $areaId);
                    } else {
                        $q->whereHas('area', fn ($aq) => $aq->where('name', $areaId));
                    }
                });
            }

            if (!empty($expandedOdcFilterIds)) {
                $extraOdcQuery->whereIn('id', $expandedOdcFilterIds);
            } elseif ($search) {
                $extraOdcQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('children', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('olt', fn ($oq) => $oq->where('name', 'like', "%{$search}%"));
                });
            } else {
                // Tanpa filter ODP/Status/Search: sertakan seluruh ODC bertipe Split agar pasangan Induk & Lanjutan tidak pernah hilang meskipun 0 ODP
                $extraOdcQuery->where(function ($q) {
                    $q->where('is_split', true)
                      ->orWhere('type', 'Split')
                      ->orWhereNotNull('parent_odc_id')
                      ->orWhereHas('children');
                });
            }

            $extraIds = $extraOdcQuery->pluck('id')->map(fn ($v) => (int) $v)->all();
            $candidateOdcIds = array_values(array_unique(array_merge($candidateOdcIds, $extraIds)));
        }

        $finalDisplayOdcIds = $this->expandSplitOdcIds($candidateOdcIds);
        $displayOdcs = collect();
        if (!empty($finalDisplayOdcIds)) {
            $displayOdcQuery = Odc::with($odcRelations)
                ->whereIn('id', $finalDisplayOdcIds)
                ->orderByRaw('COALESCE(parent_odc_id, id) ASC, CASE WHEN parent_odc_id IS NULL THEN 0 ELSE 1 END ASC, id ASC');
            DataScopeService::applyOdcScope($displayOdcQuery, $user);
            $displayOdcs = $displayOdcQuery->get();
        }

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
        if (!empty($expandedOdcFilterIds)) {
            $baseOdpQuery->whereIn('odc_id', $expandedOdcFilterIds);
        }
        if ($areaId) {
            $baseOdpQuery->where(function ($q) use ($areaId) {
                if (is_numeric($areaId)) {
                    $q->where('area_id', (int) $areaId)
                      ->orWhere(function ($fallback) use ($areaId) {
                          $fallback->whereNull('area_id')
                              ->whereHas('odc', fn($odcq) => $odcq->where('area_id', (int) $areaId));
                      });
                } else {
                    $q->whereHas('area', fn($aq) => $aq->where('name', $areaId))
                      ->orWhere(function ($fallback) use ($areaId) {
                          $fallback->whereNull('area_id')
                              ->whereHas('odc.area', fn($aq) => $aq->where('name', $areaId));
                      });
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
        
        $termCustQuery = Customer::where('status', 'terminated')
            ->select(['id', 'customer_code', 'name', 'status', 'notes', 'updated_at', 'area_id'])
            ->with([
                'ont:id,customer_id,odp_id,port_number',
                'ont.odp:id,name',
                'latestCbpRequest.technicians:id,name',
            ]);
        DataScopeService::applyCustomerScope($termCustQuery, $user, 'network_data');
        $terminatedCustomers = $termCustQuery->get();

        $cbpQuery = \App\Models\CbpRequest::whereIn('status', ['pending', 'assigned'])
            ->select(['id', 'cbp_number', 'customer_id', 'status', 'assigned_to', 'notes', 'created_at'])
            ->with([
                'customer:id,customer_code,name',
                'technicians:id,name',
            ]);
        DataScopeService::applyCbpScope($cbpQuery, $user);
        $cbpRequests = $cbpQuery->get();

        $oltFilterQuery = Olt::query()->select(['id', 'name', 'pon_vlans'])->orderBy('name');
        DataScopeService::applyOltScope($oltFilterQuery, $user);

        $odcFilterQuery = Odc::select(['id', 'name', 'olt_id', 'area_id', 'parent_odc_id', 'is_split'])
            ->with(['parent:id,name,parent_odc_id', 'children:id,name,parent_odc_id'])
            ->orderByRaw('COALESCE(parent_odc_id, id) ASC, CASE WHEN parent_odc_id IS NULL THEN 0 ELSE 1 END ASC, id ASC');
        DataScopeService::applyOdcScope($odcFilterQuery, $user);

        $odpFilterQuery = Odp::select(['id', 'name', 'odc_id', 'area_id', 'parent_odp_id', 'is_split', 'total_ports', 'used_ports'])
            ->with(['parent:id,name,parent_odp_id', 'children:id,name,parent_odp_id'])
            ->orderByRaw('COALESCE(parent_odp_id, id) ASC, CASE WHEN parent_odp_id IS NULL THEN 0 ELSE 1 END ASC, id ASC');
        DataScopeService::applyOdpScope($odpFilterQuery, $user);

        return Inertia::render('NetworkData/Index', [
            'olts' => $oltFilterQuery->get(),
            'odcs' => $odcFilterQuery->get(),
            'display_odcs' => $displayOdcs,
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
        @ini_set('memory_limit', '512M');

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
