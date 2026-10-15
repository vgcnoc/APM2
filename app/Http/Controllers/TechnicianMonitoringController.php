<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Area;
use App\Models\Ticket;
use App\Models\PgkTicket;
use App\Models\PgjTicket;
use App\Models\PgsTicket;
use App\Models\CbpRequest;
use App\Models\TechnicianSchedule;
use App\Models\Survey;
use App\Models\InfrastructureWorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TechnicianMonitoringController extends Controller
{
    /**
     * Halaman Utama Monitoring Kinerja Teknisi (Gangguan, Instalasi, Leaderboard)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isTeknisiOnly = ($user->role === 'teknisi' && !$user->hasAnyRole(['admin', 'Super Admin', 'noc', 'kepala teknisi', 'Dispatcher', 'manager', 'cs']));

        // Filter Periode Tanggal
        $period = $request->input('period', 'this_month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');
        $areaId = $request->input('area_id');
        $technicianFilterId = $request->input('technician_id');
        $activeTab = $request->input('tab', 'gangguan');

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period, $customStart, $customEnd);

        // Ambil Daftar Area
        $areas = Area::orderBy('name')->get();

        // Ambil Daftar Teknisi
        $technicianQuery = User::where(function ($q) {
            $q->whereHas('roles', function ($r) {
                $r->whereIn('name', ['teknisi', 'kepala teknisi', 'maintenance', 'teknisi lapangan']);
            })->orWhereIn('role', ['teknisi', 'kepala teknisi', 'maintenance']);
        });

        if ($areaId) {
            $technicianQuery->where('area_id', $areaId);
        }

        if ($isTeknisiOnly) {
            $technicianQuery->where('id', $user->id);
        } elseif ($technicianFilterId) {
            $technicianQuery->where('id', $technicianFilterId);
        }

        $technicians = $technicianQuery->orderBy('name')->get();

        // Ambil Data Statistik Per Teknisi
        $technicianStats = [];
        $globalTrouble = [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'pgk_count' => 0,
            'pgj_count' => 0,
            'pgs_count' => 0,
            'cbp_count' => 0,
            'general_count' => 0,
            'avg_resolution_hours' => 0,
            'total_resolution_hours' => 0,
            'resolved_with_hours_count' => 0,
        ];

        $globalInstall = [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'surveys_total' => 0,
            'surveys_feasible' => 0,
            'rollout_total' => 0,
            'rollout_completed' => 0,
        ];

        foreach ($technicians as $tech) {
            $tStats = $this->calculateTechnicianPerformance($tech, $startDate, $endDate, $areaId);
            $technicianStats[] = $tStats;

            // Akumulasi Global Trouble
            $globalTrouble['total'] += $tStats['trouble']['total'];
            $globalTrouble['completed'] += $tStats['trouble']['completed'];
            $globalTrouble['in_progress'] += $tStats['trouble']['in_progress'];
            $globalTrouble['pending'] += $tStats['trouble']['pending'];
            $globalTrouble['pgk_count'] += $tStats['trouble']['breakdown']['pgk'];
            $globalTrouble['pgj_count'] += $tStats['trouble']['breakdown']['pgj'];
            $globalTrouble['pgs_count'] += $tStats['trouble']['breakdown']['pgs'];
            $globalTrouble['cbp_count'] += $tStats['trouble']['breakdown']['cbp'];
            $globalTrouble['general_count'] += $tStats['trouble']['breakdown']['general'];
            $globalTrouble['total_resolution_hours'] += ($tStats['trouble']['avg_resolution_hours'] * $tStats['trouble']['completed']);
            $globalTrouble['resolved_with_hours_count'] += $tStats['trouble']['completed'];

            // Akumulasi Global Instalasi
            $globalInstall['total'] += $tStats['installation']['total'];
            $globalInstall['completed'] += $tStats['installation']['completed'];
            $globalInstall['in_progress'] += $tStats['installation']['in_progress'];
            $globalInstall['pending'] += $tStats['installation']['pending'];
            $globalInstall['surveys_total'] += $tStats['installation']['surveys_total'];
            $globalInstall['surveys_feasible'] += $tStats['installation']['surveys_feasible'];
            $globalInstall['rollout_total'] += $tStats['installation']['rollout_total'];
            $globalInstall['rollout_completed'] += $tStats['installation']['rollout_completed'];
        }

        // Hitung Rata-rata Resolusi Global
        $globalTrouble['avg_resolution_hours'] = $globalTrouble['resolved_with_hours_count'] > 0
            ? round($globalTrouble['total_resolution_hours'] / $globalTrouble['resolved_with_hours_count'], 1)
            : 0;

        $globalTrouble['completion_rate'] = $globalTrouble['total'] > 0
            ? round(($globalTrouble['completed'] / $globalTrouble['total']) * 100)
            : 0;

        $globalInstall['completion_rate'] = $globalInstall['total'] > 0
            ? round(($globalInstall['completed'] / $globalInstall['total']) * 100)
            : 0;

        // Sorting Leaderboard by Total Score
        usort($technicianStats, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Berikan Ranking
        foreach ($technicianStats as $idx => &$stat) {
            $stat['rank'] = $idx + 1;
        }
        unset($stat);

        // Top Performers
        $topTroubleTech = collect($technicianStats)->sortByDesc('trouble.completed')->first();
        $topInstallTech = collect($technicianStats)->sortByDesc('installation.completed')->first();
        $topOverallTech = collect($technicianStats)->sortByDesc('score')->first();

        // Chart Data: Top 8 Perbandingan Teknisi (Gangguan vs Instalasi)
        $chartCategories = [];
        $chartSeriesTrouble = [];
        $chartSeriesInstall = [];

        foreach (array_slice($technicianStats, 0, 8) as $stat) {
            $chartCategories[] = explode(' ', $stat['technician']['name'])[0];
            $chartSeriesTrouble[] = $stat['trouble']['completed'];
            $chartSeriesInstall[] = $stat['installation']['completed'];
        }

        // Donut Chart: Komposisi Gangguan
        $troubleCompositionSeries = [
            $globalTrouble['pgk_count'],
            $globalTrouble['pgj_count'],
            $globalTrouble['pgs_count'],
            $globalTrouble['cbp_count'],
            $globalTrouble['general_count'],
        ];

        return Inertia::render('Monitoring/TechnicianMonitoring', [
            'technicians' => $technicians->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
            'areas' => $areas,
            'technicianStats' => $technicianStats,
            'globalTrouble' => $globalTrouble,
            'globalInstall' => $globalInstall,
            'topPerformers' => [
                'trouble' => $topTroubleTech,
                'installation' => $topInstallTech,
                'overall' => $topOverallTech,
            ],
            'chartData' => [
                'categories' => $chartCategories,
                'troubleSeries' => $chartSeriesTrouble,
                'installSeries' => $chartSeriesInstall,
                'troubleComposition' => $troubleCompositionSeries,
            ],
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'period_label' => $periodLabel,
                'area_id' => $areaId ? (int)$areaId : '',
                'technician_id' => $technicianFilterId ? (int)$technicianFilterId : '',
                'tab' => $activeTab,
            ],
        ]);
    }

    /**
     * Hitung Detail Performa Satu Teknisi
     */
    private function calculateTechnicianPerformance(User $tech, Carbon $startDate, Carbon $endDate, $areaId = null): array
    {
        $techId = $tech->id;

        // 1. TIKET PGK (Komplain Pelanggan)
        $pgkQuery = PgkTicket::where(function ($q) use ($techId) {
            $q->where('lead_technician_id', $techId)
              ->orWhere('resolved_by', $techId)
              ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
        })->whereBetween('created_at', [$startDate, $endDate]);

        if ($areaId) $pgkQuery->where('area_id', $areaId);

        $pgkTotal = (clone $pgkQuery)->count();
        $pgkCompleted = (clone $pgkQuery)->whereIn('status', ['resolved', 'closed'])->count();
        $pgkInProgress = (clone $pgkQuery)->where('status', 'in_progress')->count();
        $pgkPending = (clone $pgkQuery)->whereIn('status', ['admin_handling', 'scheduled'])->count();

        // 2. TIKET PGJ (Gangguan Jalur)
        $pgjQuery = PgjTicket::where(function ($q) use ($techId) {
            $q->where('lead_technician_id', $techId)
              ->orWhere('resolved_by', $techId)
              ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
        })->whereBetween('created_at', [$startDate, $endDate]);

        if ($areaId) $pgjQuery->where('area_id', $areaId);

        $pgjTotal = (clone $pgjQuery)->count();
        $pgjCompleted = (clone $pgjQuery)->whereIn('status', ['resolved', 'closed'])->count();
        $pgjInProgress = (clone $pgjQuery)->where('status', 'in_progress')->count();
        $pgjPending = (clone $pgjQuery)->whereIn('status', ['reported', 'scheduled'])->count();

        // 3. TIKET PGS (Penanganan Sigap)
        $pgsQuery = PgsTicket::where(function ($q) use ($techId) {
            $q->where('lead_technician_id', $techId)
              ->orWhere('resolved_by', $techId)
              ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
        })->whereBetween('created_at', [$startDate, $endDate]);

        if ($areaId) $pgsQuery->where('area_id', $areaId);

        $pgsTotal = (clone $pgsQuery)->count();
        $pgsCompleted = (clone $pgsQuery)->whereIn('status', ['resolved', 'closed'])->count();
        $pgsInProgress = (clone $pgsQuery)->where('status', 'in_progress')->count();
        $pgsPending = (clone $pgsQuery)->whereIn('status', ['reported', 'scheduled'])->count();

        // 4. CBP (Cabut Perangkat)
        $cbpQuery = CbpRequest::where(function ($q) use ($techId) {
            $q->where('assigned_to', $techId)
              ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
        })->whereBetween('created_at', [$startDate, $endDate]);

        $cbpTotal = (clone $cbpQuery)->count();
        $cbpCompleted = (clone $cbpQuery)->where('status', 'completed')->count();
        $cbpInProgress = (clone $cbpQuery)->where('status', 'in_progress')->count();
        $cbpPending = (clone $cbpQuery)->whereIn('status', ['pending', 'scheduled'])->count();

        // 5. TIKET UMUM (Ticket table)
        $ticketQuery = Ticket::where(function ($q) use ($techId) {
            $q->where('assigned_to', $techId)
              ->orWhereHas('schedules', fn($r) => $r->where('technician_id', $techId));
        })->whereBetween('created_at', [$startDate, $endDate]);

        $ticketTotal = (clone $ticketQuery)->count();
        $ticketCompleted = (clone $ticketQuery)->whereIn('status', ['resolved', 'closed'])->count();
        $ticketInProgress = (clone $ticketQuery)->where('status', 'in_progress')->count();
        $ticketPending = (clone $ticketQuery)->whereIn('status', ['open', 'assigned'])->count();

        // Rata-rata Resolusi Tiket (Estimasi jam kerja penyelesaian)
        $resolvedPgkTimes = (clone $pgkQuery)->whereNotNull('resolved_at')->whereNotNull('created_at')->get(['created_at', 'resolved_at']);
        $totalHours = 0;
        $resolvedCount = 0;

        foreach ($resolvedPgkTimes as $t) {
            $diffHours = Carbon::parse($t->created_at)->diffInHours(Carbon::parse($t->resolved_at));
            if ($diffHours <= 168) { // max 1 minggu filter outlier
                $totalHours += $diffHours;
                $resolvedCount++;
            }
        }

        $avgResolutionHours = $resolvedCount > 0 ? round($totalHours / $resolvedCount, 1) : 2.5;

        // Total Gangguan
        $troubleTotal = $pgkTotal + $pgjTotal + $pgsTotal + $cbpTotal + $ticketTotal;
        $troubleCompleted = $pgkCompleted + $pgjCompleted + $pgsCompleted + $cbpCompleted + $ticketCompleted;
        $troubleInProgress = $pgkInProgress + $pgjInProgress + $pgsInProgress + $cbpInProgress + $ticketInProgress;
        $troublePending = $pgkPending + $pgjPending + $pgsPending + $cbpPending + $ticketPending;
        $troubleRate = $troubleTotal > 0 ? round(($troubleCompleted / $troubleTotal) * 100) : 0;

        // ─────────────────────────────────────────────────────────────
        // 6. INSTALASI / PEMASANGAN BARU (PSB)
        // ─────────────────────────────────────────────────────────────
        $installScheduleQuery = TechnicianSchedule::where('technician_id', $techId)
            ->where('type', 'installation')
            ->whereBetween('scheduled_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['customer.ont']);

        if ($areaId) {
            $installScheduleQuery->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
        }

        $installSchedules = $installScheduleQuery->get();
        $installTotal = $installSchedules->count();
        $installCompleted = 0;
        $installInProgress = 0;
        $installPending = 0;

        foreach ($installSchedules as $sch) {
            $cust = $sch->customer;
            if ($sch->status === 'completed' || ($cust && in_array($cust->status, ['active', 'activation'])) || ($cust && $cust->ont && $cust->ont->photo_ont)) {
                $installCompleted++;
            } elseif ($sch->status === 'in_progress' || ($cust && $cust->status === 'installed')) {
                $installInProgress++;
            } else {
                $installPending++;
            }
        }

        // 7. SURVEY LOKASI (PSB Survey)
        $surveyQuery = Survey::where('surveyor_id', $techId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($areaId) {
            $surveyQuery->whereHas('customer', fn($c) => $c->where('area_id', $areaId));
        }

        $surveysTotal = (clone $surveyQuery)->count();
        $surveysFeasible = (clone $surveyQuery)->where('feasibility', 'feasible')->count();

        // 8. ROLLOUT ODC & ODP (Work Orders)
        $rolloutQuery = InfrastructureWorkOrder::where(function ($q) use ($techId) {
            $q->where('team_leader_id', $techId)
              ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
        })->whereBetween('created_at', [$startDate, $endDate]);

        $rolloutTotal = (clone $rolloutQuery)->count();
        $rolloutCompleted = (clone $rolloutQuery)->where('status', 'completed')->count();

        // Total Instalasi
        $installTotalAll = $installTotal;
        $installRate = $installTotalAll > 0 ? round(($installCompleted / $installTotalAll) * 100) : 0;

        // ─────────────────────────────────────────────────────────────
        // SKOR PRODUKTIVITAS & STATUS KERJA HARI INI
        // ─────────────────────────────────────────────────────────────
        // Bobot: Pasang Selesai = 3 poin, Gangguan Selesai = 2 poin, Survey = 1 poin, Rollout = 3 poin
        $score = ($installCompleted * 3) + ($troubleCompleted * 2) + ($surveysFeasible * 1) + ($rolloutCompleted * 3);

        // Status Kerja Hari Ini
        $todayStr = Carbon::today()->format('Y-m-d');
        $activeJobsToday = TechnicianSchedule::where('technician_id', $techId)
            ->where('scheduled_date', $todayStr)
            ->count();

        $activeTroublesToday = PgkTicket::where('scheduled_date', $todayStr)
            ->where(function ($q) use ($techId) {
                $q->where('lead_technician_id', $techId)
                  ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
            })->count();

        $statusToday = 'Standby';
        if (($activeJobsToday + $activeTroublesToday) > 0) {
            $statusToday = "Aktif (" . ($activeJobsToday + $activeTroublesToday) . " Penugasan Hari Ini)";
        }

        return [
            'technician' => [
                'id' => $tech->id,
                'name' => $tech->name,
                'phone' => $tech->phone,
                'email' => $tech->email,
                'area_name' => $tech->area ? $tech->area->name : 'Semua Area',
                'status_today' => $statusToday,
            ],
            'trouble' => [
                'total' => $troubleTotal,
                'completed' => $troubleCompleted,
                'in_progress' => $troubleInProgress,
                'pending' => $troublePending,
                'completion_rate' => $troubleRate,
                'avg_resolution_hours' => $avgResolutionHours,
                'breakdown' => [
                    'pgk' => $pgkTotal,
                    'pgj' => $pgjTotal,
                    'pgs' => $pgsTotal,
                    'cbp' => $cbpTotal,
                    'general' => $ticketTotal,
                ],
            ],
            'installation' => [
                'total' => $installTotalAll,
                'completed' => $installCompleted,
                'in_progress' => $installInProgress,
                'pending' => $installPending,
                'completion_rate' => $installRate,
                'surveys_total' => $surveysTotal,
                'surveys_feasible' => $surveysFeasible,
                'rollout_total' => $rolloutTotal,
                'rollout_completed' => $rolloutCompleted,
            ],
            'total_jobs_completed' => ($troubleCompleted + $installCompleted + $surveysTotal + $rolloutCompleted),
            'score' => $score,
        ];
    }

    /**
     * Detail Drilldown Pekerjaan Spesifik Teknisi (JSON Modal)
     */
    public function technicianDetail(Request $request, User $technician)
    {
        $period = $request->input('period', 'this_month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');
        $areaId = $request->input('area_id');
        $tab = $request->input('tab', 'gangguan');

        [$startDate, $endDate] = $this->resolveDateRange($period, $customStart, $customEnd);
        $techId = $technician->id;

        if ($tab === 'gangguan') {
            // Ambil Rincian Tiket PGK, PGJ, PGS, CBP, Tiket Umum
            $pgkTickets = PgkTicket::with(['customer', 'area'])
                ->where(function ($q) use ($techId) {
                    $q->where('lead_technician_id', $techId)
                      ->orWhere('resolved_by', $techId)
                      ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->latest()
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'type' => 'PGK (Komplain Pelanggan)',
                    'number' => $t->ticket_number,
                    'title' => $t->reported_issue,
                    'customer_name' => $t->customer ? $t->customer->name : ($t->complainant_name ?: '-'),
                    'customer_phone' => $t->customer ? $t->customer->phone : ($t->complainant_phone ?: '-'),
                    'area' => $t->area ? $t->area->name : '-',
                    'status' => $t->status,
                    'date' => $t->scheduled_date ? $t->scheduled_date->format('Y-m-d') : $t->created_at->format('Y-m-d'),
                    'resolved_at' => $t->resolved_at ? $t->resolved_at->format('Y-m-d H:i') : null,
                ]);

            $pgjTickets = PgjTicket::with(['area', 'odc', 'odp'])
                ->where(function ($q) use ($techId) {
                    $q->where('lead_technician_id', $techId)
                      ->orWhere('resolved_by', $techId)
                      ->orWhereHas('technicians', fn($r) => $r->where('users.id', $techId));
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->latest()
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'type' => 'PGJ (Gangguan Jalur)',
                    'number' => $t->ticket_number,
                    'title' => $t->issue_description,
                    'customer_name' => 'Infrastruktur Jalur / ' . ($t->odc ? $t->odc->name : ($t->odp ? $t->odp->name : '-')),
                    'customer_phone' => '-',
                    'area' => $t->area ? $t->area->name : '-',
                    'status' => $t->status,
                    'date' => $t->scheduled_date ? $t->scheduled_date->format('Y-m-d') : $t->created_at->format('Y-m-d'),
                    'resolved_at' => $t->resolved_at ? $t->resolved_at->format('Y-m-d H:i') : null,
                ]);

            $items = $pgkTickets->concat($pgjTickets);
        } else {
            // Ambil Rincian Jadwal Pemasangan (PSB)
            $items = TechnicianSchedule::with(['customer.package', 'customer.area'])
                ->where('technician_id', $techId)
                ->where('type', 'installation')
                ->whereBetween('scheduled_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->latest('scheduled_date')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'type' => 'PSB Instalasi',
                    'number' => $s->customer ? $s->customer->customer_code : ('SCH-' . $s->id),
                    'title' => 'Pemasangan PSB: ' . ($s->customer ? $s->customer->name : '-'),
                    'customer_name' => $s->customer ? $s->customer->name : '-',
                    'customer_phone' => $s->customer ? $s->customer->phone : '-',
                    'package' => $s->customer && $s->customer->package ? $s->customer->package->name : '-',
                    'area' => $s->customer && $s->customer->area ? $s->customer->area->name : ($s->customer ? $s->customer->area : '-'),
                    'address' => $s->customer ? $s->customer->address : '-',
                    'status' => $s->status === 'completed' || ($s->customer && in_array($s->customer->status, ['active', 'activation'])) ? 'Selesai' : ($s->status === 'in_progress' ? 'Proses' : 'Terjadwal'),
                    'date' => $s->scheduled_date ? $s->scheduled_date->format('Y-m-d') : '-',
                    'time' => $s->scheduled_time ? $s->scheduled_time->format('H:i') : '10:00',
                    'notes' => $s->notes,
                ]);
        }

        return response()->json([
            'success' => true,
            'technician' => [
                'id' => $technician->id,
                'name' => $technician->name,
                'phone' => $technician->phone,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Helper Penentu Rentang Tanggal
     */
    private function resolveDateRange(string $period, $customStart = null, $customEnd = null): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $label = 'Hari Ini (' . $start->format('d M Y') . ')';
                break;
            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                $label = 'Kemarin (' . $start->format('d M Y') . ')';
                break;
            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                $label = 'Minggu Ini (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                break;
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                $label = 'Bulan Lalu (' . $start->format('F Y') . ')';
                break;
            case 'custom':
                $start = $customStart ? Carbon::parse($customStart)->startOfDay() : Carbon::now()->startOfMonth();
                $end = $customEnd ? Carbon::parse($customEnd)->endOfDay() : Carbon::now()->endOfDay();
                $label = $start->format('d M Y') . ' s/d ' . $end->format('d M Y');
                break;
            case 'this_month':
            default:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $label = 'Bulan Ini (' . $start->format('F Y') . ')';
                break;
        }

        return [$start, $end, $label];
    }
}
