<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$m = \App\Models\Material::where('name', 'like', '%frecon%')->first();
if ($m) {
    echo $m->name . ' - Unit: ' . $m->unit . PHP_EOL;
    foreach($m->stocks as $s) {
        echo 'Area ' . $s->area_id . ' Stock: ' . $s->stock . PHP_EOL;
    }
} else {
    echo 'Not found';
}
