<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$materials = \App\Models\Material::all();
foreach($materials as $m) {
    echo $m->name . ' : ' . $m->price . ' (' . $m->unit . ")\n";
}
