<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\BillingIsolationSchedule;
use App\Services\CustomerBillingPrice;
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
    public function handle(): int
    {
        $billingTimezone = config('billing.timezone', 'Asia/Jakarta');
        $today = now()->setTimezone($billingTimezone);
        $todayDateString = $today->toDateString();

        $globalCycle = Setting::get('billing_cycle', 'anniversary');
        $globalBillingType = Setting::get('billing_type', 'prabayar');
        $globalTaxPpn = (float) Setting::get('tax_ppn', '0');
        $calendarIssueDay = max(1, (int) Setting::get('invoice_issue_date', '1'));
        $force = (bool) $this->option('force');

        $isolationSchedule = app(BillingIsolationSchedule::class);
        $isolationSettings = $isolationSchedule->settings();
        $dueDateTime = $isolationSchedule->forIssueDate($today, $isolationSettings)
            ->setTimezone($billingTimezone);

        $customers = Customer::whereIn('status', ['active', 'suspended'])
            ->where(function ($q) {
                $q->where('is_reseller', false)
                  ->orWhereNull('is_reseller');
            })
            ->whereNotIn('service_status', ['gratis', 'free_home'])
            ->with('package')
            ->get();

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
                    $actDate = $customer->activation_date
                        ? Carbon::parse($customer->activation_date)
                        : ($customer->registration_date ? Carbon::parse($customer->registration_date) : null);
                    $targetDay = $actDate ? $actDate->day : $calendarIssueDay;
                    // Antisipasi bulan dengan jumlah hari lebih sedikit (mis. Feb 28/29, April 30)
                    $targetDay = min($targetDay, $today->daysInMonth);
                    $isDueToday = ($today->day === $targetDay);
                } elseif ($effectiveCycle === 'custom_date') {
                    // Berdasarkan tanggal khusus yang dipilih pelanggan (1-28)
                    $customDay = max(1, (int) ($customer->billing_day ?: 1));
                    $targetDay = min($customDay, $today->daysInMonth);
                    $isDueToday = ($today->day === $targetDay);
                } else {
                    // Siklus Kalender / Serentak
                    $targetDay = min($calendarIssueDay, $today->daysInMonth);
                    $isDueToday = ($today->day === $targetDay);
                }

                if (!$isDueToday) {
                    continue;
                }
            }

            // Cek apakah invoice periode bulan ini ATAU invoice yang sudah dijadwalkan terbit hari ini
            // (mis. tagihan pertama prorata pascabayar bulan lalu yang terbit tanggal 1 bulan ini) sudah ada.
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('is_reseller_balance', false)
                ->where(function ($numQuery) {
                    $numQuery->whereNull('invoice_number')
                        ->orWhere('invoice_number', 'not like', 'INV-MIG-%');
                })
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($today, $todayDateString) {
                    $q->where(function ($periodQuery) use ($today) {
                        $periodQuery->where('period_month', $today->month)
                            ->where('period_year', $today->year);
                    })->orWhere(function ($issueQuery) use ($todayDateString) {
                        $issueQuery->whereDate('issued_date', $todayDateString)
                            ->where(function ($bt) {
                                $bt->whereNull('billing_type')
                                    ->orWhereNotIn('billing_type', ['upgrade', 'downgrade']);
                            });
                    });
                })
                ->exists();

            if ($exists) {
                continue;
            }

            // Hitung tunggakan aktif riil (abaikan invoice migrasi historis, dibatalkan, atau top-up reseller)
            $unpaidCount = Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->where('is_reseller_balance', false)
                ->where(function ($numQuery) {
                    $numQuery->whereNull('invoice_number')
                        ->orWhere('invoice_number', 'not like', 'INV-MIG-%');
                })
                ->count();

            // Jika pelanggan sudah memiliki >= 2 tagihan tertunggak, jangan buat tagihan baru
            if ($unpaidCount >= 2) {
                continue;
            }

            // Jika suspended murni stop sementara manual (tanpa tunggakan), jangan buat tagihan baru
            if ($customer->status === 'suspended' && $unpaidCount === 0) {
                continue;
            }

            $amount = CustomerBillingPrice::monthly($customer);
            if ($amount <= 0) {
                continue;
            }

            // Hanya PPN yang ditambahkan ke invoice tagihan pelanggan
            $taxPpn = (float) ($customer->tax_ppn ?? $globalTaxPpn);
            if ($taxPpn > 0) {
                $amount = $amount + ($amount * ($taxPpn / 100));
            }
            $amount = (int) round($amount);

            Invoice::create([
                'customer_id' => $customer->id,
                'billing_type' => $customer->billing_type ?: $globalBillingType,
                'billing_cycle' => $effectiveCycle,
                'period_month' => $today->month,
                'period_year' => $today->year,
                'amount' => $amount,
                'due_date' => $dueDateTime,
                'issued_date' => $today,
                'status' => 'unpaid',
            ]);
            $count++;
        }

        $summary = "Generated {$count} invoices for period {$today->format('M Y')} (Default Cycle: {$globalCycle}, Issue Date: {$calendarIssueDay}).";
        $this->info($summary);

        if ($count > 0) {
            Log::info('[Billing] ' . $summary);
        }

        return self::SUCCESS;
    }
}
