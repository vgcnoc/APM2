<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncMigratedPackagePrices extends Command
{
    protected $signature = 'app:sync-migrated-package-prices
                            {--apply : Terapkan tarif paket ke data pelanggan hasil migrasi}';

    protected $description = 'Selaraskan tarif dasar pelanggan hasil migrasi dengan Paket Langganan & Tarif';

    public function handle(): int
    {
        $customers = Customer::query()
            ->with('package')
            ->where('is_migrated', true)
            ->whereNotNull('package_id')
            ->whereNotIn('service_status', ['gratis', 'free_home'])
            ->get();

        $changes = $customers
            ->filter(fn (Customer $customer) => $customer->package
                && (int) $customer->base_amount !== (int) $customer->package->current_price)
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'customer_name' => $customer->name,
                'package_id' => $customer->package->id,
                'package_name' => $customer->package->name,
                'old_base_amount' => (int) $customer->base_amount,
                'new_base_amount' => (int) $customer->package->current_price,
            ])
            ->values();

        $this->info("Pelanggan migrasi dengan paket: {$customers->count()}. Tarif perlu diselaraskan: {$changes->count()}.");
        $this->table(
            ['Pelanggan', 'Paket', 'Tarif Lama', 'Tarif Paket'],
            $changes->take(15)->map(fn (array $change) => [
                $change['customer_name'],
                $change['package_name'],
                number_format($change['old_base_amount'], 0, ',', '.'),
                number_format($change['new_base_amount'], 0, ',', '.'),
            ])->all(),
        );

        if (!$this->option('apply')) {
            $this->warn('Pratinjau saja. Jalankan ulang dengan --apply untuk menyimpan perubahan.');

            return self::SUCCESS;
        }

        $backupPath = 'backups/migrated-customer-package-prices-' . now()->format('YmdHis') . '.json';
        Storage::disk('local')->put($backupPath, json_encode([
            'created_at' => now()->toIso8601String(),
            'changes' => $changes,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        DB::transaction(function () use ($changes): void {
            foreach ($changes as $change) {
                Customer::whereKey($change['id'])->update(['base_amount' => $change['new_base_amount']]);
            }
        });

        $this->info("{$changes->count()} tarif pelanggan diperbarui. Cadangan sebelum perubahan: storage/app/{$backupPath}");

        return self::SUCCESS;
    }
}
