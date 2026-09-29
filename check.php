<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$customer = \App\Models\Customer::where('name', 'SOPI')->with('ont.odp')->first();
echo json_encode($customer);
