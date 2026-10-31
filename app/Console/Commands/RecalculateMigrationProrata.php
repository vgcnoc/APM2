<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\CustomerBillingPrice;
use App\Services\ProrataCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecalculateMigrationProrata extends Command
{
    protected $signature = 'app:recalculate-migration-prorata
                            {--apply : Terapkan nominal baru; tanpa opsi ini hanya menampilkan pratinjau}';

    protected $description = 'Hitung ulang invoice prorata pelanggan aktivasi Oktober 2026 dengan rumus prorata aktif';

    public function handle(): int
    {
        $formula = Setting::get('prorata_formula', 'exact_days');
        $globalTaxPpn = (float) Setting::get('tax_ppn', '0');

        $invoices = Invoice::query()
            ->with(['customer.package'])
            ->where('is_reseller_balance', false)
            ->where('invoice_number', 'not like', 'INV-MIG-%')
            ->where('status', 'unpaid')
            ->where('is_prorata', true)
            ->where('period_year', 2026)
            ->where('period_month', 10)
            ->whereHas('customer', function ($query) {
                $query->whereNotIn('service_status', ['gratis', 'free_home'])
                    ->whereDate('activation_date', '>=', '2026-10-01')
                    ->whereDate('activation_date', '<', '2026-11-01');
            })
            ->get();

        $changes = $invoices->map(function (Invoice $invoice) use ($formula, $globalTaxPpn) {
            $customer = $invoice->customer;
            $baseAmount = CustomerBillingPrice::monthly($customer);
            $amount = ProrataCalculator::firstMonthAmount($baseAmount, $customer->activation_date, $formula);
            $taxPpn = (float) ($customer->tax_ppn ?? $globalTaxPpn);

            if ($taxPpn > 0) {
                $amount = (int) round($amount * (1 + ($taxPpn / 100)));
            }

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'activation_date' => $customer->activation_date->toDateString(),
                'base_amount' => (int) round($baseAmount),
                'tax_ppn' => $taxPpn,
                'old_amount' => (int) $invoice->amount,
                'new_amount' => $amount,
            ];
        })->filter(fn (array $change) => $change['old_amount'] !== $change['new_amount'])->values();

        $this->info("Rumus aktif: {$formula}. Invoice memenuhi kriteria: {$invoices->count()}. Nominal berubah: {$changes->count()}.");
        $this->table(
            ['Invoice', 'Pelanggan', 'Aktivasi', 'Lama', 'Baru'],
            $changes->take(15)->map(fn (array $change) => [
                $change['invoice_number'],
                $change['customer_name'],
                $change['activation_date'],
                number_format($change['old_amount'], 0, ',', '.'),
                number_format($change['new_amount'], 0, ',', '.'),
            ])->all(),
        );

        if (!$this->option('apply')) {
            $this->warn('Pratinjau saja. Jalankan ulang dengan --apply untuk memperbarui invoice.');

            return self::SUCCESS;
        }

        $backupPath = 'backups/prorata-invoices-october-2026-' . now()->format('YmdHis') . '.json';
        Storage::disk('local')->put($backupPath, json_encode([
            'created_at' => now()->toIso8601String(),
            'formula' => $formula,
            'changes' => $changes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        DB::transaction(function () use ($changes) {
            foreach ($changes as $change) {
                Invoice::whereKey($change['id'])->update(['amount' => $change['new_amount']]);
            }
        });

        $this->info("{$changes->count()} invoice diperbarui. Cadangan sebelum perubahan: storage/app/{$backupPath}");

        return self::SUCCESS;
    }
}
