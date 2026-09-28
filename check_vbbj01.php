<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();

\ = \App\Models\Odp::where('name', 'V-BBJ01')->first();
if (!\) { echo "ODP V-BBJ01 not found\n"; exit; }

\ = \App\Models\Ont::where('odp_id', \->id)->with('area', 'customer')->get();
echo "ODP V-BBJ01 (Area ID: {\->area_id})\n";
foreach (\ as \) {
    echo "ONT ID: {\->id}, SN: {\->serial_number}, Area ID: {\->area_id}, Port: {\->port_number}, Customer ID: {\->customer_id}\n";
}
