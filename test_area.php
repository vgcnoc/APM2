<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $c = \App\Models\Area::create([
        'name' => 'Area Baru ' . time(),
        'description' => 'Test',
    ]);
    echo "SUCCESS: " . $c->id . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
