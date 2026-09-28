<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$validator = Illuminate\Support\Facades\Validator::make(
    ['technician_ids' => []],
    ['technician_ids' => 'required|array']
);

if ($validator->fails()) {
    echo "FAILS: ";
    print_r($validator->errors()->all());
} else {
    echo "PASSES";
}
