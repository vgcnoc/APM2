<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Employee;
use App\Models\InfrastructureWorkOrder;
use App\Models\User;
use App\Services\DataScopeService;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RolloutProgressController extends Controller
{
    /**
     * Dashboard & Laporan Terpadu Progress Rollout Pembangunan ODC & ODP
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $areaId = $request->input('area_id');
        $customNote = $request->input('note', 'Tetap utamakan keselamatan kerja & standardisasi pemasangan! ⚡');

        // Parse Carbon Date & Format Bahasa Indonesia
        $carbonDate = Carbon::parse($date)->locale('id');
        $indonesianDayDate = $this->formatIndonesianDayDate($carbonDate);

        $user = Auth::user();
        $isGlobal = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->can('infrastructure_work_orders_view_all'));
        $accessibleAreaIds = (!$isGlobal && $user) ? $user->getAccessibleAreaIds() : null;

        $targetAreaIds = null;
        if ($accessibleAreaIds !== null) {
            if (empty($accessibleAreaIds)) {
                $targetAreaIds = [-1];
                $areaId = null;
            } else {
                if ($areaId) {
                    $requestedAreaId = (int) $areaId;
                    if (in_array($requestedAreaId, $accessibleAreaIds, true)) {
                        $targetAreaIds = [$requestedAreaId];
                    } else {
                        $targetAreaIds = $accessibleAreaIds;
                        $areaId = $accessibleAreaIds[0];
                    }
                } else {
                    $targetAreaIds = $accessibleAreaIds;
                    $areaId = $accessibleAreaIds[0];
                }
            }
        } else {
            if ($areaId) {
                $targetAreaIds = [(int) $areaId];
            }
        }

        $areas = DataScopeService::getScopedAreas($user, 'infrastructure_work_orders');
        $selectedArea = $areaId ? Area::find($areaId) : null;
        $areaLabel = $selectedArea ? $selectedArea->name : ($accessibleAreaIds !== null ? 'Wilayah Ditugaskan' : 'Semua Wilayah / Area');

        // Ambil Data Rollout
        $rolloutList = $this->getRolloutData($date, $targetAreaIds);

        // Kumpulkan teknisi rollout aktif
        $activeTechNames = [];
        foreach ($rolloutList as $item) {
            if (!empty($item['technicians'])) {
                foreach (explode(',', $item['technicians']) as $tName) {
                    $trimmed = trim($tName);
                    if ($trimmed && !in_array($trimmed, ['Belum Ditugaskan', 'Tim Rollout Lapangan', '-'])) {
                        $activeTechNames[] = $trimmed;
                    }
                }
            }
        }
        $activeTechNames = array_values(array_unique($activeTechNames));

        // Ringkasan KPI
        $summary = [
            'total' => count($rolloutList),
            'completed' => collect($rolloutList)->where('status', 'completed')->count(),
            'under_review' => collect($rolloutList)->where('status', 'under_review')->count(),
            'in_progress' => collect($rolloutList)->where('status', 'in_progress')->count(),
            'material_ready' => collect($rolloutList)->where('status', 'material_ready')->count(),
            'pending_material' => collect($rolloutList)->where('status', 'pending_material')->count(),
            'rejected' => collect($rolloutList)->where('status', 'rejected')->count(),
            'total_cable_meters' => (int) collect($rolloutList)->sum('cable_length_used'),
            'total_odp' => collect($rolloutList)->where('device_type', 'ODP')->count(),
            'total_odc' => collect($rolloutList)->where('device_type', 'ODC')->count(),
        ];

        // Format Teks WhatsApp Khusus Rollout
        $waText = $this->buildWhatsAppRolloutTemplate(
            $areaLabel,
            $indonesianDayDate,
            $activeTechNames,
            $rolloutList,
            $summary,
            $customNote
        );

        // Status Gateway WhatsApp
        $waGatewayStatus = false;
        try {
            $waService = app(WhatsAppGatewayService::class);
            $gwInfo = $waService->getStatus();
            $waGatewayStatus = !empty($gwInfo['connected']);
        } catch (\Throwable $e) {
            $waGatewayStatus = false;
        }

        return Inertia::render('Infrastructure/RolloutProgress', [
            'filters' => [
                'date' => $date,
                'area_id' => $areaId ? (int) $areaId : null,
                'note' => $customNote,
            ],
            'areas' => $areas,
            'indonesianDate' => $indonesianDayDate,
            'areaLabel' => $areaLabel,
            'rolloutList' => $rolloutList,
            'technicians' => User::forAssignment()->orderBy('name')->get(['id', 'name', 'phone', 'role']),
            'activeTechNames' => $activeTechNames,
            'summary' => $summary,
            'waText' => $waText,
            'waGatewayStatus' => $waGatewayStatus,
            'canViewAllAreas' => $isGlobal,
            'isAreaScoped' => !$isGlobal,
        ]);
    }

    /**
     * Ambil Data Work Order Rollout Pembangunan ODC & ODP
     */
    private function getRolloutData(string $date, string|array|null $areaId): array
    {
        $items = [];
        $areaIds = is_array($areaId) ? $areaId : ($areaId ? [$areaId] : []);
        if (in_array(-1, $areaIds, true)) {
            return [];
        }
        $isToday = ($date === Carbon::today()->toDateString());

        $query = InfrastructureWorkOrder::with([
            'area', 
            'olt', 
            'parentOdc', 
            'technicians', 
            'technician', 
            'creator', 
            'materials.material', 
            'opticalChecks'
        ])
        ->where(function ($q) use ($date, $isToday) {
            $q->whereDate('scheduled_date', $date)
              ->orWhereDate('updated_at', $date)
              ->orWhereDate('approved_at', $date);

            if ($isToday) {
                $q->orWhere(function ($sq) use ($date) {
                    $sq->whereIn('status', ['in_progress', 'material_ready', 'pending_material', 'under_review'])
                       ->where(function ($ssq) use ($date) {
                           $ssq->whereNull('scheduled_date')
                               ->orWhereDate('scheduled_date', '<=', $date);
                       });
                });
            }
        });

        if (!empty($areaIds)) {
            $query->whereIn('area_id', $areaIds);
        }

        foreach ($query->orderBy('id', 'desc')->get() as $wo) {
            $techNames = [];
            if ($wo->technician) {
                $techNames[] = $wo->technician->name;
            }
            if ($wo->technicians && $wo->technicians->isNotEmpty()) {
                foreach ($wo->technicians as $t) {
                    $techNames[] = $t->name;
                }
            }
            $techNames = array_values(array_unique(array_filter($techNames)));
            $techniciansStr = !empty($techNames) ? implode(', ', $techNames) : 'Tim Rollout Lapangan';

            $isDone = in_array($wo->status, ['completed', 'approved', 'commissioned'], true);
            $isInProgress = in_array($wo->status, ['in_progress', 'under_review'], true);
            $isPending = in_array($wo->status, ['pending_material', 'material_ready', 'draft'], true);

            switch ($wo->status) {
                case 'completed':
                case 'approved':
                case 'commissioned':
                    $statusLabel = '✅ Selesai & Aktif di GIS';
                    $statusClass = 'completed';
                    break;
                case 'under_review':
                    $statusLabel = '🔍 Review QC NOC';
                    $statusClass = 'under_review';
                    break;
                case 'in_progress':
                    $statusLabel = '🛠️ Sedang Dikerjakan';
                    $statusClass = 'in_progress';
                    break;
                case 'material_ready':
                    $statusLabel = '📦 Material Siap';
                    $statusClass = 'material_ready';
                    break;
                case 'pending_material':
                    $statusLabel = '⏳ Menunggu Material';
                    $statusClass = 'pending_material';
                    break;
                case 'rejected':
                    $statusLabel = '❌ Revisi / Ditolak';
                    $statusClass = 'rejected';
                    break;
                default:
                    $statusLabel = '⏳ ' . ucfirst((string) $wo->status);
                    $statusClass = 'pending';
                    break;
            }

            $schedDateStr = $wo->scheduled_date ? Carbon::parse($wo->scheduled_date)->toDateString() : null;

            $opmChecks = $wo->opticalChecks->map(function ($oc) {
                return [
                    'id' => $oc->id,
                    'port_number' => $oc->port_number,
                    'core_number' => $oc->core_number,
                    'measured_loss' => $oc->measured_loss,
                    'status' => $oc->status,
                    'passed' => (bool) $oc->passed,
                ];
            })->values();

            $isOdc = (str_contains($wo->wo_number, 'WO-ODC') || $wo->type === 'odc_deployment' || stripos($wo->target_name, 'ODC') !== false);
            $deviceCategory = $isOdc ? 'ODC' : 'ODP';
            $topology = $wo->device_type ?: 'Normal';

            $materialsList = $wo->materials->map(function ($m) {
                $requested = (float) ($m->requested_qty ?? 0);
                $used = (float) ($m->used_qty ?? 0);
                $returned = (float) ($m->returned_qty ?? 0);
                $qty = ($used > 0) ? $used : $requested;

                return [
                    'id' => $m->id,
                    'material_id' => $m->material_id,
                    'name' => $m->material?->name ?? 'Material Rollout',
                    'requested_qty' => $requested,
                    'used_qty' => $used,
                    'returned_qty' => $returned,
                    'quantity_requested' => $requested,
                    'quantity_approved' => $requested,
                    'quantity_used' => $used,
                    'quantity' => $qty,
                    'unit' => $m->unit ?: ($m->material?->unit ?? 'pcs'),
                ];
            })->values();

            $techIds = [];
            if ($wo->technician_id) {
                $techIds[] = (int) $wo->technician_id;
            }
            if ($wo->technicians) {
                foreach ($wo->technicians as $t) {
                    $techIds[] = (int) $t->id;
                }
            }
            $techIds = array_values(array_unique($techIds));

            $typeLabel = match ($wo->type) {
                'odp_deployment' => 'Pembangunan ODP Baru',
                'odc_deployment' => 'Pembangunan ODC Baru',
                'build' => 'Pembangunan Baru',
                'expand' => 'Ekspansi Kapasitas',
                'repair' => 'Perbaikan Fisik',
                default => ($wo->type ? ucwords(str_replace('_', ' ', (string) $wo->type)) : ($isOdc ? 'Pembangunan ODC' : 'Pembangunan ODP')),
            };

            $items[] = [
                'id' => $wo->id,
                'wo_number' => $wo->wo_number,
                'type' => $wo->type,
                'type_label' => $typeLabel,
                'device_category' => $deviceCategory,
                'device_type' => $deviceCategory,
                'device_topology' => $topology,
                'target_name' => $wo->target_name,
                'area_name' => $wo->area?->name ?? '-',
                'olt_name' => $wo->olt?->name ?? null,
                'pon_port' => $wo->pon_port,
                'parent_odc' => $wo->parentOdc?->name ?? null,
                'odc_splitter_port' => $wo->odc_splitter_port,
                'capacity' => $wo->capacity,
                'core_capacity' => $wo->core_capacity,
                'splitter_ratio' => $wo->splitter_ratio,
                'location' => $wo->location,
                'actual_latitude' => $wo->actual_latitude ? (float) $wo->actual_latitude : null,
                'actual_longitude' => $wo->actual_longitude ? (float) $wo->actual_longitude : null,
                'planned_latitude' => $wo->planned_latitude ? (float) $wo->planned_latitude : null,
                'planned_longitude' => $wo->planned_longitude ? (float) $wo->planned_longitude : null,
                'technicians' => $techniciansStr,
                'technician_ids' => $techIds,
                'scheduled_date' => $schedDateStr,
                'status' => $wo->status,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_pending' => $isPending,
                'start_point' => $wo->start_point,
                'end_point' => $wo->end_point,
                'cable_pull' => $wo->cable_pull,
                'cable_meter_start' => $wo->cable_meter_start,
                'cable_meter_end' => $wo->cable_meter_end,
                'cable_length_used' => $wo->cable_length_used,
                'photo_pole' => $wo->photo_pole ? Storage::url($wo->photo_pole) : null,
                'photo_splicing' => $wo->photo_splicing ? Storage::url($wo->photo_splicing) : null,
                'photo_opm' => $wo->photo_opm ? Storage::url($wo->photo_opm) : null,
                'photo_odp' => $wo->photo_odp ? Storage::url($wo->photo_odp) : null,
                'notes' => $wo->notes,
                'optical_checks' => $opmChecks,
                'materials' => $materialsList,
            ];
        }

        return $items;
    }

    /**
     * Susun Format Broadcast WhatsApp Khusus Rollout Pembangunan ODC & ODP
     */
    private function buildWhatsAppRolloutTemplate(
        string $areaLabel,
        string $indonesianDayDate,
        array $activeTechNames,
        array $rolloutList,
        array $summary,
        string $customNote
    ): string {
        $lines = [];

        // Header
        $lines[] = "⚡ *PROGRESS ROLLOUT PEMBANGUNAN ODC & ODP*";
        $lines[] = "🏢 *{$areaLabel}*";
        $lines[] = $indonesianDayDate;
        $lines[] = "";

        // Tim Petugas Rollout
        $techDutyStr = !empty($activeTechNames) ? implode(', ', $activeTechNames) : '-';
        $lines[] = "👷‍♂️ *TIM ROLLOUT LAPANGAN :*";
        $lines[] = "• Petugas Bertugas: {$techDutyStr}";
        $lines[] = "";

        // Rincian WO Rollout
        if (!empty($rolloutList)) {
            $lines[] = "🏗️ *DAFTAR PEKERJAAN ROLLOUT :*";
            $idx = 1;
            foreach ($rolloutList as $r) {
                $lines[] = "";
                $targetDisplay = trim($r['target_name'] ?: ($r['device_type'] . ' ' . $r['wo_number']));
                $topologySuffix = (!empty($r['device_topology']) && strtolower($r['device_topology']) !== 'normal') ? " - {$r['device_topology']}" : '';
                $lines[] = "{$idx}. WO: *{$r['wo_number']}* ({$targetDisplay}{$topologySuffix})";
                $lines[] = "   Tipe    : {$r['type_label']}";
                $lines[] = "   Lokasi  : " . ($r['location'] ?: $r['area_name']);
                
                if (!empty($r['olt_name']) || !empty($r['parent_odc'])) {
                    $uplink = [];
                    if ($r['olt_name']) $uplink[] = "OLT: {$r['olt_name']}" . ($r['pon_port'] ? " (PON {$r['pon_port']})" : '');
                    if ($r['parent_odc']) $uplink[] = "ODC: {$r['parent_odc']}" . ($r['odc_splitter_port'] ? " (Port {$r['odc_splitter_port']})" : '');
                    $lines[] = "   Uplink  : " . implode(' | ', $uplink);
                }

                $lines[] = "   Kapasitas: {$r['capacity']} Port" . ($r['core_capacity'] ? " ({$r['core_capacity']} Core / Rasio {$r['splitter_ratio']})" : '');

                if ($r['cable_length_used'] || $r['cable_pull']) {
                    $cableParts = [];
                    if ($r['cable_length_used']) {
                        $cableParts[] = "{$r['cable_length_used']} Meter";
                    }
                    if ($r['cable_pull']) {
                        $cableParts[] = ($r['cable_pull'] === 'Individu' ? 'Individu' : "Tipe {$r['cable_pull']}");
                    }
                    $lines[] = "   Tarik Kabel: " . implode(' / ', $cableParts);
                }

                if (!empty($r['optical_checks']) && count($r['optical_checks']) > 0) {
                    $opmStr = [];
                    foreach ($r['optical_checks'] as $oc) {
                        if ($oc['measured_loss']) {
                            $opmStr[] = "P{$oc['port_number']}: {$oc['measured_loss']} dBm";
                        }
                    }
                    if (!empty($opmStr)) {
                        $lines[] = "   Redaman OPM: " . implode(', ', array_slice($opmStr, 0, 8)) . (count($opmStr) > 8 ? ' ...' : '');
                    }
                }

                if (!empty($r['materials']) && count($r['materials']) > 0) {
                    $matParts = [];
                    foreach ($r['materials'] as $m) {
                        $q = ($m['used_qty'] > 0) ? $m['used_qty'] : (($m['requested_qty'] > 0) ? $m['requested_qty'] : ($m['quantity'] ?? 0));
                        if ($q <= 0 && isset($m['quantity_requested']) && $m['quantity_requested'] > 0) {
                            $q = $m['quantity_requested'];
                        }
                        $qFormatted = (fmod($q, 1) == 0.0) ? (int)$q : rtrim(rtrim(number_format($q, 2, '.', ''), '0'), '.');
                        $matParts[] = "{$qFormatted} {$m['unit']} {$m['name']}";
                    }
                    if (!empty($matParts)) {
                        $lines[] = "   Material: " . implode(', ', $matParts);
                    }
                }

                $lines[] = "   Teknisi : {$r['technicians']}";
                $lines[] = "   Status  : {$r['status_label']}";
                $idx++;
            }
            $lines[] = "";
        } else {
            $lines[] = "🌴 *Nihil Pekerjaan Rollout Hari Ini*";
            $lines[] = "Tidak ada jadwal pembangunan ODC atau ODP pada tanggal ini.";
            $lines[] = "";
        }

        // Ringkasan
        $lines[] = "📊 *RINGKASAN STATUS ROLLOUT :*";
        $lines[] = "* Total Pembangunan : {$summary['total']} ({$summary['total_odp']} ODP, {$summary['total_odc']} ODC)";
        $lines[] = "* Selesai & Aktif GIS: {$summary['completed']}";
        $lines[] = "* Review QC NOC      : {$summary['under_review']}";
        $lines[] = "* Sedang Dikerjakan  : {$summary['in_progress']}";
        $lines[] = "* Material Siap      : {$summary['material_ready']}";
        $lines[] = "* Menunggu Material  : {$summary['pending_material']}";
        if ($summary['rejected'] > 0) {
            $lines[] = "* Revisi / Ditolak   : {$summary['rejected']}";
        }
        if ($summary['total_cable_meters'] > 0) {
            $lines[] = "* Total Tarik Kabel  : {$summary['total_cable_meters']} Meter";
        }
        $lines[] = "";

        // Catatan
        $lines[] = "📝 *NOTE*";
        $lines[] = $customNote ?: "Tetap utamakan keselamatan kerja & standardisasi pemasangan! ⚡";

        return implode("\n", $lines);
    }

    /**
     * Kirim Pesan Langsung via WhatsApp Gateway
     */
    public function sendWa(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ], [
            'phone.required' => 'Nomor tujuan WhatsApp atau ID Grup wajib diisi.',
            'message.required' => 'Isi pesan WhatsApp tidak boleh kosong.',
        ]);

        try {
            $waService = app(WhatsAppGatewayService::class);
            $rawPhone = trim($validated['phone']);

            $rawPhone = preg_replace('/[^\d@\.a-zA-Z_-]/', '', $rawPhone);
            if (str_starts_with($rawPhone, '08')) {
                $rawPhone = '628' . substr($rawPhone, 2);
            }

            $res = $waService->sendMessage($rawPhone, $validated['message']);

            if (!empty($res['success']) || (!empty($res['status']) && $res['status'] === 'success')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan progress rollout berhasil dikirim ke WhatsApp ' . $rawPhone . '!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Gagal mengirim pesan melalui WhatsApp Gateway.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[RolloutProgress::sendWa] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghubungi WA Gateway: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper Format Tanggal Bahasa Indonesia
     */
    private function formatIndonesianDayDate(Carbon $carbonDate): string
    {
        $days = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $months = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember',
        ];

        $dayName = $days[$carbonDate->englishDayOfWeek] ?? $carbonDate->englishDayOfWeek;
        $monthName = $months[$carbonDate->englishMonth] ?? $carbonDate->englishMonth;

        return "{$dayName}, {$carbonDate->day} {$monthName} {$carbonDate->year}";
    }
}

