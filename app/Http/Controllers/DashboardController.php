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

        if (auth()->check() && (auth()->user()->role === 'reseller' || auth()->user()->role === 'customer')) {
            $user = auth()->user();
            $customer = $user->customer;
            $reseller = $user->reseller;
            
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
            if ($user->role === 'reseller') {
                return redirect()->route('client-area.dashboard');
            }
            if ($user->role === 'customer') {
                return redirect()->route('customer-area.dashboard');
            }
        }

        // ── Statistik Pelanggan ──────────────────────────────
        $customerStats = [
            'total_active'     => Customer::where('status', 'active')->whereNotNull('package_id')->count(),
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

        $invoiceStats = [
            'paid_this_month'   => Invoice::where('period_month', $currentMonth)->where('period_year', $currentYear)->where('status', 'paid')->count(),
            'unpaid_this_month' => Invoice::where('period_month', $currentMonth)->where('period_year', $currentYear)->whereIn('status', ['unpaid', 'overdue'])->count(),
        ];

        // ── Reseller Voucher Belum Bayar ────────────────────
        $resellerUnpaidInvoices = Invoice::where('is_reseller_balance', true)
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
}
