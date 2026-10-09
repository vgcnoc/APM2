<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customer = \App\Models\Customer::where('name', 'like', '%Udin%')->first();
if ($customer) {
    $schedule = $customer->technicianSchedules()->where('type', 'installation')->first();
    if ($schedule) {
        $notes = $schedule->notes;
        $notes = str_replace('Klem (10 pcs)', 'Klem (3 pcs aktual)', $notes);
        $schedule->update(['notes' => $notes]);
        echo "Updated Udin schedule notes.\n";
    }
}
