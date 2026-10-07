<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invoices = \Illuminate\Support\Facades\DB::table('invoices')->orderBy('id', 'desc')->limit(5)->get();
print_r($invoices);
