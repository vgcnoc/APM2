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
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\SpecialAttendance;
use App\Models\SpecialTeam;
use App\Models\SpecialTeamMember;
use App\Models\WorkSchedule;
use App\Models\Shift;
use App\Models\Voucher;
use App\Models\Radius\RadAcct;
use App\Models\Radius\Nas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->check() && auth()->user()->role === 'teknisi') {
            return $this->teknisiDashboard($request);
        }

        if (auth()->check() && (auth()->user()->role === 'sales' || auth()->user()->role === 'affiliate')) {
            return redirect()->route('affiliate.dashboard');
        }

        if (auth()->check() && (
            auth()->user()->role === 'reseller' || 
            auth()->user()->role === 'customer' || 
            (method_exists(auth()->user(), 'hasRole') && (auth()->user()->hasRole('customer') || auth()->user()->hasRole('reseller')))
        )) {
            $user = auth()->user();
            $customer = $user->customer ?: \App\Models\Customer::where('user_id', $user->id)->first();
            $reseller = $user->reseller ?: \App\Models\Reseller::where('user_id', $user->id)->first();
            
            if ($customer && $customer->package_id) {
                return redirect()->route('customer-area.dashboard');
            }
            
            if ($reseller) {
                return redirect()->route('client-area.dashboard');
            }
            
            if ($customer) {
                return redirect()->route('customer-area.dashboard');
            }

            // Fallback to role-based routing if no record found
            if ($user->role === 'reseller' || (method_exists($user, 'hasRole') && $user->hasRole('reseller'))) {
                return redirect()->route('client-area.dashboard');
            }
            if ($user->role === 'customer' || (method_exists($user, 'hasRole') && $user->hasRole('customer'))) {
                return redirect()->route('customer-area.dashboard');
            }
        }

        $user = auth()->user();

        // ── 0. Hak Akses & Filter Wilayah (Auto, Cerdas, Sempurna) ──
        $canViewAllAreas = $user && (
            $user->hasRole('admin') || 
            $user->role === 'admin' || 
            $user->hasRole('super admin') || 
            $user->role === 'super admin' || 
            $user->can('dashboard_view_all') || 
            $user->can('customers_all_view_all')
        );

        $canFilterArea = $user && (
            $canViewAllAreas ||
            $user->can('dashboard_filter_area') ||
            $user->can('dashboard_filter_period')
        );

        $userAccessibleAreaIds = $user ? $user->getAccessibleAreaIds() : [];

        // Daftar Wilayah yang Diizinkan untuk Dipilih
        if ($canViewAllAreas) {
            $availableAreas = Area::orderBy('name')->get(['id', 'name', 'address', 'radius_meters']);
        } else {
            $availableAreas = Area::whereIn('id', $userAccessibleAreaIds)->orderBy('name')->get(['id', 'name', 'address', 'radius_meters']);
        }

        // Penentuan Wilayah Terpilih
        $selectedAreaId = 'all';
        if ($request->filled('area_id') && $canFilterArea) {
            $reqArea = $request->input('area_id');
            if ($reqArea === 'all') {
                $selectedAreaId = 'all';
            } else {
                $reqAreaId = (int) $reqArea;
                if ($canViewAllAreas || in_array($reqAreaId, $userAccessibleAreaIds)) {
                    $selectedAreaId = $reqAreaId;
                } else {
                    $selectedAreaId = $canViewAllAreas ? 'all' : (count($userAccessibleAreaIds) > 0 ? $userAccessibleAreaIds[0] : 'all');
                }
            }
        } else {
            // Otomatis & Cerdas: Jika user hanya memiliki 1 area yang ditugaskan atau tidak memiliki izin filter, default langsung ke areanya
            if ((!$canViewAllAreas || !$canFilterArea) && count($userAccessibleAreaIds) === 1) {
                $selectedAreaId = $userAccessibleAreaIds[0];
            } else {
                $selectedAreaId = 'all';
            }
        }

        // Closures untuk scoping query per area
        $applyCustomerArea = function ($query) use ($selectedAreaId, $canViewAllAreas, $userAccessibleAreaIds) {
            if (is_numeric($selectedAreaId)) {
                $query->where('area_id', $selectedAreaId);
            } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
                $query->whereIn('area_id', $userAccessibleAreaIds);
            }
            return $query;
        };

        $applyInvoiceArea = function ($query) use ($selectedAreaId, $canViewAllAreas, $userAccessibleAreaIds) {
            if (is_numeric($selectedAreaId)) {
                $query->whereHas('customer', fn($q) => $q->where('area_id', $selectedAreaId));
            } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
                $query->whereHas('customer', fn($q) => $q->whereIn('area_id', $userAccessibleAreaIds));
            }
            return $query;
        };

        $applyTicketArea = function ($query) use ($selectedAreaId, $canViewAllAreas, $userAccessibleAreaIds) {
            if (is_numeric($selectedAreaId)) {
                $query->whereHas('customer', fn($q) => $q->where('area_id', $selectedAreaId));
            } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
                $query->whereHas('customer', fn($q) => $q->whereIn('area_id', $userAccessibleAreaIds));
            }
            return $query;
        };

        $applyInfraArea = function ($query) use ($selectedAreaId, $canViewAllAreas, $userAccessibleAreaIds) {
            if (is_numeric($selectedAreaId)) {
                $query->where('area_id', $selectedAreaId);
            } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
                $query->whereIn('area_id', $userAccessibleAreaIds);
            }
            return $query;
        };

        // ── 1. Statistik Pelanggan ──────────────────────────
        $custBase = Customer::query();
        $applyCustomerArea($custBase);

        $customerStats = [
            'total_active'     => (clone $custBase)->where('status', 'active')->whereNotNull('package_id')->count(),
            'total_booking'    => (clone $custBase)->where('status', 'booking')->count(),
            'total_survey'     => (clone $custBase)->whereIn('status', ['survey'])->count(),
            'total_installing' => (clone $custBase)->where('status', 'installing')->count(),
            'total_activation' => (clone $custBase)->where('status', 'menunggu_aktivasi')->count(),
            'total_all'        => (clone $custBase)->count(),
        ];

        // ── 2. Statistik Infrastruktur ─────────────────────
        $oltQuery = $applyInfraArea(Olt::query());
        $odcQuery = $applyInfraArea(Odc::query());
        $odpQuery = $applyInfraArea(Odp::query());
        $ontQuery = $applyInfraArea(Ont::query());

        $infraStats = [
            'olt_total'   => (clone $oltQuery)->count(),
            'olt_active'  => (clone $oltQuery)->where('status', 'active')->count(),
            'odc_total'   => (clone $odcQuery)->count(),
            'odc_active'  => (clone $odcQuery)->where('status', 'active')->count(),
            'odp_total'   => (clone $odpQuery)->count(),
            'odp_active'  => (clone $odpQuery)->where('status', '!=', 'full')->count(),
            'ont_total'   => (clone $ontQuery)->count(),
            'ont_active'  => (clone $ontQuery)->where('status', 'active')->count(),
        ];

        // ── 3. Tiket ───────────────────────────────────────
        $ticketBase = Ticket::query();
        $applyTicketArea($ticketBase);

        $ticketStats = [
            'open'           => (clone $ticketBase)->where('status', 'open')->count(),
            'in_progress'    => (clone $ticketBase)->where('status', 'in_progress')->count(),
            'resolved_today' => (clone $ticketBase)->where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
        ];

        // ── 4. Pendapatan & Invoice ────────────────────────
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $invoiceBase = Invoice::query();
        $applyInvoiceArea($invoiceBase);

        $invoiceStats = [
            'paid_this_month'   => (clone $invoiceBase)->where('period_month', $currentMonth)->where('period_year', $currentYear)->where('status', 'paid')->count(),
            'unpaid_this_month' => (clone $invoiceBase)->where('period_month', $currentMonth)->where('period_year', $currentYear)->whereIn('status', ['unpaid', 'overdue'])->count(),
        ];

        // ── Reseller Voucher Belum Bayar ────────────────────
        $resellerUnpaidInvoices = (clone $invoiceBase)->where('is_reseller_balance', true)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->with(['payments' => function ($q) {
                $q->where('status', 'verified');
            }])
            ->get();

        $resellerUnpaidCount = $resellerUnpaidInvoices->count();
        $resellerUnpaidAmount = (float) $resellerUnpaidInvoices->sum(function ($inv) {
            $paid = $inv->payments->sum('amount');
            return max(0, (float) $inv->amount - (float) $paid);
        });

        $monthlyRevenue = (clone $invoiceBase)->where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->where('status', 'paid')
            ->sum('amount');

        $totalRevenue = (clone $invoiceBase)->where('status', 'paid')->sum('amount');

        // Pendapatan per bulan (6 bulan terakhir)
        $revenueChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $month = $date->month;
            $year  = $date->year;
            $total = (clone $invoiceBase)->where('period_month', $month)
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
        $totalInvoices = (clone $invoiceBase)->where('status', 'paid')->count();

        // Pelanggan baru bulan ini
        $newCustomersThisMonth = (clone $custBase)->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        // ── 5. Pipeline Pelanggan (Terbaru) ─────────────────
        $recentCustomers = (clone $custBase)->with(['package', 'areaModel'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'package'    => $c->package?->name ?? '-',
                'area_name'  => $c->areaModel?->name ?? ($c->area ?: '-'),
                'status'     => $c->status,
                'initial'    => strtoupper(substr($c->name, 0, 1)),
                'created_at' => $c->created_at->format('d M Y'),
            ]);

        // ── 6. Tiket Terbaru (5) ────────────────────────────
        $recentTickets = (clone $ticketBase)->with(['customer', 'assignee'])
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

        // ── 7. Aktivitas Terbaru ────────────────────────────
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

        // ── 8. Distribusi Paket Pelanggan ───────────────────
        $packageDistribution = (clone $custBase)->where('status', 'active')
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

        // ── 9. Top 5 Area vs Detail Wilayah Terpilih ────────
        $topAreasQuery = Customer::select('area_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('area_id');
        if (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
            $topAreasQuery->whereIn('area_id', $userAccessibleAreaIds);
        }
        $topAreas = $topAreasQuery->groupBy('area_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $area = Area::find($item->area_id);
                return [
                    'id'    => $item->area_id,
                    'name'  => $area?->name ?? 'Unknown',
                    'total' => $item->total,
                ];
            });

        $totalCustomersInAreas = $topAreas->sum('total');

        // Detail Wilayah Spesifik jika ada area yang dipilih
        $selectedAreaDetail = null;
        if (is_numeric($selectedAreaId)) {
            $curArea = Area::find($selectedAreaId);
            if ($curArea) {
                $curAreaCust = Customer::where('area_id', $curArea->id);
                $selectedAreaDetail = [
                    'id'               => $curArea->id,
                    'name'             => $curArea->name,
                    'address'          => $curArea->address ?: 'Wilayah Operasional',
                    'radius_meters'    => $curArea->radius_meters ?? 0,
                    'total_customers'  => (clone $curAreaCust)->count(),
                    'active_customers' => (clone $curAreaCust)->where('status', 'active')->count(),
                    'total_odp'        => Odp::where('area_id', $curArea->id)->count(),
                    'total_odc'        => Odc::where('area_id', $curArea->id)->count(),
                    'total_olt'        => Olt::where('area_id', $curArea->id)->count(),
                    'total_ont'        => Ont::where('area_id', $curArea->id)->count(),
                ];
            }
        }

        // ── 10. Data Presensi Widget ────────────────────────
        $attendanceData = $this->getAttendanceDashboardData($request->input('employee_id'));

        return Inertia::render('Dashboard', [
            'customerStats'        => $customerStats,
            'infraStats'           => $infraStats,
            'ticketStats'          => $ticketStats,
            'invoiceStats'         => $invoiceStats,
            'resellerUnpaidCount'  => $resellerUnpaidCount,
            'resellerUnpaidAmount' => $resellerUnpaidAmount,
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
            'attendanceData'       => $attendanceData,
            'selectedAreaId'       => $selectedAreaId,
            'availableAreas'       => $availableAreas,
            'selectedAreaDetail'   => $selectedAreaDetail,
            'canViewAllAreas'      => $canViewAllAreas,
            'canFilterArea'        => $canFilterArea,
            'realtimeStats'        => $this->getRealtimeData($user, $selectedAreaId),
        ]);
    }

    private function teknisiDashboard(Request $request): Response
    {
        $userId = auth()->id();
        $attendanceData = $this->getAttendanceDashboardData($request->input('employee_id'));

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
            'tickets_assigned' => Ticket::where('assigned_to', $userId)
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
                'schedule_date' => $c->technicianSchedules->first()?->scheduled_date 
                    ? \Carbon\Carbon::parse($c->technicianSchedules->first()->scheduled_date)->format('Y-m-d') 
                    : null,
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
                'schedule_date' => $c->technicianSchedules->first()?->scheduled_date 
                    ? \Carbon\Carbon::parse($c->technicianSchedules->first()->scheduled_date)->format('Y-m-d') 
                    : null,
            ]);

        // ── Daftar Tiket Gangguan ────────────────────────────
        $ticketTasks = Ticket::where('assigned_to', $userId)
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
            'attendanceData' => $attendanceData,
        ]);
    }

    /**
     * Ambil data presensi hari ini untuk widget dashboard (Reguler atau Tim Khusus)
     */
    protected function getAttendanceDashboardData(?int $employeeId = null): array
    {
        $user = auth()->user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $currentEmployee = null;
        if ($employeeId && $user && in_array($user->role, ['superadmin', 'admin', 'hrd'])) {
            $currentEmployee = Employee::with(['area', 'shift'])->find($employeeId);
        }

        if (!$currentEmployee && $user) {
            $currentEmployee = Employee::with(['area', 'shift'])->where('email', $user->email)->first();
        }

        if (!$currentEmployee && $user && in_array($user->role, ['superadmin', 'admin', 'hrd'])) {
            $currentEmployee = Employee::with(['area', 'shift'])->first();
        }

        // Cek apakah karyawan ini terdaftar di grup Tim Khusus aktif
        $specialTeamMember = null;
        $specialTeam = null;
        $isSpecialTeam = false;

        if ($currentEmployee) {
            $specialTeamMember = SpecialTeamMember::with('team')
                ->where('employee_id', $currentEmployee->id)
                ->whereHas('team', function ($q) {
                    $q->where('is_active', true);
                })
                ->first();

            if ($specialTeamMember && $specialTeamMember->team) {
                $isSpecialTeam = true;
                $specialTeam = $specialTeamMember->team;
            }
        }

        $todaySchedule = null;
        $shiftToday = null;
        $todayAttendance = null;
        $officeLocation = null;

        if ($currentEmployee) {
            if ($isSpecialTeam) {
                // Absensi Tim Khusus
                $todayAttendance = SpecialAttendance::where('special_team_id', $specialTeam->id)
                    ->where('employee_id', $currentEmployee->id)
                    ->where('date', $today)
                    ->first();

                // Shift khusus dari aturan Tim Khusus
                $startTimeStr = $specialTeam->start_time ? substr($specialTeam->start_time, 0, 5) : '08:30';
                $endTimeStr = $specialTeam->end_time ? substr($specialTeam->end_time, 0, 5) : '18:00';

                $shiftToday = (object) [
                    'id' => null,
                    'name' => $specialTeam->name,
                    'start_time' => $specialTeam->start_time,
                    'end_time' => $specialTeam->end_time,
                    'formatted_time' => "{$startTimeStr} - {$endTimeStr} WIB",
                    'color' => 'indigo',
                    'tolerance_minutes' => (int) $specialTeam->tolerance_minutes,
                    'without_radius' => (bool) $specialTeam->without_radius,
                    'is_special' => true,
                ];

                $officeLocation = [
                    'id' => null,
                    'name' => $specialTeam->name . ' (Penugasan Lapangan)',
                    'address' => 'Absensi Bebas Radius Kantor',
                    'latitude' => null,
                    'longitude' => null,
                    'radius_meters' => 999999,
                    'without_radius' => true,
                ];
            } else {
                // Absensi Reguler Standar
                $todaySchedule = WorkSchedule::with('shift')
                    ->where('employee_id', $currentEmployee->id)
                    ->where('date', $today)
                    ->first();

                $shiftToday = $todaySchedule?->shift ?: $currentEmployee->shift;

                $todayAttendance = Attendance::with('shift')
                    ->where('employee_id', $currentEmployee->id)
                    ->where('date', $today)
                    ->first();

                if ($currentEmployee->area) {
                    $officeLocation = [
                        'id' => $currentEmployee->area->id,
                        'name' => $currentEmployee->area->name,
                        'address' => $currentEmployee->area->address,
                        'latitude' => $currentEmployee->area->latitude ? (float) $currentEmployee->area->latitude : null,
                        'longitude' => $currentEmployee->area->longitude ? (float) $currentEmployee->area->longitude : null,
                        'radius_meters' => $currentEmployee->area->radius_meters ? (int) $currentEmployee->area->radius_meters : 200,
                        'without_radius' => false,
                    ];
                }
            }
        }

        $allShifts = Shift::where('is_active', true)->orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']);

        $allEmployees = [];
        if ($user && in_array($user->role, ['superadmin', 'admin', 'hrd'])) {
            $allEmployees = Employee::with(['area', 'shift', 'specialTeams'])
                ->orderBy('name')
                ->get(['id', 'name', 'position', 'branch', 'area_id', 'shift_id', 'email', 'iak_number'])
                ->map(function ($emp) {
                    $activeSpecialTeam = $emp->specialTeams->where('is_active', true)->first();
                    $empArr = $emp->toArray();
                    $empArr['is_special_team'] = !empty($activeSpecialTeam);
                    $empArr['special_team_name'] = $activeSpecialTeam ? $activeSpecialTeam->name : null;
                    return $empArr;
                });
        }

        if ($isSpecialTeam) {
            $totalEmployees = SpecialTeamMember::where('special_team_id', $specialTeam->id)->count();
            $presentToday = SpecialAttendance::where('special_team_id', $specialTeam->id)->where('date', $today)->whereNotNull('clock_in')->count();
            $lateToday = SpecialAttendance::where('special_team_id', $specialTeam->id)->where('date', $today)->where('status', 'late')->count();
            $completedToday = SpecialAttendance::where('special_team_id', $specialTeam->id)->where('date', $today)->whereNotNull('clock_out')->count();
        } else {
            $totalEmployees = Employee::count();
            $presentToday = Attendance::where('date', $today)->whereNotNull('clock_in')->count();
            $lateToday = Attendance::where('date', $today)->where('status', 'late')->count();
            $completedToday = Attendance::where('date', $today)->whereNotNull('clock_out')->count();
        }

        return [
            'todayDateStr' => $now->translatedFormat('l, d F Y'),
            'todayIso' => $today,
            'isSpecialTeam' => $isSpecialTeam,
            'specialTeam' => $specialTeam ? [
                'id' => $specialTeam->id,
                'name' => $specialTeam->name,
                'code' => $specialTeam->code,
                'start_time' => $specialTeam->start_time,
                'end_time' => $specialTeam->end_time,
                'tolerance_minutes' => (int) $specialTeam->tolerance_minutes,
                'without_radius' => (bool) $specialTeam->without_radius,
                'late_deduction_amount' => (float) $specialTeam->late_deduction_amount,
                'no_out_deduction_amount' => (float) $specialTeam->no_out_deduction_amount,
            ] : null,
            'specialTeamMember' => $specialTeamMember ? [
                'role' => $specialTeamMember->role,
                'is_leader' => (bool) $specialTeamMember->is_leader,
            ] : null,
            'currentEmployee' => $currentEmployee,
            'allEmployees' => $allEmployees,
            'allShifts' => $allShifts,
            'todaySchedule' => $todaySchedule,
            'shiftToday' => $shiftToday,
            'todayAttendance' => $todayAttendance,
            'officeLocation' => $officeLocation,
            'attendanceSummary' => [
                'total_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'late_today' => $lateToday,
                'completed_today' => $completedToday,
            ],
        ];
    }

    /**
     * Halaman Menu Dashboard Realtime
     */
    public function realtime(Request $request)
    {
        $user = auth()->user();

        // Hak akses view dashboard realtime
        if ($user && !$user->hasRole('admin') && !$user->hasRole('super admin') && $user->role !== 'admin' && $user->role !== 'super admin') {
            if (!$user->can('menu_dashboard_realtime') && !$user->can('dashboard_card_realtime')) {
                abort(403, 'Anda tidak memiliki hak akses untuk membuka Dashboard Realtime.');
            }
        }

        $canViewAllAreas = $user && (
            $user->hasRole('admin') || 
            $user->role === 'admin' || 
            $user->hasRole('super admin') || 
            $user->role === 'super admin' || 
            $user->can('dashboard_view_all') || 
            $user->can('customers_all_view_all')
        );

        $userAccessibleAreaIds = $user ? $user->getAccessibleAreaIds() : [];

        if ($canViewAllAreas) {
            $availableAreas = Area::orderBy('name')->get(['id', 'name', 'address', 'radius_meters']);
        } else {
            $availableAreas = Area::whereIn('id', $userAccessibleAreaIds)->orderBy('name')->get(['id', 'name', 'address', 'radius_meters']);
        }

        $selectedAreaId = 'all';
        if ($request->filled('area_id')) {
            $reqArea = $request->input('area_id');
            if ($reqArea === 'all') {
                $selectedAreaId = 'all';
            } else {
                $reqAreaId = (int) $reqArea;
                if ($canViewAllAreas || in_array($reqAreaId, $userAccessibleAreaIds)) {
                    $selectedAreaId = $reqAreaId;
                } else {
                    $selectedAreaId = $canViewAllAreas ? 'all' : (count($userAccessibleAreaIds) > 0 ? $userAccessibleAreaIds[0] : 'all');
                }
            }
        } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) === 1) {
            $selectedAreaId = $userAccessibleAreaIds[0];
        }

        $realtimeStats = $this->getRealtimeData($user, $selectedAreaId);

        $nasCount = 1;
        try {
            if (class_exists(Nas::class)) {
                $nasCount = max(1, Nas::count());
            }
        } catch (\Throwable $e) {}

        return Inertia::render('DashboardRealtime', [
            'realtimeStats'  => $realtimeStats,
            'availableAreas' => $availableAreas,
            'selectedAreaId' => $selectedAreaId,
            'nasStats'       => ['total_nas' => $nasCount],
        ]);
    }

    /**
     * API Polling data realtime untuk AJAX / live poll
     */
    public function realtimeApi(Request $request)
    {
        $user = auth()->user();
        $selectedAreaId = $request->input('area_id', 'all');

        $stats = $this->getRealtimeData($user, $selectedAreaId);

        return response()->json($stats);
    }

    /**
     * Helper perhitungan statistik realtime untuk widget Realtime
     */
    public function getRealtimeData(?User $user = null, $areaId = 'all'): array
    {
        $user = $user ?? auth()->user();

        $canViewAllAreas = $user && (
            $user->hasRole('admin') || 
            $user->role === 'admin' || 
            $user->hasRole('super admin') || 
            $user->role === 'super admin' || 
            $user->can('dashboard_view_all') || 
            $user->can('customers_all_view_all')
        );

        $userAccessibleAreaIds = $user ? $user->getAccessibleAreaIds() : [];

        // Scope filter helper
        $applyAreaScope = function ($query, $tablePrefix = '') use ($areaId, $canViewAllAreas, $userAccessibleAreaIds) {
            $col = $tablePrefix ? "{$tablePrefix}.area_id" : 'area_id';
            if (is_numeric($areaId)) {
                $query->where($col, (int) $areaId);
            } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
                $query->whereIn($col, $userAccessibleAreaIds);
            }
            return $query;
        };

        // 1. Sesi RADIUS Online
        $onlineUsernames = [];
        try {
            if (class_exists(RadAcct::class)) {
                $onlineUsernames = RadAcct::online()->pluck('username')->filter()->unique()->toArray();
            }
        } catch (\Throwable $e) {
            $onlineUsernames = [];
        }

        // Voucher online vs PPPoE / Billing Online
        $voucherUsernames = [];
        try {
            if (count($onlineUsernames) > 0) {
                $voucherUsernames = Voucher::whereIn('username', $onlineUsernames)->pluck('username')->toArray();
            }
        } catch (\Throwable $e) {
            $voucherUsernames = [];
        }

        $onlineVoucherCount = count($voucherUsernames);
        // Sisanya adalah sesi pelanggan PPPoE / Billing
        $onlineBillingCount = max(0, count($onlineUsernames) - $onlineVoucherCount);

        // Jika data RADIUS online kosong (misal lab/dev/radius offline), fallback ke data pelanggan aktif
        $custBase = Customer::query();
        $applyAreaScope($custBase);
        $totalActiveCust = (clone $custBase)->where('status', 'active')->count();

        if (count($onlineUsernames) === 0 && $totalActiveCust > 0) {
            $onlineBillingCount = $totalActiveCust;
            $onlineVoucherCount = Voucher::where('status', 'used')->where('is_active', true)->count();
        }

        $realOnline = $onlineVoucherCount + $onlineBillingCount;

        // Pending count: pelanggan proses pemasangan/aktivasi
        $pendingCount = (clone $custBase)->whereIn('status', ['booking', 'survey', 'installing', 'menunggu_aktivasi'])->count();

        // Billing UNPAID
        $invoiceBase = Invoice::query()->whereIn('status', ['unpaid', 'partially_paid', 'pending']);
        if (is_numeric($areaId)) {
            $invoiceBase->whereHas('customer', fn($q) => $q->where('area_id', (int) $areaId));
        } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
            $invoiceBase->whereHas('customer', fn($q) => $q->whereIn('area_id', $userAccessibleAreaIds));
        }
        $unpaidCount = (clone $invoiceBase)->count();

        // Sample list unpaid untuk modal aksi
        $unpaidCustomers = (clone $invoiceBase)
            ->with(['customer.areaModel'])
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'customer_name' => $inv->customer?->name ?? 'Pelanggan',
                'customer_code' => $inv->customer?->customer_code ?? '-',
                'invoice_number' => $inv->invoice_number,
                'area_name' => $inv->customer?->areaModel?->name ?? 'Area Default',
                'amount' => $inv->amount,
                'customer_phone' => $inv->customer?->phone ?? '-',
            ]);

        // Billing ISOLIR
        $isolirBase = Customer::query()->where(function ($q) {
            $q->where('status', 'suspended')->orWhere('service_status', 'isolir');
        });
        $applyAreaScope($isolirBase);
        $isolirCount = (clone $isolirBase)->count();

        // Sample list isolir untuk modal aksi
        $isolirCustomers = (clone $isolirBase)
            ->with(['areaModel'])
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'customer_code' => $c->customer_code,
                'area_name' => $c->areaModel?->name ?? 'Area Default',
                'phone' => $c->phone ?? '-',
            ]);

        // Max Capacity (minimal 3600 sesuai desain atau kapasitas dinamis)
        $maxCapacity = max(3600, (int) (ceil(max($realOnline, $totalActiveCust) / 500) * 500));

        return [
            'real_online'      => $realOnline,
            'max_capacity'     => $maxCapacity,
            'online_voucher'   => $onlineVoucherCount,
            'online_billing'   => $onlineBillingCount,
            'pending_count'    => $pendingCount,
            'unpaid_count'     => $unpaidCount,
            'isolir_count'     => $isolirCount,
            'unpaid_customers' => $unpaidCustomers,
            'isolir_customers' => $isolirCustomers,
            'last_updated'     => now()->timezone('Asia/Jakarta')->format('H:i:s'),
        ];
    }
}
