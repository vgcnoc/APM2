<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ProrataCalculator;
use Carbon\Carbon;

class GenerateMissingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-missing-invoices {--october-prorata : Khusus terbitkan invoice prorata untuk pelanggan yang aktif Oktober 2026}';

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

            $activationDate = $customer->activation_date ? Carbon::parse($customer->activation_date) : Carbon::parse($customer->created_at);

            // Khusus opsi --october-prorata: Hanya proses pelanggan yang teraktivasi pada Oktober 2026
            if ($this->option('october-prorata')) {
                if ($activationDate->month !== 10 || $activationDate->year !== 2026) {
                    continue;
                }
            }

            // Check if invoice exists for this customer in activation month/year
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('period_month', $activationDate->month)
                ->where('period_year', $activationDate->year)
                ->exists();

            if (!$exists) {
                $basePrice = $customer->base_amount > 0 ? (float) $customer->base_amount : ($customer->package ? (float) $customer->package->price : 0);
                
                $now = now();
                $isCurrentMonth = ($activationDate->month === $now->month && $activationDate->year === $now->year);
                
                $isProrata = false;
                $billingType = $customer->billing_type ?? \App\Models\Setting::get('billing_type', 'pascabayar');
                $notes = 'Tagihan Pertama';
                
                // Jika aktivasi pada bulan berjalan (cth: Oktober 2026), terbitkan tagihan prorata
                if ($isCurrentMonth || $this->option('october-prorata')) {
                    $endOfMonth = $activationDate->copy()->endOfMonth();
                    $amount = ProrataCalculator::firstMonthAmount(
                        $basePrice,
                        $activationDate,
                        Setting::get('prorata_formula', 'exact_days'),
                    );
                    $isProrata = true;
                    $billingType = 'prorata';
                    $notes = 'Tagihan Pertama (Prorata s/d Akhir Bulan: ' . $activationDate->format('d M') . ' - ' . $endOfMonth->format('d M Y') . ')';
                } else {
                    $amount = $basePrice;
                    $taxPpn = (float) ($customer->tax_ppn ?? \App\Models\Setting::get('tax_ppn', '0'));
                    if ($taxPpn > 0) {
                        $amount = $amount + ($amount * ($taxPpn / 100));
                    }
                    $amount = (int) round($amount);
                }
                
                $issueDateSetting = (int) \App\Models\Setting::get('invoice_issue_date', '1');
                $nextBillingDate = $activationDate->copy();
                if ($activationDate->day >= $issueDateSetting) {
                    $nextBillingDate->startOfMonth()->addMonthNoOverflow()->day($issueDateSetting);
                } else {
                    $nextBillingDate->day($issueDateSetting);
                }
                
                $invoiceIssuedDate = ($billingType === 'pascabayar' || $isProrata) ? $nextBillingDate : $activationDate;
                $dueDateTime = app(\App\Services\BillingIsolationSchedule::class)->forIssueDate($invoiceIssuedDate)
                    ->setTimezone(config('billing.timezone', 'Asia/Jakarta'));

                Invoice::create([
                    'customer_id' => $customer->id,
                    'billing_type' => $billingType,
                    'billing_cycle' => $customer->billing_cycle ?: 'calendar',
                    'period_month' => $activationDate->month,
                    'period_year' => $activationDate->year,
                    'amount' => $amount,
                    'due_date' => $dueDateTime,
                    'issued_date' => $invoiceIssuedDate,
                    'status' => 'unpaid',
                    'is_prorata' => $isProrata,
                    'notes' => $notes,
                ]);
                $count++;
            }
        }
        
        $this->info("Created $count missing invoices for active customers.");
    }
}
