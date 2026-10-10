<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ont = \App\Models\Ont::find(30);
if ($ont) {
    print_r($ont->toArray());
}
