<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedules = App\Models\TechnicianSchedule::where('type', 'installation')->get();
            
echo "Installation Schedules count: " . $schedules->count() . "\n";
foreach($schedules as $s) {
    echo "ID: " . $s->id . " Customer ID: " . $s->customer_id . " Status: " . $s->status . "\n";
}
