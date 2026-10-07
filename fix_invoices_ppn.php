<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$taxPpn = (float) \App\Models\Setting::get('tax_ppn', '0');
$taxBhp = (float) \App\Models\Setting::get('tax_bhp', '0');
$taxUso = (float) \App\Models\Setting::get('tax_uso', '0');
$totalTaxPercent = $taxPpn + $taxBhp + $taxUso;

if ($totalTaxPercent > 0) {
    $invoices = \App\Models\Invoice::where('status', 'unpaid')->with('customer.package')->get();
    $count = 0;
    foreach($invoices as $inv) {
        $packagePrice = $inv->customer->package ? $inv->customer->package->price : 0;
        if ($packagePrice > 0 && $inv->amount == $packagePrice) {
            $inv->amount = $inv->amount + ($inv->amount * ($totalTaxPercent / 100));
            $inv->remaining = $inv->amount - $inv->total_paid; // Adjust remaining just in case
            $inv->save();
            $count++;
        }
    }
    echo "Updated $count invoices with PPN.\n";
} else {
    echo "No tax setting found.\n";
}
