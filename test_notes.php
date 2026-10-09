<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedules = App\Models\TechnicianSchedule::where('type', 'installation')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get();

foreach ($schedules as $s) {
    echo "ID: " . $s->id . " Status: " . $s->status . "\n";
    echo "Notes: \n" . $s->notes . "\n------------------------\n";
}
