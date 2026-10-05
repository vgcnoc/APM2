<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;

$customers = Customer::where('status', 'active')->get();
$count = 0;

foreach ($customers as $customer) {
    // Check if invoice exists for this customer
    $exists = Invoice::where('customer_id', $customer->id)->exists();
    if (!$exists) {
        $activationDate = $customer->activation_date ? Carbon::parse($customer->activation_date) : Carbon::parse($customer->created_at);
        $amount = $customer->package ? $customer->package->price : 0;
        
        Invoice::create([
            'customer_id' => $customer->id,
            'period_month' => $activationDate->month,
            'period_year' => $activationDate->year,
            'amount' => $amount,
            'due_date' => $activationDate->copy()->addDays(7),
            'issued_date' => $activationDate,
            'status' => 'unpaid',
        ]);
        $count++;
    }
}

echo "Created $count missing invoices for active customers.\n";
