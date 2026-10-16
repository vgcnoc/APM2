<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapacityMonitoringController extends Controller
{
    /**
     * Dashboard Cerdas Monitoring Kapasitas ODC & ODP.
     */
    public function index(Request $request): Response|StreamedResponse
    {
        $user = Auth::user();
        $isRestricted = $user && !$user->hasRole('admin') && !$user->can('network_capacity_view_all');
        $accessibleAreaIds = ($isRestricted && $user->can('network_capacity_view_area'))
            ? $user->getAccessibleAreaIds()
            : null;

        $tab = $request->input('tab', 'odp'); // 'odp', 'odc', 'expansion'
        $statusFilter = $request->input('status', 'all'); // 'all', 'empty', 'available', 'critical', 'full'
        $feasibilityFilter = $request->input('feasibility', 'all'); // 'all', 'can_open', 'cannot_open'
        $areaId = $request->input('area_id');
        $oltId = $request->input('olt_id');
        $odcId = $request->input('odc_id');
        $search = $request->input('search');

        // CSV Export Handler
        if ($request->has('export') && $request->input('export') === 'csv') {
            return $this->exportCsv($tab, $statusFilter, $feasibilityFilter, $areaId, $oltId, $odcId, $search, $accessibleAreaIds);
        }

        // Base Queries with relationships
        $odpBaseQuery = Odp::with(['odc.olt', 'area', 'onts.customer']);
        $odcBaseQuery = Odc::with(['olt', 'area'])->withCount('odps');

        if ($accessibleAreaIds !== null) {
            $odpBaseQuery->whereIn('area_id', $accessibleAreaIds);
            $odcBaseQuery->whereIn('area_id', $accessibleAreaIds);
        }

        // Global KPI Computations
        $allOdps = (clone $odpBaseQuery)->get();
        $allOdcs = (clone $odcBaseQuery)->get();

        // ODP Stats
        $odpTotalCount = $allOdps->count();
        $odpTotalPorts = $allOdps->sum('total_ports');
        $odpUsedPorts = $allOdps->sum('used_ports');
        $odpAvailablePorts = max(0, $odpTotalPorts - $odpUsedPorts);
        $odpUsagePercent = $odpTotalPorts > 0 ? round(($odpUsedPorts / $odpTotalPorts) * 100, 1) : 0;

        $odpEmptyCount = $allOdps->filter(fn($o) => (int)$o->used_ports === 0)->count();
        $odpAvailableCount = $allOdps->filter(fn($o) => (int)$o->used_ports > 0 && ((int)$o->total_ports - (int)$o->used_ports) > 2)->count();
        $odpCriticalCount = $allOdps->filter(fn($o) => ((int)$o->total_ports - (int)$o->used_ports) <= 2 && ((int)$o->total_ports - (int)$o->used_ports) > 0 && (int)$o->used_ports > 0)->count();
        $odpFullCount = $allOdps->filter(fn($o) => (int)$o->used_ports >= (int)$o->total_ports)->count();

        // ODC Stats
        $odcTotalCount = $allOdcs->count();
        $odcTotalCapacity = $allOdcs->sum('capacity');
        $odcUsedSlots = $allOdcs->sum('odps_count');
        $odcAvailableSlots = max(0, $odcTotalCapacity - $odcUsedSlots);
        $odcUsagePercent = $odcTotalCapacity > 0 ? round(($odcUsedSlots / $odcTotalCapacity) * 100, 1) : 0;

        $odcEmptyCount = $allOdcs->filter(fn($o) => (int)$o->odps_count === 0)->count();
        $odcAvailableCount = $allOdcs->filter(fn($o) => (int)$o->odps_count > 0 && ((int)$o->capacity - (int)$o->odps_count) > 1)->count();
        $odcCriticalCount = $allOdcs->filter(fn($o) => ((int)$o->capacity - (int)$o->odps_count) === 1)->count();
        $odcFullCount = $allOdcs->filter(fn($o) => (int)$o->odps_count >= (int)$o->capacity)->count();

        // Transform ODPs
        $odpQuery = clone $odpBaseQuery;
        if ($areaId) $odpQuery->where('area_id', $areaId);
        if ($odcId) $odpQuery->where('odc_id', $odcId);
        if ($oltId) {
            $odpQuery->whereHas('odc', fn($q) => $q->where('olt_id', $oltId));
        }
        if ($search) {
            $odpQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('odc', fn($oq) => $oq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('area', fn($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }

        $transformedOdps = $odpQuery->get()->map(function ($odp) {
            $total = (int) $odp->total_ports;
            $used = (int) $odp->used_ports;
            $available = max(0, $total - $used);
            $usagePct = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            if ($used === 0) {
                $statusCategory = 'empty';
                $statusLabel = 'Masih Kosong (100% Siap)';
                $statusColor = 'cyan';
            } elseif ($available === 0) {
                $statusCategory = 'full';
                $statusLabel = 'Penuh (0 Port Tersisa)';
                $statusColor = 'red';
            } elseif ($available <= 2) {
                $statusCategory = 'critical';
                $statusLabel = "Kritis (Sisa {$available} Port)";
                $statusColor = 'amber';
            } else {
                $statusCategory = 'available';
                $statusLabel = "Ada Slot (Sisa {$available} Port)";
                $statusColor = 'emerald';
            }

            $canRegisterCustomer = ($available > 0) && ($odp->status === 'active');

            $parentOdc = $odp->odc;
            $parentOdcRemaining = 0;
            $parentOdcCanExpand = false;
            if ($parentOdc) {
                $parentOdcRemaining = max(0, (int)$parentOdc->capacity - (int)$parentOdc->odps()->count());
                $parentOdcCanExpand = ($parentOdcRemaining > 0) && ($parentOdc->status === 'active');
            }

            if ($available === 0) {
                if ($parentOdcCanExpand) {
                    $recommendation = "🔴 ODP Penuh: Segera tarik ODP baru dari ODC induk '{$parentOdc->name}' (Masih ada {$parentOdcRemaining} slot ODC kosong).";
                } else {
                    $recommendation = "⛔ ODP & ODC Penuh: Butuh Rollout ODC baru atau splitter cascade di area ini.";
                }
            } elseif ($available <= 2) {
                $recommendation = "🟠 Waspada: Sisa {$available} port pelanggan. Siapkan rencana ekspansi penambahan ODP sebelum kuota habis.";
            } elseif ($used === 0) {
                $recommendation = "🟢 100% Siap Jual: ODP baru/kosong dengan {$total} port siap aktivasi. Prioritaskan promosi Sales di sekitar lokasi ini.";
            } else {
                $recommendation = "🔵 Siap Pasang Baru: Tersedia {$available} port aktif untuk pendaftaran pelanggan baru.";
            }

            return [
                'id' => $odp->id,
                'name' => $odp->name,
                'type' => $odp->type,
                'address' => $odp->address,
                'latitude' => $odp->latitude,
                'longitude' => $odp->longitude,
                'status' => $odp->status,
                'total_ports' => $total,
                'used_ports' => $used,
                'available_ports' => $available,
                'usage_percent' => $usagePct,
                'status_category' => $statusCategory,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'can_register_customer' => $canRegisterCustomer,
                'parent_odc_id' => $odp->odc_id,
                'parent_odc_name' => $parentOdc?->name,
                'parent_odc_capacity' => (int) ($parentOdc?->capacity ?? 0),
                'parent_odc_remaining' => $parentOdcRemaining,
                'parent_odc_can_expand' => $parentOdcCanExpand,
                'olt_name' => $parentOdc?->olt?->name,
                'olt_id' => $parentOdc?->olt_id,
                'pon_port' => $parentOdc?->pon_port,
                'area_id' => $odp->area_id,
                'area_name' => $odp->area?->name,
                'recommendation' => $recommendation,
                'onts_count' => $odp->onts->count(),
                'active_customers_count' => $odp->onts->filter(fn($o) => $o->customer && $o->customer->status === 'active')->count(),
                'created_at' => $odp->created_at?->format('d/m/Y'),
            ];
        });

        if ($statusFilter !== 'all') {
            $transformedOdps = $transformedOdps->filter(fn($o) => $o['status_category'] === $statusFilter);
        }
        if ($feasibilityFilter === 'can_open') {
            $transformedOdps = $transformedOdps->filter(fn($o) => $o['can_register_customer'] === true);
        } elseif ($feasibilityFilter === 'cannot_open') {
            $transformedOdps = $transformedOdps->filter(fn($o) => $o['can_register_customer'] === false);
        }

        // Transform ODCs
        $odcQuery = clone $odcBaseQuery;
        if ($areaId) $odcQuery->where('area_id', $areaId);
        if ($oltId) $odcQuery->where('olt_id', $oltId);
        if ($search) {
            $odcQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('olt', fn($olq) => $olq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('area', fn($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }

        $transformedOdcs = $odcQuery->get()->map(function ($odc) {
            $capacity = (int) $odc->capacity;
            $odpsCount = (int) $odc->odps_count;
            $availableSlots = max(0, $capacity - $odpsCount);
            $usagePct = $capacity > 0 ? round(($odpsCount / $capacity) * 100, 1) : 0;

            if ($odpsCount === 0) {
                $statusCategory = 'empty';
                $statusLabel = 'Masih Kosong (0 ODP)';
                $statusColor = 'cyan';
            } elseif ($availableSlots === 0) {
                $statusCategory = 'full';
                $statusLabel = 'Penuh (0 Slot ODP)';
                $statusColor = 'red';
            } elseif ($availableSlots === 1) {
                $statusCategory = 'critical';
                $statusLabel = 'Kritis (Sisa 1 Slot)';
                $statusColor = 'amber';
            } else {
                $statusCategory = 'available';
                $statusLabel = "Ada Slot (Sisa {$availableSlots} ODP)";
                $statusColor = 'emerald';
            }

            $canOpenOdp = ($availableSlots > 0) && ($odc->status === 'active');
            $canSplitOdc = ($odc->type === 'Split') || ($odc->status === 'active');

            $odpList = $odc->odps;
            $downstreamTotalPorts = $odpList->sum('total_ports');
            $downstreamUsedPorts = $odpList->sum('used_ports');
            $downstreamAvailablePorts = max(0, $downstreamTotalPorts - $downstreamUsedPorts);
            $downstreamUsagePct = $downstreamTotalPorts > 0 ? round(($downstreamUsedPorts / $downstreamTotalPorts) * 100, 1) : 0;

            if ($availableSlots === 0) {
                $recommendation = "🔴 Slot ODC Habis: Tidak bisa menambah ODP baru langsung dari ODC ini. Rencanakan Split ODC atau Rollout ODC baru dari OLT PON {$odc->pon_port}.";
            } elseif ($availableSlots === 1) {
                $recommendation = "🟠 Kritis: Sisa 1 slot penarikan ODP. Rencanakan kapasitas sebelum wilayah ini padat.";
            } elseif ($odpsCount === 0) {
                $recommendation = "⚪ ODC Idle / Kosong: Belum ada ODP terhubung. Sangat direkomendasikan untuk titik awal penarikan ODP baru.";
            } else {
                $recommendation = "🟢 Siap Ekspansi: Tersedia {$availableSlots} slot ODP keluar yang siap ditarik.";
            }

            return [
                'id' => $odc->id,
                'name' => $odc->name,
                'type' => $odc->type,
                'location' => $odc->location,
                'latitude' => $odc->latitude,
                'longitude' => $odc->longitude,
                'status' => $odc->status,
                'capacity' => $capacity,
                'odps_count' => $odpsCount,
                'available_slots' => $availableSlots,
                'usage_percent' => $usagePct,
                'status_category' => $statusCategory,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'can_open_odp' => $canOpenOdp,
                'can_split_odc' => $canSplitOdc,
                'downstream_total_ports' => $downstreamTotalPorts,
                'downstream_used_ports' => $downstreamUsedPorts,
                'downstream_available_ports' => $downstreamAvailablePorts,
                'downstream_usage_percent' => $downstreamUsagePct,
                'olt_id' => $odc->olt_id,
                'olt_name' => $odc->olt?->name,
                'pon_port' => $odc->pon_port,
                'splitter_ratio' => $odc->splitter_ratio,
                'area_id' => $odc->area_id,
                'area_name' => $odc->area?->name,
                'recommendation' => $recommendation,
                'created_at' => $odc->created_at?->format('d/m/Y'),
            ];
        });

        if ($statusFilter !== 'all') {
            $transformedOdcs = $transformedOdcs->filter(fn($o) => $o['status_category'] === $statusFilter);
        }
        if ($feasibilityFilter === 'can_open') {
            $transformedOdcs = $transformedOdcs->filter(fn($o) => $o['can_open_odp'] === true);
        } elseif ($feasibilityFilter === 'cannot_open') {
            $transformedOdcs = $transformedOdcs->filter(fn($o) => $o['can_open_odp'] === false);
        }

        // Smart Expansion Matrix (Per-Area Analytics)
        $areaQuery = Area::query();
        if ($accessibleAreaIds !== null) {
            $areaQuery->whereIn('id', $accessibleAreaIds);
        }
        if ($areaId) {
            $areaQuery->where('id', $areaId);
        }

        $areas = $areaQuery->orderBy('name')->get();
        $expansionMatrix = $areas->map(function ($area) use ($allOdcs, $allOdps) {
            $areaOdcs = $allOdcs->where('area_id', $area->id);
            $areaOdps = $allOdps->where('area_id', $area->id);

            $odcCount = $areaOdcs->count();
            $odcCap = $areaOdcs->sum('capacity');
            $odcUsed = $areaOdcs->sum('odps_count');
            $odcAvailable = max(0, $odcCap - $odcUsed);

            $odpCount = $areaOdps->count();
            $odpTotalPorts = $areaOdps->sum('total_ports');
            $odpUsedPorts = $areaOdps->sum('used_ports');
            $odpAvailablePorts = max(0, $odpTotalPorts - $odpUsedPorts);
            $odpFullCount = $areaOdps->filter(fn($o) => (int)$o->used_ports >= (int)$o->total_ports)->count();
            $odpCriticalCount = $areaOdps->filter(fn($o) => ((int)$o->total_ports - (int)$o->used_ports) <= 2 && ((int)$o->total_ports - (int)$o->used_ports) > 0)->count();

            $urgency = 'normal';
            $actionRecommendation = '';
            $canAddOdp = $odcAvailable > 0;
            $needsNewOdc = false;

            if ($odpCount === 0 && $odcCount === 0) {
                $urgency = 'idle';
                $actionRecommendation = 'Area Baru: Belum ada infrastruktur ODC/ODP.';
            } elseif ($odpFullCount > 0 && $odcAvailable > 0) {
                $urgency = 'high';
                $actionRecommendation = "⚡ Butuh Tambah ODP: Terdapat {$odpFullCount} ODP penuh. ODC di area ini masih memiliki {$odcAvailable} slot kosong untuk penarikan ODP baru.";
            } elseif ($odpFullCount > 0 && $odcAvailable === 0) {
                $urgency = 'critical';
                $needsNewOdc = true;
                $actionRecommendation = "🚨 KRISIS KAPASITAS: ODP penuh dan seluruh slot ODC telah habis. Butuh Rollout ODC baru segera!";
            } elseif ($odpCriticalCount > 0 && $odcAvailable <= 1) {
                $urgency = 'warning';
                $actionRecommendation = "⚠️ Waspada: Beberapa ODP kritis dan slot ODC menipis (sisa {$odcAvailable}). Rencanakan penambahan ODC/ODP.";
            } elseif ($odpAvailablePorts > 20) {
                $urgency = 'optimal';
                $actionRecommendation = "🎯 Optimal untuk Sales: Kapasitas melimpah ({$odpAvailablePorts} port kosong siap jual). Prioritaskan program penjualan di sini.";
            } else {
                $urgency = 'normal';
                $actionRecommendation = "✅ Kapasitas Stabil: Tersedia {$odpAvailablePorts} port ODP dan {$odcAvailable} slot ODC.";
            }

            return [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'odc_count' => $odcCount,
                'odc_total_slots' => $odcCap,
                'odc_used_slots' => $odcUsed,
                'odc_available_slots' => $odcAvailable,
                'odp_count' => $odpCount,
                'odp_total_ports' => $odpTotalPorts,
                'odp_used_ports' => $odpUsedPorts,
                'odp_available_ports' => $odpAvailablePorts,
                'odp_full_count' => $odpFullCount,
                'odp_critical_count' => $odpCriticalCount,
                'urgency' => $urgency,
                'can_add_odp' => $canAddOdp,
                'needs_new_odc' => $needsNewOdc,
                'recommendation' => $actionRecommendation,
            ];
        });

        // Smart Insights Banner
        $smartInsights = [];
        if ($odpFullCount > 0) {
            $smartInsights[] = [
                'type' => 'danger',
                'title' => "Terdapat {$odpFullCount} ODP dengan Status Penuh (0 Port)",
                'desc' => "Pelanggan baru tidak dapat dipasang pada ODP ini. Periksa ketersediaan slot ODC induknya untuk menambah ODP cabang.",
                'action_label' => 'Lihat ODP Penuh',
                'action_tab' => 'odp',
                'action_status' => 'full'
            ];
        }
        if ($odcAvailableSlots > 0) {
            $smartInsights[] = [
                'type' => 'success',
                'title' => "Tersedia {$odcAvailableSlots} Slot ODP di {$odcTotalCount} ODC",
                'desc' => "Infrastruktur distribusi siap menerima pembangunan/penarikan ODP baru tanpa perlu menambah ODC.",
                'action_label' => 'Lihat ODC Siap Rollout',
                'action_tab' => 'odc',
                'action_feasibility' => 'can_open'
            ];
        }
        if ($odcFullCount > 0) {
            $smartInsights[] = [
                'type' => 'warning',
                'title' => "{$odcFullCount} ODC Telah Mencapai Kapasitas Maksimal",
                'desc' => "Tidak bisa menarik ODP baru langsung dari ODC tersebut. Rencanakan Split ODC atau Rollout ODC baru.",
                'action_label' => 'Lihat ODC Penuh',
                'action_tab' => 'odc',
                'action_status' => 'full'
            ];
        }
        if ($odpEmptyCount > 0) {
            $smartInsights[] = [
                'type' => 'info',
                'title' => "{$odpEmptyCount} ODP Masih 100% Kosong (Idle)",
                'desc' => "Potensi port belum termonetisasi. Informasikan ke tim Sales & Marketing untuk meningkatkan penetrasi pelanggan.",
                'action_label' => 'Lihat ODP Kosong',
                'action_tab' => 'odp',
                'action_status' => 'empty'
            ];
        }

        return Inertia::render('Network/CapacityMonitoring', [
            'tab' => $tab,
            'kpi' => [
                'total_odp' => $odpTotalCount,
                'total_odp_ports' => $odpTotalPorts,
                'used_odp_ports' => $odpUsedPorts,
                'available_odp_ports' => $odpAvailablePorts,
                'odp_usage_percent' => $odpUsagePercent,
                'odp_empty_count' => $odpEmptyCount,
                'odp_available_count' => $odpAvailableCount,
                'odp_critical_count' => $odpCriticalCount,
                'odp_full_count' => $odpFullCount,

                'total_odc' => $odcTotalCount,
                'total_odc_slots' => $odcTotalCapacity,
                'used_odc_slots' => $odcUsedSlots,
                'available_odc_slots' => $odcAvailableSlots,
                'odc_usage_percent' => $odcUsagePercent,
                'odc_empty_count' => $odcEmptyCount,
                'odc_available_count' => $odcAvailableCount,
                'odc_critical_count' => $odcCriticalCount,
                'odc_full_count' => $odcFullCount,
            ],
            'odps' => $transformedOdps->values(),
            'odcs' => $transformedOdcs->values(),
            'expansion_matrix' => $expansionMatrix->values(),
            'smart_insights' => $smartInsights,
            'filter_options' => [
                'areas' => Area::orderBy('name')->get(['id', 'name']),
                'olts' => Olt::where('status', 'active')->get(['id', 'name']),
                'odcs' => Odc::where('status', 'active')->get(['id', 'name', 'area_id', 'capacity']),
            ],
            'filters' => [
                'tab' => $tab,
                'status' => $statusFilter,
                'feasibility' => $feasibilityFilter,
                'area_id' => $areaId ? (int)$areaId : '',
                'olt_id' => $oltId ? (int)$oltId : '',
                'odc_id' => $odcId ? (int)$odcId : '',
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Export data kapasitas ke CSV format Excel-friendly.
     */
    protected function exportCsv($tab, $statusFilter, $feasibilityFilter, $areaId, $oltId, $odcId, $search, $accessibleAreaIds): StreamedResponse
    {
        $filename = "monitoring_kapasitas_{$tab}_" . date('Y-m-d_His') . ".csv";

        return response()->streamDownload(function () use ($tab, $statusFilter, $feasibilityFilter, $areaId, $oltId, $odcId, $search, $accessibleAreaIds) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            if ($tab === 'odc') {
                fputcsv($handle, ['ID ODC', 'Nama ODC', 'Wilayah / Area', 'Induk OLT', 'PON Port', 'Total Kapasitas Slot', 'ODP Terpasang', 'Sisa Slot ODP', 'Persentase Terpakai', 'Status Kapasitas', 'Bisa Buka ODP Baru?', 'Total Port Downstream ODP', 'Port Downstream Terpakai', 'Rekomendasi Cerdas']);

                $query = Odc::with(['olt', 'area'])->withCount('odps');
                if ($accessibleAreaIds !== null) $query->whereIn('area_id', $accessibleAreaIds);
                if ($areaId) $query->where('area_id', $areaId);
                if ($oltId) $query->where('olt_id', $oltId);

                $query->get()->each(function ($odc) use ($handle) {
                    $cap = (int)$odc->capacity;
                    $used = (int)$odc->odps_count;
                    $avail = max(0, $cap - $used);
                    $pct = $cap > 0 ? round(($used / $cap) * 100, 1) : 0;
                    $canOpen = ($avail > 0 && $odc->status === 'active') ? 'BISA' : 'TIDAK (PENUH)';

                    $dsTotal = $odc->odps->sum('total_ports');
                    $dsUsed = $odc->odps->sum('used_ports');

                    $status = $used === 0 ? 'KOSONG' : ($avail === 0 ? 'PENUH' : ($avail === 1 ? 'KRITIS' : 'BELUM PENUH'));

                    fputcsv($handle, [
                        $odc->id,
                        $odc->name,
                        $odc->area?->name ?? '-',
                        $odc->olt?->name ?? '-',
                        $odc->pon_port ?? '-',
                        $cap,
                        $used,
                        $avail,
                        "{$pct}%",
                        $status,
                        $canOpen,
                        $dsTotal,
                        $dsUsed,
                        $avail === 0 ? 'ODC Penuh, Lakukan Split/Rollout Baru' : "Siap Buka {$avail} ODP Baru"
                    ]);
                });
            } else {
                fputcsv($handle, ['ID ODP', 'Nama ODP', 'Wilayah / Area', 'Induk ODC', 'Induk OLT', 'PON Port', 'Total Port', 'Port Terpakai', 'Sisa Port Tersedia', 'Persentase Terpakai', 'Status Kapasitas', 'Bisa Pasang Baru?', 'ODC Induk Masih Ada Slot?', 'Rekomendasi Cerdas']);

                $query = Odp::with(['odc.olt', 'area']);
                if ($accessibleAreaIds !== null) $query->whereIn('area_id', $accessibleAreaIds);
                if ($areaId) $query->where('area_id', $areaId);
                if ($odcId) $query->where('odc_id', $odcId);
                if ($oltId) $query->whereHas('odc', fn($q) => $q->where('olt_id', $oltId));

                $query->get()->each(function ($odp) use ($handle) {
                    $tot = (int)$odp->total_ports;
                    $used = (int)$odp->used_ports;
                    $avail = max(0, $tot - $used);
                    $pct = $tot > 0 ? round(($used / $tot) * 100, 1) : 0;
                    $canRegister = ($avail > 0 && $odp->status === 'active') ? 'BISA' : 'TIDAK (PENUH)';

                    $parentOdc = $odp->odc;
                    $odcAvail = $parentOdc ? max(0, (int)$parentOdc->capacity - (int)$parentOdc->odps()->count()) : 0;
                    $odcCanExpand = $odcAvail > 0 ? "YA (Sisa {$odcAvail} Slot)" : "TIDAK (ODC PENUH)";

                    $status = $used === 0 ? 'KOSONG' : ($avail === 0 ? 'PENUH' : ($avail <= 2 ? 'KRITIS' : 'BELUM PENUH'));

                    fputcsv($handle, [
                        $odp->id,
                        $odp->name,
                        $odp->area?->name ?? '-',
                        $parentOdc?->name ?? '-',
                        $parentOdc?->olt?->name ?? '-',
                        $parentOdc?->pon_port ?? '-',
                        $tot,
                        $used,
                        $avail,
                        "{$pct}%",
                        $status,
                        $canRegister,
                        $odcCanExpand,
                        $avail === 0 ? ($odcAvail > 0 ? "ODP Penuh, Tarik ODP baru dari ODC {$parentOdc?->name}" : "ODP & ODC Penuh, Butuh Rollout ODC Baru") : "Siap Pasang {$avail} Pelanggan Baru"
                    ]);
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
