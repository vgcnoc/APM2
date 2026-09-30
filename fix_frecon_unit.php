<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$materials = \App\Models\Material::where('category', 'like', '%Frecon%')->orWhere('name', 'like', '%Frecon%')->get();
foreach($materials as $m) {
    echo "Fixing {$m->name} unit to meter...\n";
    $m->unit = 'meter';
    $m->category = 'Kabel Drop / Frecon';
    $m->save();
}
echo "Done\n";
