<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\Ont::where('id', 30)->update(['customer_id' => null]);
echo "Fixed ONT 30\n";
