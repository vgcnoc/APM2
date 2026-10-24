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
use App\Models\VoucherProfile;
use App\Models\ResellerTransaction;
use App\Models\Radius\RadAcct;
use App\Models\Radius\Nas;
use App\Models\User;
use App\Services\RouterTrafficService;
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

                $isDayOff = false;
                $dayOffReason = null;

                if ($todaySchedule) {
                    if ($todaySchedule->status === 'off' || empty($todaySchedule->shift_id)) {
                        $isDayOff = true;
                        $dayOffReason = $todaySchedule->notes ?: 'Jadwal Libur Rutin (Day Off)';
                        $shiftToday = null;
                    } else {
                        $shiftToday = $todaySchedule->shift ?: $currentEmployee->shift;
                        $dayOffReason = $todaySchedule->notes;
                    }
                } else {
                    if ($now->isSunday()) {
                        $isDayOff = true;
                        $dayOffReason = 'Hari Libur Mingguan (Minggu)';
                        $shiftToday = null;
                    } else {
                        $shiftToday = $currentEmployee->shift;
                    }
                }

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

        $allShifts = Shift::where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->map(function ($s) {
                $start = substr($s->start_time ?? '', 0, 5);
                $end = substr($s->end_time ?? '', 0, 5);
                return [
                    'id'                => $s->id,
                    'name'              => $s->name,
                    'start_time'        => $start,
                    'end_time'          => $end,
                    'formatted_time'    => "{$start} - {$end} WIB",
                    'tolerance_minutes' => (int) ($s->tolerance_minutes ?? 15),
                    'color'             => $s->color ?? 'blue',
                    'duration_text'     => $s->duration_text ?? null,
                ];
            });

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
            'todaySchedule' => $todaySchedule ? [
                'id'       => $todaySchedule->id,
                'status'   => $todaySchedule->status,
                'notes'    => $todaySchedule->notes,
                'shift_id' => $todaySchedule->shift_id,
                'is_off'   => $isDayOff,
            ] : null,
            'shiftToday' => $shiftToday ? [
                'id'                => $shiftToday->id,
                'name'              => $shiftToday->name,
                'start_time'        => substr($shiftToday->start_time ?? '08:00', 0, 5),
                'end_time'          => substr($shiftToday->end_time ?? '17:00', 0, 5),
                'formatted_time'    => $shiftToday->formatted_time ?? (substr($shiftToday->start_time ?? '08:00', 0, 5) . ' - ' . substr($shiftToday->end_time ?? '17:00', 0, 5) . ' WIB'),
                'tolerance_minutes' => (int) ($shiftToday->tolerance_minutes ?? 15),
                'color'             => $shiftToday->color ?? 'blue',
                'duration_text'     => $shiftToday->duration_text ?? null,
            ] : null,
            'isDayOff' => $isDayOff ?? false,
            'dayOffReason' => $dayOffReason,
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

        $selectedNasId = $request->input('nas_id', 'all');
        $selectedPeriod = $request->input('period', 'today');
        $selectedService = $request->input('service', 'all');
        $selectedStatus = $request->input('status', 'all');
        $searchQuery = (string) $request->input('search', '');

        // List NAS Router MikroTik yang tersedia
        $availableNas = [];
        try {
            if (class_exists(Nas::class)) {
                $availableNas = Nas::orderBy('shortname')->get(['id', 'shortname', 'nasname', 'type']);
            }
        } catch (\Throwable $e) {}

        $realtimeStats = $this->getRealtimeData(
            $user,
            $selectedAreaId,
            $selectedNasId,
            $selectedPeriod,
            $selectedService,
            $selectedStatus,
            $searchQuery
        );

        return Inertia::render('DashboardRealtime', [
            'realtimeStats'   => $realtimeStats,
            'availableAreas'  => $availableAreas,
            'availableNas'    => $availableNas,
            'selectedAreaId'  => $selectedAreaId,
            'selectedNasId'   => $selectedNasId,
            'selectedPeriod'  => $selectedPeriod,
            'selectedService' => $selectedService,
            'selectedStatus'  => $selectedStatus,
            'searchQuery'     => $searchQuery,
            'nasStats'        => ['total_nas' => max(1, count($availableNas))],
        ]);
    }

    /**
     * API Polling data realtime untuk AJAX / live poll
     */
    public function realtimeApi(Request $request)
    {
        $user = auth()->user();
        $selectedAreaId = $request->input('area_id', 'all');
        $selectedNasId = $request->input('nas_id', 'all');
        $selectedPeriod = $request->input('period', 'today');
        $selectedService = $request->input('service', 'all');
        $selectedStatus = $request->input('status', 'all');
        $searchQuery = (string) $request->input('search', '');

        $stats = $this->getRealtimeData(
            $user,
            $selectedAreaId,
            $selectedNasId,
            $selectedPeriod,
            $selectedService,
            $selectedStatus,
            $searchQuery
        );

        return response()->json($stats);
    }

    /**
     * Helper komprehensif perhitungan statistik realtime, trafik internet, UP/DOWN lengkap,
     * PPPoE & Hotspot, isi saldo & penjualan voucher hari ini, serta deteksi gangguan cerdas.
     */
    public function getRealtimeData(
        ?User $user = null,
        $areaId = 'all',
        $nasId = 'all',
        $period = 'today',
        $service = 'all',
        $statusFilter = 'all',
        string $search = ''
    ): array {
        $user = $user ?? auth()->user();
        Carbon::setLocale('id');

        $canViewAllAreas = $user && (
            $user->hasRole('admin') || 
            $user->role === 'admin' || 
            $user->hasRole('super admin') || 
            $user->role === 'super admin' || 
            $user->can('dashboard_view_all') || 
            $user->can('customers_all_view_all')
        );

        $userAccessibleAreaIds = $user ? $user->getAccessibleAreaIds() : [];

        // Scope filter helper untuk tabel dengan kolom area_id
        $applyAreaScope = function ($query, $tablePrefix = '') use ($areaId, $canViewAllAreas, $userAccessibleAreaIds) {
            $col = $tablePrefix ? "{$tablePrefix}.area_id" : 'area_id';
            if (is_numeric($areaId)) {
                $query->where($col, (int) $areaId);
            } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
                $query->whereIn($col, $userAccessibleAreaIds);
            }
            return $query;
        };

        // ── 0. Pengukuran Trafik Jaringan Internet 100% Real (RouterOS API / RADIUS) ──
        $trafficService = app(RouterTrafficService::class);
        $realTraffic = $trafficService->getRealTraffic($nasId);

        // ── 1. Ambil Sesi RADIUS Online & Disconnect Terakhir ───────────────────────
        $onlineSessions = collect();
        $lastDisconnects = collect();
        $targetNasIp = null;

        if (is_numeric($nasId) && class_exists(Nas::class)) {
            $nasObj = Nas::find((int) $nasId);
            if ($nasObj) {
                $targetNasIp = $nasObj->nasname;
            }
        }

        try {
            if (class_exists(RadAcct::class)) {
                $radQuery = RadAcct::online();
                if ($targetNasIp) {
                    $radQuery->where('nasipaddress', $targetNasIp);
                }
                $onlineSessions = $radQuery->get()->keyBy('username');

                // Sesi disconnect terakhir per username
                $lastDisconnectQuery = RadAcct::whereNotNull('acctstoptime');
                if ($targetNasIp) {
                    $lastDisconnectQuery->where('nasipaddress', $targetNasIp);
                }
                $lastDisconnects = $lastDisconnectQuery
                    ->orderByDesc('acctstoptime')
                    ->get(['username', 'acctstoptime', 'callingstationid', 'framedipaddress', 'acctterminatecause', 'acctsessiontime', 'acctinputoctets', 'acctoutputoctets'])
                    ->unique('username')
                    ->keyBy('username');
            }
        } catch (\Throwable $e) {}

        $onlineUsernames = $onlineSessions->keys()->all();
        if (!empty($realTraffic['active_ppp_users'])) {
            $onlineUsernames = array_unique(array_merge($onlineUsernames, $realTraffic['active_ppp_users']));
        }

        // ── 2. Data Pelanggan PPPoE & Status UP/DOWN Lengkap (100% Real) ────────────
        $custBase = Customer::query()
            ->whereNotNull('package_id')
            ->with(['areaModel', 'ont.odp', 'package']);
        $applyAreaScope($custBase);
        $rawCustomers = $custBase->get();

        $pppoeItems = [];
        $pppoeOnlineCount = 0;
        $pppoeDownCount = 0;
        $pppoeIsolirCount = 0;

        foreach ($rawCustomers as $c) {
            $u = $c->ont?->pppoe_user ?: $c->customer_code;
            $s = $onlineSessions->get($u);
            $isOnline = !is_null($s) || in_array($u, $realTraffic['active_ppp_users'] ?? []);
            $last = $lastDisconnects->get($u);

            $isIsolir = in_array($c->status, ['suspended']) || $c->service_status === 'isolir';

            if ($isIsolir) {
                $statusKey = 'isolir';
                $pppoeIsolirCount++;
            } elseif ($isOnline) {
                $statusKey = 'up';
                $pppoeOnlineCount++;
            } else {
                $statusKey = 'down';
                $pppoeDownCount++;
            }

            // Waktu Lengkap WIB dan Waktu Relatif
            $eventTime = $isOnline
                ? ($s?->acctstarttime ? Carbon::parse($s->acctstarttime) : Carbon::parse($c->activation_date ?? $c->updated_at))
                : ($last?->acctstoptime ? Carbon::parse($last->acctstoptime) : Carbon::parse($c->updated_at));

            // Keterangan Penyebab Disconnect (Up/Down)
            $cause = $last?->acctterminatecause ?? 'Koneksi Terputus';
            if ($isOnline) {
                $causeDesc = 'Koneksi Normal (Sesi Aktif)';
            } else {
                if ($cause === 'User-Request') {
                    $causeDesc = 'Klien Memutuskan Koneksi (User-Request)';
                } elseif ($cause === 'Lost-Carrier') {
                    $causeDesc = 'Kabel Fiber Putus / ONT Padam (Lost-Carrier)';
                } elseif ($cause === 'Session-Timeout') {
                    $causeDesc = 'Batas Waktu Sesi Habis (Session-Timeout)';
                } elseif ($cause === 'Admin-Reset') {
                    $causeDesc = 'Direset Admin / Sistem (Admin-Reset)';
                } elseif ($cause === 'NAS-Request') {
                    $causeDesc = 'Permintaan Router NAS (NAS-Request)';
                } elseif ($cause === 'Port-Error') {
                    $causeDesc = 'Gangguan Port / Fisik (Port-Error)';
                } else {
                    $causeDesc = 'Koneksi Terputus (Offline)';
                }
            }

            // Nomor telepon & Link WhatsApp Follow-up Cerdas
            $phoneClean = preg_replace('/[^0-9]/', '', (string) $c->phone);
            if (str_starts_with($phoneClean, '0')) {
                $phoneClean = '62' . substr($phoneClean, 1);
            }
            $waMsg = rawurlencode("Halo Bpk/Ibu {$c->name} ({$c->customer_code}), kami mendeteksi koneksi internet Anda sedang " . ($isOnline ? "aktif normal" : "terputus sejak {$eventTime->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s')} WIB") . ". Apakah membutuhkan bantuan teknisi?");
            $waUrl = !empty($phoneClean) ? "https://wa.me/{$phoneClean}?text={$waMsg}" : null;

            $pppoeItems[] = [
                'id'               => $c->id,
                'service_type'     => 'pppoe',
                'customer_code'    => $c->customer_code,
                'name'             => $c->name,
                'phone'            => $c->phone ?? '-',
                'username'         => $u,
                'area_name'        => $c->areaModel?->name ?? 'Default Area',
                'odp_name'         => $c->ont?->odp?->name ?? 'Belum Ada ODP',
                'ip_address'       => $s?->framedipaddress ?? $last?->framedipaddress ?? '-',
                'mac_address'      => $s?->callingstationid ?? $last?->callingstationid ?? $c->ont?->mac_address ?? '-',
                'status'           => $statusKey,
                'exact_timestamp'  => $eventTime->toIso8601String(),
                'full_time'        => $eventTime->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
                'time_ago'         => $isOnline ? 'Aktif ' . $eventTime->diffForHumans(null, true) : 'Down ' . $eventTime->diffForHumans(),
                'disconnect_cause' => $causeDesc,
                'package_name'     => $c->package?->name ?? 'Paket Reguler',
                'whatsapp_url'     => $waUrl,
            ];
        }

        $pppoeTotal = count($pppoeItems);
        $pppoeUptimeRate = $pppoeTotal > 0 ? round(($pppoeOnlineCount / $pppoeTotal) * 100, 1) : 100;

        // ── 3. Data Hotspot & Voucher Terjual Hari Ini ──────────────────────────────
        $hotspotItems = [];
        $hotspotOnlineCount = 0;
        $hotspotDownCount = 0;
        $voucherAvailableCount = 0;
        $voucherSalesToday = 0;
        $voucherCountToday = 0;
        $voucherBreakdown = [];
        $recentVouchers = [];

        try {
            if (class_exists(Voucher::class)) {
                $voucherAvailableCount = Voucher::where('status', 'unused')->count();

                // Voucher yang digunakan / terjual hari ini
                $todayUsedVouchers = Voucher::with(['profile', 'reseller.user'])
                    ->where('status', 'used')
                    ->whereDate('used_at', today())
                    ->get();

                $voucherCountToday = $todayUsedVouchers->count();
                $voucherSalesToday = (float) $todayUsedVouchers->sum(fn($v) => (float) ($v->profile?->price ?? 0));

                // Breakdown per profile HANYA untuk voucher yang benar-benar terjual hari ini (100% Real)
                $targetBreakdownVouchers = $todayUsedVouchers;

                $grouped = $targetBreakdownVouchers->groupBy(fn($v) => $v->profile?->name ?? 'Hotspot Reguler');
                foreach ($grouped as $profName => $groupItems) {
                    $itemPrice = (float) ($groupItems->first()->profile?->price ?? 0);
                    $subTotal = (float) $groupItems->sum(fn($v) => (float) ($v->profile?->price ?? 0));
                    $voucherBreakdown[] = [
                        'profile_name' => $profName,
                        'count'        => $groupItems->count(),
                        'price'        => $itemPrice,
                        'price_formatted' => 'Rp ' . number_format($itemPrice, 0, ',', '.'),
                        'subtotal'     => $subTotal,
                        'subtotal_formatted' => 'Rp ' . number_format($subTotal, 0, ',', '.'),
                    ];
                }

                // Ambil daftar sesi Hotspot terbaru (15 item)
                $sampleVouchers = Voucher::with(['profile', 'reseller.user'])
                    ->whereIn('status', ['used', 'expired'])
                    ->orderByDesc('used_at')
                    ->take(15)
                    ->get();

                foreach ($sampleVouchers as $v) {
                    $vUser = $v->username ?: $v->code;
                    $isVOnline = in_array($vUser, $onlineUsernames) || in_array($vUser, $realTraffic['active_hotspot_users'] ?? []);
                    if ($isVOnline) {
                        $vStatus = 'up';
                        $hotspotOnlineCount++;
                    } else {
                        $vStatus = 'down';
                        $hotspotDownCount++;
                    }

                    $usedTime = $v->used_at ? Carbon::parse($v->used_at) : Carbon::parse($v->updated_at);
                    $hotspotItems[] = [
                        'id'               => $v->id,
                        'service_type'     => 'hotspot',
                        'customer_code'    => $v->code,
                        'name'             => 'Hotspot: ' . $v->code,
                        'phone'            => '-',
                        'username'         => $vUser,
                        'area_name'        => 'Hotspot Area',
                        'odp_name'         => 'Access Point',
                        'ip_address'       => '-',
                        'mac_address'      => '-',
                        'status'           => $vStatus,
                        'exact_timestamp'  => $usedTime->toIso8601String(),
                        'full_time'        => $usedTime->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
                        'time_ago'         => $usedTime->diffForHumans(),
                        'disconnect_cause' => $vStatus === 'up' ? 'Sesi Aktif' : 'Masa Aktif Selesai / Expired',
                        'package_name'     => $v->profile?->name ?? 'Hotspot Reguler',
                        'price'            => (float) ($v->profile?->price ?? 0),
                        'price_formatted'  => 'Rp ' . number_format($v->profile?->price ?? 0, 0, ',', '.'),
                        'reseller_name'    => $v->reseller?->name ?? $v->reseller?->user?->name ?? 'Admin / Langsung',
                        'whatsapp_url'     => null,
                    ];

                    $recentVouchers[] = [
                        'id'               => $v->id,
                        'code'             => $v->code,
                        'profile_name'     => $v->profile?->name ?? 'Hotspot Reguler',
                        'price'            => (float) ($v->profile?->price ?? 0),
                        'price_formatted'  => 'Rp ' . number_format($v->profile?->price ?? 0, 0, ',', '.'),
                        'full_time'        => $usedTime->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
                        'time_ago'         => $usedTime->diffForHumans(),
                        'reseller_name'    => $v->reseller?->name ?? $v->reseller?->user?->name ?? 'Admin / Langsung',
                    ];
                }
            }
        } catch (\Throwable $e) {}

        $hotspotTotal = $hotspotOnlineCount + $hotspotDownCount;
        $hotspotUptimeRate = $hotspotTotal > 0 ? round(($hotspotOnlineCount / $hotspotTotal) * 100, 1) : 100;

        // ── 4. Isi Saldo Reseller / Agen Hari Ini ─────────────────────────────────────
        $topupAmountToday = 0;
        $topupCountToday = 0;
        $topupTotalAll = 0;
        $recentTopups = [];

        try {
            if (class_exists(ResellerTransaction::class)) {
                $topupTodayQuery = ResellerTransaction::where('type', 'credit')->whereDate('created_at', today());
                $topupAmountToday = (float) $topupTodayQuery->sum('amount');
                $topupCountToday = $topupTodayQuery->count();

                $topupTotalAll = (float) ResellerTransaction::where('type', 'credit')->sum('amount');

                $recentTx = ResellerTransaction::where('type', 'credit')
                    ->with(['reseller.user'])
                    ->orderByDesc('created_at')
                    ->take(6)
                    ->get();

                foreach ($recentTx as $tx) {
                    $tTime = Carbon::parse($tx->created_at);
                    $recentTopups[] = [
                        'id'               => $tx->id,
                        'reseller_name'    => $tx->reseller?->name ?? $tx->reseller?->user?->name ?? ('Mitra #' . $tx->reseller_id),
                        'amount'           => (float) $tx->amount,
                        'amount_formatted' => 'Rp ' . number_format($tx->amount, 0, ',', '.'),
                        'description'      => $tx->description ?: 'Top Up Saldo Mandiri / Payment Gateway',
                        'full_time'        => $tTime->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
                        'time_ago'         => $tTime->diffForHumans(),
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // ── 5. Trafik Internet Realtime (100% Real dari RouterTrafficService) ────────
        $currentRxMbps = (float) ($realTraffic['download_mbps'] ?? 0.0);
        $currentTxMbps = (float) ($realTraffic['upload_mbps'] ?? 0.0);
        $peakMbpsToday = (float) ($realTraffic['peak_mbps_today'] ?? 0.0);
        $downloadGbToday = (float) ($realTraffic['download_gb_today'] ?? 0.0);
        $uploadGbToday = (float) ($realTraffic['upload_gb_today'] ?? 0.0);
        $totalGbToday = (float) ($realTraffic['total_gb_today'] ?? 0.0);
        $trafficHistory = $realTraffic['history'] ?? [];

        // ── 6. Deteksi Cerdas Gangguan Massal (Smart Outage Detection) ────────────────
        $downClusters = [];
        $outageDetected = false;
        $outageSummary = '';

        // Kelompokkan pelanggan PPPoE yang DOWN berdasarkan ODP
        $downPPPoE = array_filter($pppoeItems, fn($item) => $item['status'] === 'down');
        $odpGroups = [];
        foreach ($downPPPoE as $item) {
            $odpKey = $item['odp_name'] ?: 'ODP Default';
            if ($odpKey !== 'Belum Ada ODP') {
                $odpGroups[$odpKey][] = $item['name'] . ' (' . $item['customer_code'] . ')';
            }
        }

        foreach ($odpGroups as $odpName => $custNames) {
            if (count($custNames) >= 2) {
                $outageDetected = true;
                $downClusters[] = [
                    'target_type'    => 'ODP',
                    'target_name'    => $odpName,
                    'affected_count' => count($custNames),
                    'customers'      => $custNames,
                    'message'        => "Potensi putus jalur kabel dropcore/distribusi atau listrik mati di {$odpName}.",
                ];
            }
        }

        if ($outageDetected) {
            $totalAffected = array_sum(array_column($downClusters, 'affected_count'));
            $odpListStr = implode(', ', array_column($downClusters, 'target_name'));
            $outageSummary = "⚠️ Terdeteksi {$totalAffected} pelanggan offline bersamaan pada ODP: {$odpListStr}. Segera periksa jalur distribusi.";
        }

        // ── 7. Feed Aktivitas Koneksi UP & DOWN Terkini ──────────────────────────────
        $allEvents = array_merge($pppoeItems, $hotspotItems);
        // Urutkan berdasarkan waktu kejadian terbaru
        usort($allEvents, fn($a, $b) => strcmp($b['exact_timestamp'], $a['exact_timestamp']));

        // Filter penerapan Search & Service & Status jika ada parameter
        if (!empty($search)) {
            $qLower = mb_strtolower($search);
            $allEvents = array_values(array_filter($allEvents, function ($ev) use ($qLower) {
                return str_contains(mb_strtolower($ev['name']), $qLower) ||
                       str_contains(mb_strtolower($ev['customer_code']), $qLower) ||
                       str_contains(mb_strtolower($ev['username']), $qLower) ||
                       str_contains(mb_strtolower($ev['ip_address']), $qLower) ||
                       str_contains(mb_strtolower($ev['mac_address']), $qLower) ||
                       str_contains(mb_strtolower($ev['odp_name']), $qLower) ||
                       str_contains(mb_strtolower($ev['phone']), $qLower);
            }));
        }

        if ($service !== 'all') {
            $allEvents = array_values(array_filter($allEvents, fn($ev) => $ev['service_type'] === $service));
        }

        if ($statusFilter !== 'all') {
            $allEvents = array_values(array_filter($allEvents, fn($ev) => $ev['status'] === $statusFilter));
        }

        // Batasi 30 feed aktivitas untuk efisiensi transfer data realtime
        $connectionFeed = array_slice($allEvents, 0, 30);

        // ── 8. Unpaid & Isolir Billing ──────────────────────────────────────────────
        $invoiceBase = Invoice::query()->whereIn('status', ['unpaid', 'partially_paid', 'pending']);
        if (is_numeric($areaId)) {
            $invoiceBase->whereHas('customer', fn($q) => $q->where('area_id', (int) $areaId));
        } elseif (!$canViewAllAreas && count($userAccessibleAreaIds) > 0) {
            $invoiceBase->whereHas('customer', fn($q) => $q->whereIn('area_id', $userAccessibleAreaIds));
        }
        $unpaidCount = (clone $invoiceBase)->count();

        $unpaidCustomers = (clone $invoiceBase)
            ->with(['customer.areaModel'])
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($inv) => [
                'id'             => $inv->id,
                'customer_name'  => $inv->customer?->name ?? 'Pelanggan',
                'customer_code'  => $inv->customer?->customer_code ?? '-',
                'invoice_number' => $inv->invoice_number,
                'area_name'      => $inv->customer?->areaModel?->name ?? 'Area Default',
                'amount'         => $inv->amount,
                'customer_phone' => $inv->customer?->phone ?? '-',
            ]);

        $isolirBase = Customer::query()->where(function ($q) {
            $q->where('status', 'suspended')->orWhere('service_status', 'isolir');
        });
        $applyAreaScope($isolirBase);
        $isolirCount = (clone $isolirBase)->count();

        $isolirCustomers = (clone $isolirBase)
            ->with(['areaModel'])
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'customer_code' => $c->customer_code,
                'area_name'     => $c->areaModel?->name ?? 'Area Default',
                'phone'         => $c->phone ?? '-',
            ]);

        // ── 9. Deteksi Kapasitas Maksimal CPU Router MikroTik (Cerdas) ──────────────
        $capacityService = app(\App\Services\RouterCapacityService::class);
        try {
            $capacity = $capacityService->detect(
                $capacityService->nasIdsForScope($areaId, $canViewAllAreas, $userAccessibleAreaIds)
            );
        } catch (\Throwable $e) {
            $capacity = ['max_capacity' => 0, 'source' => 'unknown', 'cpu_load' => null, 'devices' => []];
        }
        $maxCapacity = max(1, (int) $capacity['max_capacity']);
        $realOnline = $pppoeOnlineCount + $hotspotOnlineCount;

        return [
            // Metrik Utama Realtime
            'real_online'        => $realOnline,
            'max_capacity'       => $maxCapacity,
            'capacity_source'    => $capacity['source'],
            'capacity_cpu_load'  => $capacity['cpu_load'],
            'capacity_devices'   => $capacity['devices'],
            'online_voucher'     => $hotspotOnlineCount,
            'online_billing'     => $pppoeOnlineCount,
            'pending_count'      => (clone $custBase)->whereIn('status', ['booking', 'survey', 'installing', 'menunggu_aktivasi'])->count(),
            'unpaid_count'       => $unpaidCount,
            'isolir_count'       => $isolirCount,
            'unpaid_customers'   => $unpaidCustomers,
            'isolir_customers'   => $isolirCustomers,

            // Trafik Internet Lengkap (100% Real)
            'traffic' => [
                'download_mbps'    => $currentRxMbps,
                'upload_mbps'      => $currentTxMbps,
                'total_mbps'       => round($currentRxMbps + $currentTxMbps, 1),
                'peak_mbps_today'  => $peakMbpsToday,
                'download_gb_today'=> $downloadGbToday,
                'upload_gb_today'  => $uploadGbToday,
                'total_gb_today'   => $totalGbToday,
                'history'          => $trafficHistory,
                'source_type'      => $realTraffic['source_type'] ?? 'standby',
                'source_label'     => $realTraffic['source_label'] ?? 'Standby (0 Mbps)',
                'router_online'    => (bool) ($realTraffic['router_online'] ?? false),
            ],

            // PPPoE Stats & Items
            'pppoe' => [
                'online'      => $pppoeOnlineCount,
                'down'        => $pppoeDownCount,
                'isolir'      => $pppoeIsolirCount,
                'total'       => $pppoeTotal,
                'uptime_rate' => $pppoeUptimeRate,
                'items'       => array_slice($pppoeItems, 0, 50),
            ],

            // Hotspot Stats & Items
            'hotspot' => [
                'online'      => $hotspotOnlineCount,
                'down'        => $hotspotDownCount,
                'available'   => $voucherAvailableCount,
                'total'       => $hotspotTotal,
                'uptime_rate' => $hotspotUptimeRate,
                'items'       => array_slice($hotspotItems, 0, 50),
            ],

            // Isi Saldo Hari Ini
            'topup' => [
                'today_amount'           => $topupAmountToday,
                'today_amount_formatted' => 'Rp ' . number_format($topupAmountToday, 0, ',', '.'),
                'today_count'            => $topupCountToday,
                'total_all_time'         => $topupTotalAll,
                'total_all_formatted'    => 'Rp ' . number_format($topupTotalAll, 0, ',', '.'),
                'recent_topups'          => $recentTopups,
            ],

            // Voucher Terjual Hari Ini
            'voucher_sales' => [
                'today_amount'           => $voucherSalesToday,
                'today_amount_formatted' => 'Rp ' . number_format($voucherSalesToday, 0, ',', '.'),
                'today_count'            => $voucherCountToday,
                'breakdown'              => $voucherBreakdown,
                'recent_vouchers'        => $recentVouchers,
            ],

            // Live Connection Events Feed (UP & DOWN dengan Waktu Lengkap)
            'connection_feed' => $connectionFeed,

            // Deteksi Gangguan Massal Cerdas
            'smart_outage' => [
                'detected' => $outageDetected,
                'summary'  => $outageSummary,
                'clusters' => $downClusters,
            ],

            'last_updated'      => now()->timezone('Asia/Jakarta')->format('H:i:s'),
            'last_updated_full' => now()->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
        ];
    }
}
