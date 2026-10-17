<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;

class GenerateMissingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-missing-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing invoices for active customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customers = Customer::where('status', 'active')->get();
        $count = 0;
        
        foreach ($customers as $customer) {
            $unpaidCount = Invoice::where('customer_id', $customer->id)
                ->where('status', 'unpaid')
                ->count();
                
            if ($unpaidCount >= 2) {
                continue;
            }

            // Pelanggan gratis dan free home tidak boleh diterbitkan invoice tagihan
            if (in_array($customer->service_status, ['gratis', 'free_home'])) {
                continue;
            }

            // Check if invoice exists for this customer
            $exists = Invoice::where('customer_id', $customer->id)->exists();
            if (!$exists) {
                $activationDate = $customer->activation_date ? Carbon::parse($customer->activation_date) : Carbon::parse($customer->created_at);
                $amount = $customer->package ? $customer->package->price : 0;
                $status = 'unpaid';
                
                // Hanya PPN yang ditambahkan ke invoice tagihan pelanggan
                $taxPpn = (float) ($customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
                if ($taxPpn > 0) {
                    $amount = $amount + ($amount * ($taxPpn / 100));
                }
                $amount = (int) round($amount);
                
                $isolateDays = (int) \App\Models\Setting::get('isolate_days', '3');
                $isolateTime = \App\Models\Setting::get('isolate_time', '00:00');
                
                $dueDateTime = $activationDate->copy()->addDays($isolateDays);
                $timeParts = explode(':', $isolateTime);
                if (count($timeParts) == 2) {
                    $dueDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                }

                Invoice::create([
                    'customer_id' => $customer->id,
                    'billing_type' => $customer->billing_type ?? \App\Models\Setting::get('billing_type', 'prabayar'),
                    'period_month' => $activationDate->month,
                    'period_year' => $activationDate->year,
                    'amount' => $amount,
                    'due_date' => $dueDateTime,
                    'issued_date' => $activationDate,
                    'status' => $status,
                ]);
                $count++;
            }
        }
        
        $this->info("Created $count missing invoices for active customers.");
    }
}
