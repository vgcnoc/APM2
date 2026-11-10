<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Customer;
use App\Models\CustomerMaterialDismantle;
use App\Models\CustomerSocializationVisit;
use App\Models\Employee;
use App\Models\Material;
use App\Models\NetworkMaintenance;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\PgjTicket;
use App\Models\PgkTicket;
use App\Models\PgsTicket;
use App\Models\User;
use App\Services\DataScopeService;
use App\Services\ImageCompressionService;
use App\Services\RestockStockService;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ChronicMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', '90_days'); // 7_days, 30_days, 90_days, this_month, all
        $areaId = $request->input('area_id');
        $minTickets = max(1, (int) $request->input('min_tickets', 1));
        $search = trim((string) $request->input('search', ''));
        $visitStatus = $request->input('visit_status', 'all');
        $myTasksOnly = $request->boolean('my_tasks');

        $user = auth()->user();
        $isGlobal = $user && (
            $user->hasRole('admin') ||
            $user->hasRole('super admin') ||
            $user->role === 'admin' ||
            $user->can('operational_progress_view_all') ||
            $user->can('pgk_view_all') ||
            $user->can('pgs_view_all')
        );
        $accessibleAreaIds = (!$isGlobal && $user) ? $user->getAccessibleAreaIds() : null;

        $targetAreaIds = null;
        if ($accessibleAreaIds !== null) {
            if (empty($accessibleAreaIds)) {
                $targetAreaIds = [-1];
                $areaId = null;
            } else {
                if ($areaId && in_array((int) $areaId, $accessibleAreaIds, true)) {
                    $targetAreaIds = [(int) $areaId];
                } else {
                    $targetAreaIds = $accessibleAreaIds;
                    if ($areaId) {
                        $areaId = $accessibleAreaIds[0];
                    }
                }
            }
        } elseif ($areaId) {
            $targetAreaIds = [(int) $areaId];
        }

        $areas = DataScopeService::getScopedAreas($user, 'operational_progress');

        // Determine start date from period
        $startDate = match ($period) {
            '7_days' => Carbon::now()->subDays(7)->startOfDay(),
            '30_days' => Carbon::now()->subDays(30)->startOfDay(),
            '90_days' => Carbon::now()->subDays(90)->startOfDay(),
            'this_month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        // ── 1. QUERY TIKET PGK & PGS UNTUK ANALISA PELANGGAN & ODP ─────────────
        $pgkQuery = PgkTicket::with(['technicians', 'leadTechnician', 'resolvedByUser'])
            ->whereNotNull('customer_id');
        $pgsQuery = PgsTicket::with(['technicians', 'leadTechnician', 'resolvedByUser'])
            ->whereNotNull('customer_id');
        $pgjQuery = PgjTicket::with(['area', 'olt', 'odc', 'odp', 'technicians', 'leadTechnician']);

        if ($startDate) {
            $pgkQuery->where('created_at', '>=', $startDate);
            $pgsQuery->where('created_at', '>=', $startDate);
            $pgjQuery->where('created_at', '>=', $startDate);
        }

        if (!empty($targetAreaIds)) {
            $pgkQuery->where(function ($q) use ($targetAreaIds) {
                $q->whereIn('area_id', $targetAreaIds)
                  ->orWhereHas('customer', fn ($cq) => $cq->whereIn('area_id', $targetAreaIds));
            });
            $pgsQuery->where(function ($q) use ($targetAreaIds) {
                $q->whereIn('area_id', $targetAreaIds)
                  ->orWhereHas('customer', fn ($cq) => $cq->whereIn('area_id', $targetAreaIds));
            });
            $pgjQuery->whereIn('area_id', $targetAreaIds);
        }

        $pgkTickets = $pgkQuery->orderByDesc('created_at')->get();
        $pgsTickets = $pgsQuery->orderByDesc('created_at')->get();
        $pgjTickets = $pgjQuery->orderByDesc('created_at')->get();

        // Group tickets by customer_id
        $customerTicketsMap = [];
        foreach ($pgkTickets as $t) {
            $cid = (int) $t->customer_id;
            if (!isset($customerTicketsMap[$cid])) {
                $customerTicketsMap[$cid] = ['pgk' => [], 'pgs' => []];
            }
            $customerTicketsMap[$cid]['pgk'][] = $t;
        }
        foreach ($pgsTickets as $t) {
            $cid = (int) $t->customer_id;
            if (!isset($customerTicketsMap[$cid])) {
                $customerTicketsMap[$cid] = ['pgk' => [], 'pgs' => []];
            }
            $customerTicketsMap[$cid]['pgs'][] = $t;
        }

        $affectedCustomerIds = array_keys($customerTicketsMap);

        // Load Customer Models with ONT, ODP, ODC, Area, Package
        $customersById = collect();
        if (!empty($affectedCustomerIds)) {
            $customersById = Customer::with([
                'areaModel',
                'package',
                'ont.odp.odc.olt',
            ])->whereIn('id', $affectedCustomerIds)->get()->keyBy('id');
        }

        // Load Material Dismantles for these customers
        $dismantlesByCustomer = collect();
        if (!empty($affectedCustomerIds) && Schema::hasTable('customer_material_dismantles')) {
            $dismantlesByCustomer = CustomerMaterialDismantle::with('material')
                ->whereIn('customer_id', $affectedCustomerIds)
                ->get()
                ->groupBy('customer_id');
        }

        // Load Socialization Visits for these customers
        $visitsByCustomer = collect();
        $hasVisitsTable = Schema::hasTable('customer_socialization_visits');
        if ($hasVisitsTable && !empty($affectedCustomerIds)) {
            $visitsByCustomer = CustomerSocializationVisit::with(['officers', 'completedByUser'])
                ->whereIn('customer_id', $affectedCustomerIds)
                ->orderByDesc('scheduled_date')
                ->orderByDesc('id')
                ->get()
                ->groupBy('customer_id');
        }

        // Build Chronic Customers List
        $chronicCustomers = [];
        $odpCustomerTicketsMap = []; // odp_id => ['tickets' => [], 'customers' => []]
        $odcCustomerTicketsMap = []; // odc_id => ['tickets' => [], 'customers' => []]

        foreach ($customerTicketsMap as $cid => $grouped) {
            $customer = $customersById->get($cid);
            if (!$customer) {
                continue;
            }

            $pgkList = $grouped['pgk'];
            $pgsList = $grouped['pgs'];
            $pgkCount = count($pgkList);
            $pgsCount = count($pgsList);
            $totalTickets = $pgkCount + $pgsCount;

            $ont = $customer->ont;
            $odp = $ont?->odp;
            $odc = $odp?->odc;
            $olt = $odc?->olt;

            // Aggregate categories & timeline
            $categoryCounts = [];
            $timeline = [];
            $openCount = 0;
            $ontReplacedCount = 0;
            $latestOpticalAfter = null;

            $allMerged = [];
            foreach ($pgkList as $t) {
                $allMerged[] = ['type' => 'PGK', 'model' => $t];
            }
            foreach ($pgsList as $t) {
                $allMerged[] = ['type' => 'PGS', 'model' => $t];
            }

            usort($allMerged, fn ($a, $b) => strcmp((string) $b['model']->created_at, (string) $a['model']->created_at));

            foreach ($allMerged as $entry) {
                $t = $entry['model'];
                $type = $entry['type'];
                $cat = trim((string) ($t->category ?: 'Gangguan Umum'));
                $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;

                $isDone = in_array($t->status, ['completed', 'resolved_by_admin'], true);
                if (!$isDone) {
                    $openCount++;
                }
                if (!empty($t->prepared_ont_id) || $t->ont_replacement_status === 'completed') {
                    $ontReplacedCount++;
                }
                if ($latestOpticalAfter === null && !empty($t->optical_power_after)) {
                    $latestOpticalAfter = $t->optical_power_after;
                }

                $techNames = $t->technicians ? $t->technicians->pluck('name')->toArray() : [];
                if ($t->leadTechnician && !in_array($t->leadTechnician->name, $techNames, true)) {
                    array_unshift($techNames, $t->leadTechnician->name);
                }

                $timeline[] = [
                    'id' => $t->id,
                    'type' => $type,
                    'ticket_number' => $t->ticket_number,
                    'category' => $cat,
                    'priority' => $t->priority ?? 'normal',
                    'status' => $t->status,
                    'is_done' => $isDone,
                    'reported_issue' => $t->reported_issue,
                    'action_taken' => $t->action_taken ?: $t->resolution_notes,
                    'optical_power_before' => $t->optical_power_before,
                    'optical_power_after' => $t->optical_power_after,
                    'technicians' => !empty($techNames) ? implode(', ', $techNames) : ($t->resolvedByUser?->name ?? 'Admin / NOC'),
                    'created_at' => $t->created_at ? Carbon::parse($t->created_at)->format('d M Y H:i') : '-',
                    'created_date' => $t->created_at ? Carbon::parse($t->created_at)->toDateString() : null,
                    'resolved_at' => $t->resolved_at ? Carbon::parse($t->resolved_at)->format('d M Y H:i') : null,
                    'ont_replaced' => !empty($t->prepared_ont_id) || $t->ont_replacement_status === 'completed',
                ];
            }

            arsort($categoryCounts);
            $topCategories = [];
            foreach ($categoryCounts as $cName => $cTotal) {
                $topCategories[] = ['category' => $cName, 'count' => $cTotal];
            }

            $rxPower = $ont?->rx_power !== null ? (float) $ont->rx_power : null;
            if ($rxPower === null && $latestOpticalAfter !== null) {
                $parsed = preg_replace('/[^0-9.\-]/', '', (string) $latestOpticalAfter);
                if (is_numeric($parsed)) {
                    $rxPower = (float) $parsed;
                }
            }
            $isBadRx = $rxPower !== null && $rxPower < -25.0;

            $dismantles = $dismantlesByCustomer->get($cid, collect());
            $materialReplacedCount = $dismantles->count();
            $replacedItemsSummary = $dismantles->map(fn ($d) => ($d->material?->name ?? $d->custom_material_name ?? 'Material') . ' (' . (float) $d->quantity . ' ' . ($d->unit ?? 'pcs') . ')')->take(5)->values()->toArray();

            $customerVisits = $visitsByCustomer->get($cid, collect());
            $latestVisit = $customerVisits->first();
            $completedVisitsCount = $customerVisits->where('status', 'completed')->count();
            $hasActiveVisit = $customerVisits->whereIn('status', ['scheduled', 'in_progress', 'rescheduled'])->isNotEmpty();

            // Determine Severity & Recommendation
            $severity = 'normal';
            if ($totalTickets >= 3 || ($totalTickets >= 2 && $isBadRx)) {
                $severity = 'critical';
            } elseif ($totalTickets === 2 || $isBadRx) {
                $severity = 'warning';
            }

            $dominantCat = $topCategories[0]['category'] ?? 'Gangguan Koneksi';
            $recommendations = [];
            if ($totalTickets >= 3) {
                $recommendations[] = "Kronis ({$totalTickets}x tiket): Wajib inspeksi total jalur dropcore/konektor & utus petugas sosialisasi ke rumah pelanggan.";
            } elseif ($totalTickets === 2) {
                $recommendations[] = "Berulang (2x tiket - {$dominantCat}): Perlu evaluasi kualitas sambungan & kunjungan sosialisasi.";
            }
            if ($isBadRx) {
                $recommendations[] = "Redaman rawan ({$rxPower} dBm): Perbaiki redaman optik hingga di atas -24 dBm.";
            }
            if ($ontReplacedCount > 0) {
                $recommendations[] = "Sudah ganti ONT {$ontReplacedCount}x.";
            }
            if (empty($recommendations)) {
                $recommendations[] = "Pantau kestabilan layanan pasca penanganan {$dominantCat}.";
            }

            $areaName = $customer->areaModel?->name ?: ($customer->area ?: '-');

            // Feed into ODP / ODC aggregation
            if ($odp) {
                $odpId = $odp->id;
                if (!isset($odpCustomerTicketsMap[$odpId])) {
                    $odpCustomerTicketsMap[$odpId] = [
                        'odp' => $odp,
                        'pgk_count' => 0,
                        'pgs_count' => 0,
                        'customers' => [],
                        'tickets' => [],
                    ];
                }
                $odpCustomerTicketsMap[$odpId]['pgk_count'] += $pgkCount;
                $odpCustomerTicketsMap[$odpId]['pgs_count'] += $pgsCount;
                $odpCustomerTicketsMap[$odpId]['customers'][$cid] = [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'port_number' => $ont?->port_number,
                    'rx_power' => $rxPower,
                    'total_tickets' => $totalTickets,
                    'dominant_category' => $dominantCat,
                    'has_visited' => $completedVisitsCount > 0,
                    'has_scheduled_visit' => $hasActiveVisit,
                ];
                foreach ($timeline as $tl) {
                    $odpCustomerTicketsMap[$odpId]['tickets'][] = array_merge($tl, [
                        'customer_name' => $customer->name,
                        'customer_code' => $customer->customer_code,
                    ]);
                }
            }

            if ($odc) {
                $odcId = $odc->id;
                if (!isset($odcCustomerTicketsMap[$odcId])) {
                    $odcCustomerTicketsMap[$odcId] = [
                        'odc' => $odc,
                        'pgk_count' => 0,
                        'pgs_count' => 0,
                        'customers' => [],
                        'tickets' => [],
                    ];
                }
                $odcCustomerTicketsMap[$odcId]['pgk_count'] += $pgkCount;
                $odcCustomerTicketsMap[$odcId]['pgs_count'] += $pgsCount;
                $odcCustomerTicketsMap[$odcId]['customers'][$cid] = [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'port_number' => $ont?->port_number,
                    'rx_power' => $rxPower,
                    'total_tickets' => $totalTickets,
                    'dominant_category' => $dominantCat,
                    'has_visited' => $completedVisitsCount > 0,
                    'has_scheduled_visit' => $hasActiveVisit,
                ];
                foreach ($timeline as $tl) {
                    $odcCustomerTicketsMap[$odcId]['tickets'][] = array_merge($tl, [
                        'customer_name' => $customer->name,
                        'customer_code' => $customer->customer_code,
                    ]);
                }
            }

            if ($totalTickets < $minTickets) {
                continue;
            }

            if ($search !== '') {
                $haystack = strtolower(
                    $customer->name . ' ' .
                    $customer->customer_code . ' ' .
                    $customer->phone . ' ' .
                    $customer->address . ' ' .
                    $areaName . ' ' .
                    ($odp?->name ?? '') . ' ' .
                    ($odc?->name ?? '') . ' ' .
                    $dominantCat
                );
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $chronicCustomers[] = [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'area_id' => $customer->area_id ?: $odp?->area_id,
                'area_name' => $areaName,
                'package_name' => $customer->package?->name ?? '-',
                'status' => $customer->status,
                'ont_sn' => $ont?->serial_number ?? '-',
                'rx_power' => $rxPower,
                'is_bad_rx' => $isBadRx,
                'odp_id' => $odp?->id,
                'odp_name' => $odp?->name ?? 'Belum Terhubung ODP',
                'odp_port' => $ont?->port_number,
                'odc_id' => $odc?->id,
                'odc_name' => $odc?->name ?? '-',
                'olt_name' => $olt?->name ?? '-',
                'pgk_count' => $pgkCount,
                'pgs_count' => $pgsCount,
                'total_tickets' => $totalTickets,
                'open_tickets_count' => $openCount,
                'ont_replaced_count' => $ontReplacedCount,
                'material_replaced_count' => $materialReplacedCount,
                'replaced_items_summary' => $replacedItemsSummary,
                'top_categories' => array_slice($topCategories, 0, 3),
                'dominant_category' => $dominantCat,
                'last_ticket_at' => $timeline[0]['created_at'] ?? '-',
                'last_issue' => $timeline[0]['reported_issue'] ?? '-',
                'last_action_taken' => $timeline[0]['action_taken'] ?? '-',
                'severity' => $severity,
                'recommendation' => implode(' ', $recommendations),
                'socialization_status' => $hasActiveVisit
                    ? 'scheduled'
                    : ($completedVisitsCount > 0 ? 'visited' : 'unvisited'),
                'completed_visits_count' => $completedVisitsCount,
                'latest_visit' => $latestVisit ? [
                    'id' => $latestVisit->id,
                    'visit_number' => $latestVisit->visit_number,
                    'status' => $latestVisit->status,
                    'scheduled_date' => $latestVisit->scheduled_date ? Carbon::parse($latestVisit->scheduled_date)->format('d M Y') : '-',
                    'officers' => $latestVisit->officers->pluck('name')->join(', ') ?: 'Petugas Lapangan',
                    'met_with_name' => $latestVisit->met_with_name,
                    'customer_response' => $latestVisit->customer_response,
                    'response_label' => $latestVisit->response_label,
                    'report_notes' => $latestVisit->report_notes,
                    'photo_visit_urls' => $latestVisit->photo_visit_urls,
                ] : null,
                'tickets_timeline' => $timeline,
            ];
        }

        // Sort Chronic Customers: highest total_tickets first, then open_tickets_count, then worst rx_power
        usort($chronicCustomers, function ($a, $b) {
            if ($b['total_tickets'] !== $a['total_tickets']) {
                return $b['total_tickets'] <=> $a['total_tickets'];
            }
            if ($b['open_tickets_count'] !== $a['open_tickets_count']) {
                return $b['open_tickets_count'] <=> $a['open_tickets_count'];
            }
            return ($a['rx_power'] ?? 0) <=> ($b['rx_power'] ?? 0);
        });

        // ── 2. ANALISA JALUR / ODP / ODC SERING GANGGUAN ──────────────────────
        $pgjByOdp = $pgjTickets->whereNotNull('odp_id')->groupBy('odp_id');
        $pgjByOdc = $pgjTickets->whereNotNull('odc_id')->groupBy('odc_id');

        $allAffectedOdpIds = array_unique(array_merge(
            array_keys($odpCustomerTicketsMap),
            $pgjByOdp->keys()->toArray()
        ));

        $odpModels = !empty($allAffectedOdpIds)
            ? Odp::with(['area', 'odc.olt', 'onts'])->whereIn('id', $allAffectedOdpIds)->get()->keyBy('id')
            : collect();

        $latestMaintByOdp = collect();
        $latestMaintByOdc = collect();
        if (Schema::hasTable('network_maintenances')) {
            if (!empty($allAffectedOdpIds)) {
                $latestMaintByOdp = NetworkMaintenance::with(['technicians', 'materials.material'])
                    ->whereIn('odp_id', $allAffectedOdpIds)
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('odp_id');
            }
            $allAffectedOdcIds = array_unique(array_merge(
                array_keys($odcCustomerTicketsMap),
                $pgjByOdc->keys()->toArray()
            ));
            if (!empty($allAffectedOdcIds)) {
                $latestMaintByOdc = NetworkMaintenance::with(['technicians', 'materials.material'])
                    ->whereIn('odc_id', $allAffectedOdcIds)
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('odc_id');
            }
        }

        $chronicPaths = [];
        foreach ($allAffectedOdpIds as $odpId) {
            $odp = $odpModels->get($odpId) ?? ($odpCustomerTicketsMap[$odpId]['odp'] ?? null);
            if (!$odp) {
                continue;
            }

            $pgjList = $pgjByOdp->get($odpId, collect());
            $pgjCount = $pgjList->count();
            $custData = $odpCustomerTicketsMap[$odpId] ?? ['pgk_count' => 0, 'pgs_count' => 0, 'customers' => [], 'tickets' => []];
            $custTicketCount = $custData['pgk_count'] + $custData['pgs_count'];
            $totalIncidents = $pgjCount + $custTicketCount;
            $affectedCustomersList = array_values($custData['customers']);
            $affectedCustomersCount = count($affectedCustomersList);

            if ($totalIncidents < $minTickets) {
                continue;
            }

            $areaName = $odp->area?->name ?? '-';
            $odcName = $odp->odc?->name ?? '-';
            $oltName = $odp->odc?->olt?->name ?? '-';

            if ($search !== '') {
                $haystack = strtolower($odp->name . ' ' . $odcName . ' ' . $oltName . ' ' . $areaName . ' ' . ($odp->address ?? ''));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            // Calculate average and worst Rx Power of ONTs on this ODP
            $ontRxValues = $odp->relationLoaded('onts')
                ? $odp->onts->whereNotNull('rx_power')->pluck('rx_power')->map(fn ($v) => (float) $v)->filter(fn ($v) => $v < 0)->values()
                : collect();

            $avgRx = $ontRxValues->isNotEmpty() ? round($ontRxValues->avg(), 2) : null;
            $worstRx = $ontRxValues->isNotEmpty() ? round($ontRxValues->min(), 2) : null;
            $badRxCount = $ontRxValues->filter(fn ($v) => $v < -25.0)->count();

            $severity = 'normal';
            if ($pgjCount >= 2 || $affectedCustomersCount >= 3 || $totalIncidents >= 4 || $badRxCount >= 3) {
                $severity = 'critical';
            } elseif ($pgjCount >= 1 || $affectedCustomersCount >= 2 || $totalIncidents >= 2 || $badRxCount >= 1) {
                $severity = 'warning';
            }

            $diagParts = [];
            if ($pgjCount > 0) {
                $diagParts[] = "Tercatat {$pgjCount}x tiket Gangguan Jalur (PGJ) langsung pada ODP ini.";
            }
            if ($affectedCustomersCount >= 2) {
                $diagParts[] = "Ada {$affectedCustomersCount} pelanggan berbeda di ODP ini yang mengalami gangguan ({$custTicketCount} tiket PGK/PGS) — indikasi kuat splitter/adapter ODP kotor atau jalur distribusi dari ODC bermasalah.";
            } elseif ($custTicketCount >= 2) {
                $diagParts[] = "Terdapat {$custTicketCount}x keluhan berulang dari pelanggan di ODP ini.";
            }
            if ($badRxCount > 0) {
                $diagParts[] = "{$badRxCount} pelanggan memiliki redaman buruk (< -25 dBm, terburuk {$worstRx} dBm).";
            }
            if (empty($diagParts)) {
                $diagParts[] = "Terdapat {$totalIncidents} insiden tercatat pada ODP ini.";
            }

            $maintList = $latestMaintByOdp->get($odpId, collect());
            $latestMaint = $maintList->first();

            // Merge PGJ tickets into recent_tickets
            $recentTickets = $custData['tickets'];
            foreach ($pgjList as $pgj) {
                $recentTickets[] = [
                    'id' => $pgj->id,
                    'type' => 'PGJ',
                    'ticket_number' => $pgj->ticket_number,
                    'category' => $pgj->category ?: 'Gangguan Jalur',
                    'priority' => $pgj->priority ?? 'high',
                    'status' => $pgj->status,
                    'is_done' => in_array($pgj->status, ['completed', 'resolved_by_admin'], true),
                    'reported_issue' => $pgj->reported_issue,
                    'action_taken' => $pgj->action_taken ?: $pgj->resolution_notes,
                    'customer_name' => "Jalur ODP ({$pgj->affected_customers_count} Pelanggan)",
                    'customer_code' => $odp->name,
                    'created_at' => $pgj->created_at ? Carbon::parse($pgj->created_at)->format('d M Y H:i') : '-',
                ];
            }

            $chronicPaths[] = [
                'node_key' => 'ODP-' . $odp->id,
                'node_type' => 'ODP',
                'id' => $odp->id,
                'odp_id' => $odp->id,
                'odc_id' => $odp->odc_id,
                'name' => $odp->name,
                'parent_name' => $odcName !== '-' ? "ODC: {$odcName} / OLT: {$oltName}" : "OLT: {$oltName}",
                'area_id' => $odp->area_id,
                'area_name' => $areaName,
                'address' => $odp->address ?? '-',
                'latitude' => $odp->latitude,
                'longitude' => $odp->longitude,
                'used_ports' => (int) $odp->used_ports,
                'total_ports' => (int) $odp->total_ports,
                'pgj_count' => $pgjCount,
                'pgk_count' => $custData['pgk_count'],
                'pgs_count' => $custData['pgs_count'],
                'customer_ticket_count' => $custTicketCount,
                'total_incidents' => $totalIncidents,
                'affected_customers_count' => $affectedCustomersCount,
                'avg_rx_power' => $avgRx,
                'worst_rx_power' => $worstRx,
                'bad_rx_ont_count' => $badRxCount,
                'severity' => $severity,
                'diagnosis' => implode(' ', $diagParts),
                'latest_maintenance' => $latestMaint ? [
                    'id' => $latestMaint->id,
                    'maintenance_number' => $latestMaint->maintenance_number,
                    'status' => $latestMaint->status,
                    'scheduled_date' => $latestMaint->scheduled_date ? Carbon::parse($latestMaint->scheduled_date)->format('d M Y') : '-',
                    'scheduled_date_raw' => $latestMaint->scheduled_date ? Carbon::parse($latestMaint->scheduled_date)->toDateString() : Carbon::today()->toDateString(),
                    'action_taken' => $latestMaint->action_taken,
                    'technicians' => $latestMaint->technicians->pluck('name')->join(', ') ?: 'Tim Maintenance',
                    'materials_summary' => $latestMaint->materials->map(function ($m) {
                        $nm = $m->material?->name ?? 'Material';
                        $q = (float) $m->quantity_prepared;
                        $u = $m->unit ?: ($m->material?->unit ?? 'pcs');
                        return "{$nm} ({$q} {$u})";
                    })->values()->toArray(),
                ] : null,
                'affected_customers' => $affectedCustomersList,
                'recent_tickets' => array_slice($recentTickets, 0, 15),
            ];
        }

        // Also include ODCs that had direct PGJ tickets without specific ODP
        foreach ($pgjByOdc as $odcId => $pgjOdcList) {
            $odcModel = Odc::with(['area', 'olt'])->find($odcId);
            if (!$odcModel) {
                continue;
            }
            $pgjCount = $pgjOdcList->count();
            $custAgg = $odcCustomerTicketsMap[$odcId] ?? ['pgk_count' => 0, 'pgs_count' => 0, 'customers' => [], 'tickets' => []];
            $custTicketCount = $custAgg['pgk_count'] + $custAgg['pgs_count'];
            $totalIncidents = $pgjCount + $custTicketCount;
            if ($totalIncidents < $minTickets) {
                continue;
            }

            if ($search !== '') {
                $odcAreaName = $odcModel->area?->name ?? '';
                $odcOltName = $odcModel->olt?->name ?? '';
                $haystack = strtolower($odcModel->name . ' ' . $odcOltName . ' ' . $odcAreaName . ' ' . ($odcModel->address ?? ''));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }

            $odcMaintList = $latestMaintByOdc->get($odcId, collect());
            $latestOdcMaint = $odcMaintList->first();

            $odcRecentTickets = $custAgg['tickets'] ?? [];
            foreach ($pgjOdcList as $pgj) {
                $odcRecentTickets[] = [
                    'id' => $pgj->id,
                    'type' => 'PGJ',
                    'ticket_number' => $pgj->ticket_number,
                    'category' => $pgj->category ?: 'Gangguan Jalur ODC',
                    'priority' => $pgj->priority ?? 'high',
                    'status' => $pgj->status,
                    'is_done' => in_array($pgj->status, ['completed', 'resolved_by_admin'], true),
                    'reported_issue' => $pgj->reported_issue,
                    'action_taken' => $pgj->action_taken ?: $pgj->resolution_notes,
                    'customer_name' => "Jalur Distribusi ODC ({$pgj->affected_customers_count} Pelanggan)",
                    'customer_code' => $odcModel->name,
                    'created_at' => $pgj->created_at ? Carbon::parse($pgj->created_at)->format('d M Y H:i') : '-',
                ];
            }

            $chronicPaths[] = [
                'node_key' => 'ODC-' . $odcModel->id,
                'node_type' => 'ODC',
                'id' => $odcModel->id,
                'odp_id' => null,
                'odc_id' => $odcModel->id,
                'name' => $odcModel->name,
                'parent_name' => 'OLT: ' . ($odcModel->olt?->name ?? '-'),
                'area_id' => $odcModel->area_id,
                'area_name' => $odcModel->area?->name ?? '-',
                'address' => $odcModel->address ?? '-',
                'latitude' => $odcModel->latitude,
                'longitude' => $odcModel->longitude,
                'used_ports' => (int) ($odcModel->used_ports ?? 0),
                'total_ports' => (int) ($odcModel->total_ports ?? 0),
                'pgj_count' => $pgjCount,
                'pgk_count' => $custAgg['pgk_count'],
                'pgs_count' => $custAgg['pgs_count'],
                'customer_ticket_count' => $custTicketCount,
                'total_incidents' => $totalIncidents,
                'affected_customers_count' => count($custAgg['customers']),
                'avg_rx_power' => null,
                'worst_rx_power' => null,
                'bad_rx_ont_count' => 0,
                'severity' => $pgjCount >= 2 || $totalIncidents >= 4 ? 'critical' : 'warning',
                'diagnosis' => "Jalur distribusi utama ODC tercatat {$pgjCount}x PGJ & {$custTicketCount}x tiket pelanggan di bawahnya.",
                'latest_maintenance' => $latestOdcMaint ? [
                    'id' => $latestOdcMaint->id,
                    'maintenance_number' => $latestOdcMaint->maintenance_number,
                    'status' => $latestOdcMaint->status,
                    'scheduled_date' => $latestOdcMaint->scheduled_date ? Carbon::parse($latestOdcMaint->scheduled_date)->format('d M Y') : '-',
                    'scheduled_date_raw' => $latestOdcMaint->scheduled_date ? Carbon::parse($latestOdcMaint->scheduled_date)->toDateString() : Carbon::today()->toDateString(),
                    'action_taken' => $latestOdcMaint->action_taken,
                    'technicians' => $latestOdcMaint->technicians->pluck('name')->join(', ') ?: 'Tim Maintenance',
                    'materials_summary' => $latestOdcMaint->materials->map(function ($m) {
                        $nm = $m->material?->name ?? 'Material';
                        $q = (float) $m->quantity_prepared;
                        $u = $m->unit ?: ($m->material?->unit ?? 'pcs');
                        return "{$nm} ({$q} {$u})";
                    })->values()->toArray(),
                ] : null,
                'affected_customers' => array_values($custAgg['customers']),
                'recent_tickets' => array_slice($odcRecentTickets, 0, 15),
            ];
        }

        usort($chronicPaths, function ($a, $b) {
            if ($b['total_incidents'] !== $a['total_incidents']) {
                return $b['total_incidents'] <=> $a['total_incidents'];
            }
            return $b['affected_customers_count'] <=> $a['affected_customers_count'];
        });

        // ── 3. DAFTAR PENUGASAN & LAPORAN PETUGAS SOSIALISASI PELANGGAN ────────
        $socializationVisits = [];
        $visitSummary = [
            'total' => 0,
            'scheduled' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'rescheduled' => 0,
            'my_tasks' => 0,
        ];

        if ($hasVisitsTable) {
            $vQuery = CustomerSocializationVisit::with([
                'customer.areaModel',
                'customer.package',
                'customer.ont.odp',
                'area',
                'odp',
                'officers',
                'creator',
                'completedByUser',
            ]);

            if (!empty($targetAreaIds)) {
                $vQuery->where(function ($q) use ($targetAreaIds) {
                    $q->whereIn('area_id', $targetAreaIds)
                      ->orWhereHas('customer', fn ($cq) => $cq->whereIn('area_id', $targetAreaIds));
                });
            }

            if ($visitStatus && $visitStatus !== 'all') {
                $vQuery->where('status', $visitStatus);
            }

            if ($search !== '') {
                $vQuery->where(function ($q) use ($search) {
                    $q->where('visit_number', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhere('met_with_name', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%"))
                      ->orWhereHas('officers', fn ($oq) => $oq->where('name', 'like', "%{$search}%"));
                });
            }

            $allVisits = $vQuery->orderByDesc('scheduled_date')->orderByDesc('id')->limit(300)->get();
            $currentUserId = $user ? (int) $user->id : 0;

            foreach ($allVisits as $v) {
                $officerIds = $v->officers->pluck('id')->map(fn ($id) => (int) $id)->toArray();
                $officerNames = $v->officers->pluck('name')->join(', ') ?: 'Petugas Sosialisasi';
                $isMyTask = in_array($currentUserId, $officerIds, true);

                if ($myTasksOnly && !$isMyTask) {
                    continue;
                }

                $cust = $v->customer;
                $socializationVisits[] = [
                    'id' => $v->id,
                    'visit_number' => $v->visit_number,
                    'customer_id' => $v->customer_id,
                    'customer_code' => $cust?->customer_code ?? '-',
                    'customer_name' => $cust?->name ?? 'Pelanggan',
                    'customer_phone' => $cust?->phone ?? '-',
                    'customer_address' => $cust?->address ?? '-',
                    'customer_lat' => $cust?->latitude,
                    'customer_lng' => $cust?->longitude,
                    'area_id' => $v->area_id ?: $cust?->area_id,
                    'area_name' => $v->area?->name ?: ($cust?->areaModel?->name ?: ($cust?->area ?: '-')),
                    'odp_name' => $v->odp?->name ?: ($cust?->ont?->odp?->name ?? '-'),
                    'purpose_category' => $v->purpose_category,
                    'purpose_label' => $v->purpose_label,
                    'title' => $v->title,
                    'issue_summary' => $v->issue_summary,
                    'admin_notes' => $v->admin_notes,
                    'scheduled_date' => $v->scheduled_date ? Carbon::parse($v->scheduled_date)->toDateString() : null,
                    'scheduled_date_formatted' => $v->scheduled_date ? Carbon::parse($v->scheduled_date)->locale('id')->isoFormat('ddd, D MMM Y') : '-',
                    'scheduled_time' => $v->scheduled_time ? substr((string) $v->scheduled_time, 0, 5) : null,
                    'priority' => $v->priority,
                    'status' => $v->status,
                    'is_done' => $v->status === 'completed',
                    'is_in_progress' => $v->status === 'in_progress',
                    'start_time' => $v->start_time ? substr((string) $v->start_time, 0, 5) : null,
                    'end_time' => $v->end_time ? substr((string) $v->end_time, 0, 5) : null,
                    'officer_ids' => $officerIds,
                    'officers_str' => $officerNames,
                    'is_my_task' => $isMyTask,
                    'met_with_name' => $v->met_with_name,
                    'met_with_relation' => $v->met_with_relation,
                    'customer_response' => $v->customer_response,
                    'response_label' => $v->response_label,
                    'socialization_points' => $v->socialization_points ?? [],
                    'report_notes' => $v->report_notes,
                    'follow_up_action' => $v->follow_up_action,
                    'photo_visit_urls' => $v->photo_visit_urls,
                    'latitude' => $v->latitude,
                    'longitude' => $v->longitude,
                    'completed_by_name' => $v->completedByUser?->name,
                    'completed_at' => $v->completed_at ? Carbon::parse($v->completed_at)->format('d M Y H:i') : null,
                ];
            }

            $visitSummary = [
                'total' => count($socializationVisits),
                'scheduled' => collect($socializationVisits)->where('status', 'scheduled')->count(),
                'in_progress' => collect($socializationVisits)->where('status', 'in_progress')->count(),
                'completed' => collect($socializationVisits)->where('status', 'completed')->count(),
                'rescheduled' => collect($socializationVisits)->where('status', 'rescheduled')->count(),
                'my_tasks' => collect($socializationVisits)->where('is_my_task', true)->count(),
            ];
        }

        // ── 4. DAFTAR PETUGAS SOSIALISASI & TEKNISI ───────────────────────────
        $rawUsers = User::forAssignment()->with('roles')->orderBy('name')->get(['id', 'name', 'phone', 'area_id', 'role']);
        $employees = Employee::select('id', 'name', 'position')->get()->keyBy(fn ($e) => mb_strtolower(trim((string) $e->name)));

        $availableOfficers = $rawUsers->map(function ($u) use ($employees) {
            $emp = $employees->get(mb_strtolower(trim((string) $u->name)));
            $pos = $emp?->position ?: ($u->role ?: 'Petugas');
            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'area_id' => $u->area_id,
                'role_label' => ucwords((string) $pos),
            ];
        })->values();

        // Ringkasan KPI Global
        $kpiSummary = [
            'chronic_customers_critical' => collect($chronicCustomers)->where('severity', 'critical')->count(),
            'chronic_customers_warning' => collect($chronicCustomers)->where('severity', 'warning')->count(),
            'total_customers_with_issues' => count($chronicCustomers),
            'unvisited_repeat_customers' => collect($chronicCustomers)->where('total_tickets', '>=', 2)->where('socialization_status', 'unvisited')->count(),
            'chronic_paths_critical' => collect($chronicPaths)->where('severity', 'critical')->count(),
            'total_paths_with_issues' => count($chronicPaths),
            'visits_completed' => $visitSummary['completed'],
            'visits_pending' => $visitSummary['scheduled'] + $visitSummary['in_progress'] + $visitSummary['rescheduled'],
        ];

        // Lightweight customer options for manual socialization visit creation
        $customerOptionsQuery = Customer::select('id', 'customer_code', 'name', 'phone', 'address', 'area_id')
            ->whereIn('status', ['active', 'installed', 'isolated'])
            ->orderBy('name')
            ->limit(600);
        if (!empty($targetAreaIds)) {
            $customerOptionsQuery->whereIn('area_id', $targetAreaIds);
        }
        $customerOptions = $customerOptionsQuery->get();

        // Daftar Material & Order Toko Area untuk Persiapan Material Pemeliharaan Jalur
        $materials = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->with('stocks')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock', 'price_per_unit', 'category']);

        $materialTransactions = fn () => RestockStockService::getRestockTransactions();

        return Inertia::render('Operational/ChronicMonitoring', [
            'chronicCustomers' => $chronicCustomers,
            'chronicPaths' => $chronicPaths,
            'socializationVisits' => $socializationVisits,
            'visitSummary' => $visitSummary,
            'kpiSummary' => $kpiSummary,
            'areas' => $areas,
            'availableOfficers' => $availableOfficers,
            'customerOptions' => $customerOptions,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'filters' => [
                'period' => $period,
                'area_id' => $areaId ? (int) $areaId : null,
                'min_tickets' => $minTickets,
                'search' => $search,
                'visit_status' => $visitStatus,
                'my_tasks' => $myTasksOnly,
            ],
            'canManage' => true,
        ]);
    }

    /**
     * Jadwalkan Petugas Kunjungan Sosialisasi ke Rumah Pelanggan (Satuan atau Massal per ODP)
     */
    public function storeVisit(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id',
            'purpose_category' => 'required|string|max:60',
            'title' => 'required|string|max:255',
            'issue_summary' => 'nullable|string',
            'admin_notes' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:20',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'officer_ids' => 'required|array|min:1',
            'officer_ids.*' => 'exists:users,id',
            'send_wa_officer' => 'nullable|boolean',
            'send_wa_customer' => 'nullable|boolean',
        ], [
            'officer_ids.required' => 'Pilih minimal 1 petugas untuk mendatangi rumah pelanggan.',
            'scheduled_date.required' => 'Tanggal kunjungan sosialisasi wajib diisi.',
        ]);

        $targetCustomerIds = [];
        if (!empty($validated['customer_ids'])) {
            $targetCustomerIds = array_unique(array_map('intval', $validated['customer_ids']));
        } elseif (!empty($validated['customer_id'])) {
            $targetCustomerIds = [(int) $validated['customer_id']];
        }

        if (empty($targetCustomerIds)) {
            return back()->with('error', 'Pilih pelanggan yang akan dikunjungi terlebih dahulu.');
        }

        try {
            DB::beginTransaction();

            $createdVisits = [];
            $customers = Customer::with(['ont.odp'])->whereIn('id', $targetCustomerIds)->get();

            foreach ($customers as $customer) {
                $visit = CustomerSocializationVisit::create([
                    'visit_number' => CustomerSocializationVisit::generateVisitNumber(),
                    'customer_id' => $customer->id,
                    'area_id' => $customer->area_id ?: $customer->ont?->odp?->area_id,
                    'odp_id' => $customer->ont?->odp_id,
                    'purpose_category' => $validated['purpose_category'],
                    'title' => $validated['title'],
                    'issue_summary' => $validated['issue_summary'] ?? null,
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'scheduled_date' => $validated['scheduled_date'],
                    'scheduled_time' => $validated['scheduled_time'] ?? '09:00',
                    'priority' => $validated['priority'] ?? 'normal',
                    'status' => 'scheduled',
                    'created_by' => auth()->id(),
                ]);

                $visit->officers()->sync($validated['officer_ids']);
                $createdVisits[] = $visit->load(['customer', 'officers', 'odp', 'area']);
            }

            DB::commit();

            // Kirim WA ke Petugas jika dicentang
            if ($request->boolean('send_wa_officer', true)) {
                foreach ($createdVisits as $v) {
                    $this->sendOfficerWhatsAppBriefing($v);
                }
            }

            // Kirim WA Pemberitahuan Kunjungan Silaturahmi/Sosialisasi ke Pelanggan jika dicentang
            if ($request->boolean('send_wa_customer', false)) {
                foreach ($createdVisits as $v) {
                    $this->sendCustomerWhatsAppGreeting($v);
                }
            }

            $count = count($createdVisits);
            return back()->with('success', "Berhasil menjadwalkan petugas kunjungan sosialisasi untuk {$count} pelanggan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[ChronicMonitoringController::storeVisit] ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan jadwal sosialisasi: ' . $e->getMessage());
        }
    }

    /**
     * Simpan / Update Laporan Hasil Kunjungan Sosialisasi dari Rumah Pelanggan
     */
    public function updateVisitReport(Request $request, CustomerSocializationVisit $visit)
    {
        if ($request->has('socialization_points') && is_string($request->input('socialization_points'))) {
            $decoded = json_decode($request->input('socialization_points'), true);
            if (is_array($decoded)) {
                $request->merge(['socialization_points' => $decoded]);
            }
        }

        $validated = $request->validate([
            'status' => 'required|string|in:scheduled,in_progress,completed,rescheduled,cancelled',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|string|max:20',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'met_with_name' => 'nullable|string|max:150',
            'met_with_relation' => 'nullable|string|max:100',
            'customer_response' => 'nullable|string|max:60',
            'socialization_points' => 'nullable|array',
            'report_notes' => 'nullable|string',
            'follow_up_action' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'officer_ids' => 'nullable|array',
            'officer_ids.*' => 'exists:users,id',
            'photo_visit' => 'nullable|image|max:25600',
        ]);

        try {
            $now = Carbon::now();
            $updateData = [
                'status' => $validated['status'],
            ];

            if (!empty($validated['scheduled_date'])) {
                $updateData['scheduled_date'] = $validated['scheduled_date'];
            }
            if (array_key_exists('scheduled_time', $validated) && $validated['scheduled_time'] !== null) {
                $updateData['scheduled_time'] = $validated['scheduled_time'];
            }
            if (array_key_exists('met_with_name', $validated)) {
                $updateData['met_with_name'] = $validated['met_with_name'];
            }
            if (array_key_exists('met_with_relation', $validated)) {
                $updateData['met_with_relation'] = $validated['met_with_relation'];
            }
            if (array_key_exists('customer_response', $validated)) {
                $updateData['customer_response'] = $validated['customer_response'];
            }
            if (array_key_exists('socialization_points', $validated)) {
                $updateData['socialization_points'] = $validated['socialization_points'];
            }
            if (array_key_exists('report_notes', $validated)) {
                $updateData['report_notes'] = $validated['report_notes'];
            }
            if (array_key_exists('follow_up_action', $validated)) {
                $updateData['follow_up_action'] = $validated['follow_up_action'];
            }
            if (!empty($validated['latitude'])) {
                $updateData['latitude'] = $validated['latitude'];
            }
            if (!empty($validated['longitude'])) {
                $updateData['longitude'] = $validated['longitude'];
            }

            if ($validated['status'] === 'in_progress' && empty($visit->start_time)) {
                $updateData['start_time'] = $validated['start_time'] ?? $now->format('H:i');
            } elseif ($validated['status'] === 'completed') {
                $updateData['start_time'] = $validated['start_time'] ?: ($visit->start_time ?: $now->format('H:i'));
                $updateData['end_time'] = $validated['end_time'] ?: $now->format('H:i');
                $updateData['completed_by'] = auth()->id();
                $updateData['completed_at'] = $now;
            }

            if ($request->hasFile('photo_visit')) {
                $photoPath = ImageCompressionService::compressAndStore(
                    $request->file('photo_visit'),
                    'socialization_visits',
                    'public'
                );
                $updateData['photo_visit'] = $photoPath;
            }

            $visit->update($updateData);

            if (!empty($validated['officer_ids'])) {
                $visit->officers()->sync($validated['officer_ids']);
            }

            return back()->with('success', 'Laporan kunjungan sosialisasi pelanggan berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('[ChronicMonitoringController::updateVisitReport] ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui laporan sosialisasi: ' . $e->getMessage());
        }
    }

    /**
     * Kirim Ulang Instruksi WhatsApp ke Petugas Sosialisasi
     */
    public function notifyOfficerWa(CustomerSocializationVisit $visit)
    {
        $visit->load(['customer', 'officers', 'odp', 'area']);
        $sent = $this->sendOfficerWhatsAppBriefing($visit);

        if ($sent > 0) {
            return back()->with('success', "Instruksi kunjungan sosialisasi berhasil dikirim via WhatsApp ke {$sent} petugas.");
        }

        return back()->with('error', 'Tidak dapat mengirim WhatsApp (pastikan nomor HP petugas terdaftar dan WA Gateway aktif).');
    }

    /**
     * Hapus Jadwal Sosialisasi
     */
    public function destroyVisit(CustomerSocializationVisit $visit)
    {
        $visit->delete();
        return back()->with('success', 'Data kunjungan sosialisasi berhasil dihapus.');
    }

    private function sendOfficerWhatsAppBriefing(CustomerSocializationVisit $visit): int
    {
        $sentCount = 0;
        try {
            $wa = app(WhatsAppGatewayService::class);
            $cust = $visit->customer;
            if (!$cust) {
                return 0;
            }

            $dateStr = $visit->scheduled_date ? Carbon::parse($visit->scheduled_date)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $timeStr = $visit->scheduled_time ? substr((string) $visit->scheduled_time, 0, 5) : '09:00';
            $mapsLink = ($cust->latitude && $cust->longitude)
                ? "https://www.google.com/maps?q={$cust->latitude},{$cust->longitude}"
                : '-';

            $msg = "🤝 *PENUGASAN KUNJUNGAN SOSIALISASI PELANGGAN*\n"
                . "No. Tugas: *{$visit->visit_number}*\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "👤 *Pelanggan:* {$cust->name} ({$cust->customer_code})\n"
                . "📞 *No. HP/WA:* {$cust->phone}\n"
                . "📍 *Alamat:* {$cust->address}\n"
                . "🗺️ *Titik Lokasi:* {$mapsLink}\n"
                . "🔌 *Jalur ODP:* " . ($visit->odp?->name ?? '-') . "\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "📅 *Jadwal Kunjungan:* {$dateStr} (Pukul {$timeStr})\n"
                . "🎯 *Tujuan:* {$visit->purpose_label}\n"
                . ($visit->issue_summary ? "📊 *Riwayat Gangguan:* {$visit->issue_summary}\n" : '')
                . ($visit->admin_notes ? "📝 *Pesan / Instruksi:* {$visit->admin_notes}\n" : '')
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Mohon mendatangi rumah pelanggan dengan sopan & ramah untuk bersilaturahmi, memberikan penjelasan/sosialisasi, memastikan koneksi sudah nyaman, serta mengisi laporan & foto dokumentasi di menu *Monitoring Rawan Gangguan & Sosialisasi*. Terima kasih! 🙏";

            foreach ($visit->officers as $officer) {
                if (!empty($officer->phone)) {
                    $res = $wa->sendMessage($officer->phone, $msg);
                    if (!empty($res['success'])) {
                        $sentCount++;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[ChronicMonitoringController::sendOfficerWhatsAppBriefing] ' . $e->getMessage());
        }

        return $sentCount;
    }

    private function sendCustomerWhatsAppGreeting(CustomerSocializationVisit $visit): bool
    {
        try {
            $cust = $visit->customer;
            if (!$cust || empty($cust->phone)) {
                return false;
            }

            $wa = app(WhatsAppGatewayService::class);
            $dateStr = $visit->scheduled_date ? Carbon::parse($visit->scheduled_date)->locale('id')->isoFormat('dddd, D MMMM Y') : '-';
            $officersStr = $visit->officers->pluck('name')->join(', ') ?: 'Tim Layanan Pelanggan';

            $msg = "Halo Bapak/Ibu *{$cust->name}* 🙏,\n\n"
                . "Demi menjaga dan meningkatkan kualitas pelayanan internet kami di tempat Bapak/Ibu, kami menjadwalkan petugas kami (*{$officersStr}*) untuk berkunjung bersilaturahmi & melakukan pengecekan/sosialisasi layanan ke rumah Bapak/Ibu pada:\n\n"
                . "📅 *Hari/Tanggal:* {$dateStr}\n"
                . "⏰ *Estimasi Waktu:* " . ($visit->scheduled_time ? substr((string) $visit->scheduled_time, 0, 5) . ' WIB' : 'Jam Kerja') . "\n\n"
                . "Terima kasih atas kepercayaan Bapak/Ibu menggunakan layanan kami. 🙏";

            $res = $wa->sendMessage($cust->phone, $msg);
            return !empty($res['success']);
        } catch (\Throwable $e) {
            Log::warning('[ChronicMonitoringController::sendCustomerWhatsAppGreeting] ' . $e->getMessage());
            return false;
        }
    }
}

