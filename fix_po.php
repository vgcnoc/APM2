<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\MaterialTransaction::where('transaction_number', 'PO-20261010-001')->update(['area_id' => 2]);
echo "Updated PO\n";
