<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$trx = \App\Models\MaterialTransaction::where('transaction_number', 'PO-20261010-001')->first();
if ($trx) {
    echo "Transaction {$trx->transaction_number} | Area ID: {$trx->area_id} | Cabang: {$trx->cabang} | Area: {$trx->area}\n";
} else {
    echo "Transaction not found\n";
}
