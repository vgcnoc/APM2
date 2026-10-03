<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::whereHas('roles', function($q){ $q->where('name', 'teknisi'); })->first();
Auth::login($u);
$baseQuery = App\Models\Customer::survey()->when(!auth()->user()->hasRole('admin'), function($q) {
    $q->where(function($sub) {
        $sub->whereHas('technicianSchedules', function($sq) {
            $sq->where('technician_id', auth()->id());
        })
        ->orWhereHas('surveys', function($sq) {
            $sq->where('surveyor_id', auth()->id());
        });
    });
});
echo "Total in BaseQuery: " . (clone $baseQuery)->count() . "\n";
echo "jadwalkan: " . (clone $baseQuery)->where('status', 'survey')->doesntHave('surveys')->doesntHave('technicianSchedules')->count() . "\n";
echo "laporan: " . (clone $baseQuery)->where('status', 'survey')->doesntHave('surveys')->has('technicianSchedules')->count() . "\n";
echo "ready: " . (clone $baseQuery)->where('status', 'survey')->whereHas('surveys', function ($sq) { $sq->where('feasibility', 'feasible'); })->count() . "\n";
echo "unfeasible: " . (clone $baseQuery)->whereHas('surveys', function ($sq) { $sq->where('feasibility', 'not_feasible'); })->count() . "\n";
