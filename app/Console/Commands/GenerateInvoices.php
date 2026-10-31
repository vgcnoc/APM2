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
    protected $description = 'Generate periodic invoices based on configured billing cycle (anniversary/calendar/custom)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->setTimezone(config('billing.timezone', 'Asia/Jakarta'));
        $globalCycle = Setting::get('billing_cycle', 'anniversary');
        $calendarIssueDay = (int) Setting::get('invoice_issue_date', '1');
        $force = (bool) $this->option('force');

        $customers = Customer::whereIn('status', ['active', 'suspended'])->with('package')->get();
        $count = 0;

        foreach ($customers as $customer) {
            // Evaluasi siklus penagihan pelanggan: custom per pelanggan atau ikut default sistem
            $customerCycle = $customer->billing_cycle ?: 'default';
            $effectiveCycle = ($customerCycle === 'default') ? $globalCycle : $customerCycle;

            // Tentukan apakah hari ini adalah hari penerbitan tagihan untuk pelanggan ini
            if (!$force) {
                $isDueToday = false;

                if ($effectiveCycle === 'anniversary') {
                    // Mengikuti tanggal aktivasi
                    $actDate = $customer->activation_date ? Carbon::parse($customer->activation_date) : ($customer->registration_date ? Carbon::parse($customer->registration_date) : null);
                    $targetDay = $actDate ? $actDate->day : $calendarIssueDay;
                    // Antisipasi bulan dengan jumlah hari lebih sedikit (mis. Feb 28/29, April 30)
                    $targetDay = min($targetDay, $today->daysInMonth);
                    $isDueToday = ($today->day == $targetDay);
                } elseif ($effectiveCycle === 'custom_date') {
                    // Berdasarkan tanggal khusus yang dipilih pelanggan (1-28)
                    $customDay = (int) ($customer->billing_day ?: 1);
                    $targetDay = min($customDay, $today->daysInMonth);
                    $isDueToday = ($today->day == $targetDay);
                } else {
                    // Siklus Kalender / Serentak
                    $targetDay = min($calendarIssueDay, $today->daysInMonth);
                    $isDueToday = ($today->day == $targetDay);
                }

                if (!$isDueToday) {
                    continue;
                }
            }

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

            // Pelanggan gratis dan free home tidak boleh diterbitkan invoice tagihan
            if (in_array($customer->service_status, ['gratis', 'free_home'])) {
                continue;
            }

            if (!$exists) {
                $amount = $customer->package?->current_price ?? 0;
                $status = 'unpaid';

                // Hanya PPN yang ditambahkan ke invoice tagihan pelanggan
                $taxPpn = (float) ($customer->tax_ppn ?? Setting::get('tax_ppn', '0'));
                if ($taxPpn > 0) {
                    $amount = $amount + ($amount * ($taxPpn / 100));
                }
                $amount = (int) round($amount);

                $dueDateTime = app(\App\Services\BillingIsolationSchedule::class)->forIssueDate($today)
                    ->setTimezone(config('billing.timezone', 'Asia/Jakarta'));

                Invoice::create([
                    'customer_id' => $customer->id,
                    'billing_type' => $customer->billing_type ?? Setting::get('billing_type', 'prabayar'),
                    'billing_cycle' => $effectiveCycle,
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

        $this->info("Generated $count invoices for period {$today->format('M Y')} (Default Cycle: {$globalCycle}).");
    }
}
