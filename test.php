<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $c = \App\Models\Customer::create([
        'name' => 'Test',
        'phone' => '123',
        'address' => 'Test',
        'base_amount' => 150000,
        'registration_date' => now()->toDateString()
    ]);
    echo "SUCCESS: " . $c->id . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
