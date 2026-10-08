<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;

class GenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-invoices {--force : Force generation regardless of the date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate periodic invoices based on settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $issueDateSetting = Setting::get('invoice_issue_date', '1');
        $today = now();

        if (!$this->option('force') && $today->day != (int)$issueDateSetting) {
            $this->info("Today ({$today->day}) is not the configured invoice issue date ({$issueDateSetting}). Skipping.");
            return;
        }

        $customers = Customer::whereIn('status', ['active', 'suspended'])->with('package')->get();
        $count = 0;

        foreach ($customers as $customer) {
            // Jika pelanggan sudah memiliki 2 tagihan yang belum dibayar, jangan buat tagihan baru
            $unpaidCount = Invoice::where('customer_id', $customer->id)
                ->where('status', 'unpaid')
                ->count();
                
            if ($unpaidCount >= 2) {
                continue;
            }

            // Check if invoice for the current month and year already exists
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('period_month', $today->month)
                ->where('period_year', $today->year)
                ->exists();

            // Check if suspended
            if ($customer->status === 'suspended') {
                $unpaidCount = Invoice::where('customer_id', $customer->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->count();
                
                // Jika tidak ada tunggakan (murni stop sementara manual) ATAU sudah menunggak >= 2 bulan, jangan buat invoice baru
                if ($unpaidCount == 0 || $unpaidCount >= 2) {
                    continue;
                }
            }

            if (!$exists) {
                $amount = $customer->package ? $customer->package->price : 0;
                $status = 'unpaid';

                if ($customer->service_status === 'gratis') {
                    $amount = 0;
                    $status = 'paid';
                } else {
                    $taxPpn = (float) ($customer->tax_ppn ?? Setting::get('tax_ppn', '0'));
                    $taxBhp = (float) ($customer->tax_bhp ?? Setting::get('tax_bhp', '0'));
                    $taxUso = (float) ($customer->tax_uso ?? Setting::get('tax_uso', '0'));
                    
                    $totalTaxPercent = $taxPpn + $taxBhp + $taxUso;
                    if ($totalTaxPercent > 0) {
                        $amount = $amount + ($amount * ($totalTaxPercent / 100));
                    }
                }

                $isolateDays = (int) Setting::get('isolate_days', '3');
                $isolateTime = Setting::get('isolate_time', '00:00');

                $dueDateTime = $today->copy()->addDays($isolateDays);
                $timeParts = explode(':', $isolateTime);
                if (count($timeParts) == 2) {
                    $dueDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                }

                Invoice::create([
                    'customer_id' => $customer->id,
                    'period_month' => $today->month,
                    'period_year' => $today->year,
                    'amount' => $amount,
                    'due_date' => $dueDateTime,
                    'issued_date' => $today,
                    'status' => $status,
                ]);
                $count++;
            }
        }

        $this->info("Generated $count invoices for period {$today->format('M Y')}.");
    }
}
