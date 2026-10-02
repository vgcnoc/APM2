<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Odp;
use App\Models\Odc;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\Ticket;
use App\Models\AuditLog;
use App\Models\InternetPackage;
use App\Models\Area;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        if (auth()->check() && auth()->user()->hasRole('teknisi')) {
            return $this->teknisiDashboard();
        }

        // ── Statistik Pelanggan ──────────────────────────────
        $customerStats = [
            'total_active'     => Customer::where('status', 'active')->count(),
            'total_booking'    => Customer::where('status', 'booking')->count(),
            'total_survey'     => Customer::whereIn('status', ['survey'])->count(),
            'total_installing' => Customer::where('status', 'installing')->count(),
            'total_activation' => Customer::where('status', 'menunggu_aktivasi')->count(),
            'total_all'        => Customer::count(),
        ];

        // ── Statistik Infrastruktur ─────────────────────────
        $infraStats = [
            'olt_total'   => Olt::count(),
            'olt_active'  => Olt::where('status', 'active')->count(),
            'odc_total'   => Odc::count(),
            'odc_active'  => Odc::where('status', 'active')->count(),
            'odp_total'   => Odp::count(),
            'odp_active'  => Odp::where('status', '!=', 'full')->count(),
            'ont_total'   => Ont::count(),
            'ont_active'  => Ont::where('status', 'active')->count(),
        ];

        // ── Tiket ───────────────────────────────────────────
        $ticketStats = [
            'open'           => Ticket::where('status', 'open')->count(),
            'in_progress'    => Ticket::where('status', 'in_progress')->count(),
            'resolved_today' => Ticket::where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
        ];

        // ── Pendapatan ──────────────────────────────────────
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $monthlyRevenue = Invoice::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->where('status', 'paid')
            ->sum('amount');

        $totalRevenue = Invoice::where('status', 'paid')->sum('amount');

        // Pendapatan per bulan (6 bulan terakhir)
        $revenueChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $month = $date->month;
            $year  = $date->year;
            $total = Invoice::where('period_month', $month)
                ->where('period_year', $year)
                ->where('status', 'paid')
                ->sum('amount');

            $revenueChart[] = [
                'period_month' => $month,
                'period_year'  => $year,
                'total'        => (float) $total,
            ];
        }

        // Rata-rata bulanan & total transaksi
        $avgMonthly    = count($revenueChart) > 0 ? collect($revenueChart)->avg('total') : 0;
        $totalInvoices = Invoice::where('status', 'paid')->count();

        // Pelanggan baru bulan ini
        $newCustomersThisMonth = Customer::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        // ── Pipeline Pelanggan (5 terbaru) ──────────────────
        $recentCustomers = Customer::with('package')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'package'    => $c->package?->name ?? '-',
                'status'     => $c->status,
                'initial'    => strtoupper(substr($c->name, 0, 1)),
                'created_at' => $c->created_at->format('d M Y'),
            ]);

        // ── Tiket Terbaru (5) ───────────────────────────────
        $recentTickets = Ticket::with(['customer', 'assignee'])
            ->where('status', '!=', 'closed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'            => $t->id,
                'ticket_number' => $t->ticket_number,
                'customer_name' => $t->customer?->name,
                'category'      => $t->category,
                'priority'      => $t->priority,
                'status'        => $t->status,
                'assignee_name' => $t->assignee?->name,
                'created_at'    => $t->created_at->format('d M Y'),
            ]);

        // ── Aktivitas Terbaru ───────────────────────────────
        $recentActivities = AuditLog::orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'user_name'   => $a->user_name,
                'action'      => $a->action,
                'entity_type' => class_basename($a->entity_type ?? ''),
                'notes'       => $a->notes,
                'role'        => $a->role,
                'created_at'  => $a->created_at->format('H:i'),
                'created_date' => $a->created_at->format('d M Y H:i'),
            ]);

        // ── Distribusi Paket Pelanggan ──────────────────────
        $packageDistribution = Customer::where('status', 'active')
            ->select('package_id', DB::raw('COUNT(*) as total'))
            ->groupBy('package_id')
            ->with('package:id,name')
            ->get()
            ->map(fn($item) => [
                'name'  => $item->package?->name ?? 'Tanpa Paket',
                'total' => $item->total,
            ])
            ->sortByDesc('total')
            ->values();

        // ── Top 5 Area Pelanggan ────────────────────────────
        $topAreas = Customer::select('area_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('area_id')
            ->groupBy('area_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $area = Area::find($item->area_id);
                return [
                    'name'  => $area?->name ?? 'Unknown',
                    'total' => $item->total,
                ];
            });

        $totalCustomersInAreas = $topAreas->sum('total');

        return Inertia::render('Dashboard', [
            'customerStats'        => $customerStats,
            'infraStats'           => $infraStats,
            'ticketStats'          => $ticketStats,
            'monthlyRevenue'       => (float) $monthlyRevenue,
            'totalRevenue'         => (float) $totalRevenue,
            'avgMonthlyRevenue'    => (float) $avgMonthly,
            'totalInvoices'        => $totalInvoices,
            'newCustomersThisMonth' => $newCustomersThisMonth,
            'revenueChart'         => $revenueChart,
            'recentCustomers'      => $recentCustomers,
            'recentTickets'        => $recentTickets,
            'recentActivities'     => $recentActivities,
            'packageDistribution'  => $packageDistribution,
            'topAreas'             => $topAreas,
            'totalCustomersInAreas' => $totalCustomersInAreas,
        ]);
    }

    private function teknisiDashboard(): Response
    {
        $userId = auth()->id();

        // ── Statistik Tugas ─────────────────────────────────
        $stats = [
            'survey_assigned' => Customer::where('status', 'survey')
                ->whereHas('technicianSchedules', function($q) use ($userId) {
                    $q->where('type', 'survey')->where('technician_id', $userId)->where('status', 'scheduled');
                })->count(),
            'installation_assigned' => Customer::where('status', 'installing')
                ->whereHas('technicianSchedules', function($q) use ($userId) {
                    $q->where('type', 'installation')->where('technician_id', $userId)->where('status', 'scheduled');
                })->count(),
            'tickets_assigned' => Ticket::where('assignee_id', $userId)
                ->where('status', '!=', 'closed')->count(),
        ];

        // ── Daftar Tugas Survey ──────────────────────────────
        $surveyTasks = Customer::where('status', 'survey')
            ->whereHas('technicianSchedules', function($q) use ($userId) {
                $q->where('type', 'survey')->where('technician_id', $userId)->where('status', 'scheduled');
            })
            ->with(['technicianSchedules' => function($q) use ($userId) {
                $q->where('type', 'survey')->where('technician_id', $userId)->where('status', 'scheduled');
            }])
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'address' => $c->address,
                'schedule_date' => $c->technicianSchedules->first()?->scheduled_date,
            ]);

        // ── Daftar Tugas Instalasi ───────────────────────────
        $installationTasks = Customer::where('status', 'installing')
            ->whereHas('technicianSchedules', function($q) use ($userId) {
                $q->where('type', 'installation')->where('technician_id', $userId)->where('status', 'scheduled');
            })
            ->with(['technicianSchedules' => function($q) use ($userId) {
                $q->where('type', 'installation')->where('technician_id', $userId)->where('status', 'scheduled');
            }])
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'address' => $c->address,
                'schedule_date' => $c->technicianSchedules->first()?->scheduled_date,
            ]);

        // ── Daftar Tiket Gangguan ────────────────────────────
        $ticketTasks = Ticket::where('assignee_id', $userId)
            ->where('status', '!=', 'closed')
            ->with('customer')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'customer_name' => $t->customer?->name,
                'priority' => $t->priority,
                'status' => $t->status,
                'category' => $t->category,
            ]);

        return Inertia::render('DashboardTeknisi', [
            'stats' => $stats,
            'surveyTasks' => $surveyTasks,
            'installationTasks' => $installationTasks,
            'ticketTasks' => $ticketTasks,
        ]);
    }
}
