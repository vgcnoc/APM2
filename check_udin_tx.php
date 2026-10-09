<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = \App\Models\MaterialTransaction::where('purpose', 'like', '%Udin%')->first();
if ($t) {
    echo $t->items()->with('material')->get()->toJson(JSON_PRETTY_PRINT);
} else {
    echo "Transaction not found.\n";
}
