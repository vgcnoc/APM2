<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Odp;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // Statistik Pelanggan
        $customerStats = [
            'total_active' => Customer::active()->count(),
            'total_booking' => Customer::booking()->count(),
            'total_survey' => Customer::survey()->count(),
            'total_all' => Customer::count(),
        ];

        // Statistik Infrastruktur
        $infraStats = [
            'total_odp' => Odp::count(),
            'total_odp_available' => Odp::hasAvailablePort()->count(),
            'total_odp_full' => Odp::where('status', 'full')
                ->orWhereRaw('used_ports >= total_ports')->count(),
        ];

        // Tiket Terbuka
        $ticketStats = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved_today' => Ticket::where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
        ];

        // Pendapatan Bulanan (bulan ini)
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthlyRevenue = Invoice::where('period_month', $currentMonth)
            ->where('period_year', $currentYear)
            ->where('status', 'paid')
            ->sum('amount');

        // Grafik pendapatan 6 bulan terakhir
        $revenueChart = Invoice::select(
            DB::raw('period_month'),
            DB::raw('period_year'),
            DB::raw('SUM(amount) as total')
        )
            ->where('status', 'paid')
            ->where(DB::raw("CONCAT(period_year, '-', LPAD(period_month, 2, '0'))"), '>=',
                now()->subMonths(5)->format('Y-m'))
            ->groupBy('period_year', 'period_month')
            ->orderBy('period_year')
            ->orderBy('period_month')
            ->get();

        // Pelanggan terbaru
        $recentCustomers = Customer::with('package')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Tiket terbaru
        $recentTickets = Ticket::with(['customer', 'assignee'])
            ->where('status', '!=', 'closed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'customerStats' => $customerStats,
            'infraStats' => $infraStats,
            'ticketStats' => $ticketStats,
            'monthlyRevenue' => (float) $monthlyRevenue,
            'revenueChart' => $revenueChart,
            'recentCustomers' => $recentCustomers,
            'recentTickets' => $recentTickets,
        ]);
    }
}
