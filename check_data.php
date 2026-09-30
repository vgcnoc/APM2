<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Area;
use Illuminate\Http\Request;

$baseQuery = Customer::installed();
$all = (clone $baseQuery)->count();
$jadwal = (clone $baseQuery)->where('status', 'installing')->whereDoesntHave('technicianSchedules', fn($q) => $q->where('type', 'installation'))->count();
$laporan = (clone $baseQuery)->where('status', 'installing')->whereHas('technicianSchedules', fn($q) => $q->where('type', 'installation'))
                ->where(function ($q) {
                    $q->doesntHave('ont')->orWhereHas('ont', function ($q2) { $q2->whereNull('rx_power'); });
                })->count();
$audit = (clone $baseQuery)->where('status', 'installing')->where('is_audited', false)->whereHas('ont', function ($q) { $q->whereNotNull('rx_power'); })->count();
$selesai = (clone $baseQuery)->where(function ($q) {
                    $q->where('status', 'active')->orWhere(function ($q2) { $q2->where('status', 'installing')->where('is_audited', true); });
                })->count();

echo "Total Installed Scope: $all\n";
echo "- Jadwal Pasang: $jadwal\n";
echo "- Laporan Pasang: $laporan\n";
echo "- Audit: $audit\n";
echo "- Selesai Instalasi: $selesai\n";

$areas = Area::count();
echo "Total Areas in DB: $areas\n";
$customersWithAreaString = Customer::whereNotNull('area')->count();
echo "Customers with string area: $customersWithAreaString\n";
$customersWithAreaId = Customer::whereNotNull('area_id')->count();
echo "Customers with area_id: $customersWithAreaId\n";
