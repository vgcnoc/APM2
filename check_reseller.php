<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$customers = \App\Models\Customer::where('is_reseller', true)->get(['id', 'name']);
echo "Resellers:\n";
foreach($customers as $c) {
    echo "- " . $c->name . "\n";
}
