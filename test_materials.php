<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$materials = \App\Models\Material::where('category', 'like', '%Kabel%')->with('stocks')->get();
foreach($materials as $m) {
    echo "ID: {$m->id}, Name: {$m->name}, Cat: {$m->category}, Unit: {$m->unit}, Stock: {$m->stock}\n";
    foreach($m->stocks as $s) {
        echo "  - Area {$s->area_id}: {$s->stock}\n";
    }
}
