<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataMigrationService;

class SyncMigrationStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migration:sync-stocks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan stok kabel (roll/meter) dan modem ONT secara otomatis dari seluruh data migrasi pelanggan per area ke gudang & logistik';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Menghitung dan menyinkronkan stok material (kabel & modem ONT) dari data migrasi...");

        $result = DataMigrationService::syncMigrationMaterialStocks();

        if (($result['status'] ?? '') === 'success') {
            $this->info("\n=== 1. HASIL SINKRONISASI KABEL DROPCORE ===");
            $cable = $result['cable'] ?? [];
            if (($cable['status'] ?? '') === 'success') {
                $this->line("- Material: {$cable['material_name']} (ID: {$cable['material_id']})");
                $this->line("- Total Meter: " . number_format($cable['total_meter'], 2, ',', '.') . " meter");
                $this->line("- Total Roll: " . number_format($cable['total_rolls'], 2, ',', '.') . " Roll");
                $this->info("Distribusi Area Kabel:");
                foreach ($cable['areas'] as $area) {
                    $this->line("  * Area {$area['area_name']}: " . number_format($area['total_meter'], 2, ',', '.') . " m (~{$area['total_rolls']} Roll) dari {$area['customers_count']} pelanggan migrasi.");
                }
            } else {
                $this->warn("  " . ($cable['message'] ?? 'Tidak ada data kabel migrasi.'));
            }

            $this->info("\n=== 2. HASIL SINKRONISASI MODEM ONT ===");
            $ont = $result['ont'] ?? [];
            if (($ont['status'] ?? '') === 'success') {
                $this->line("- Total Perangkat ONT: {$ont['total_onts']} unit");
                $this->line("- Jumlah Model Material Unik: {$ont['distinct_models']} model");
                $this->info("Distribusi Area ONT:");
                foreach ($ont['areas'] as $area) {
                    $this->line("  * Area {$area['area_name']}: {$area['total_units']} unit ONT ({$area['distinct_models']} tipe model).");
                }
                $this->info("Daftar Material ONT Terdaftar:");
                foreach (array_slice($ont['materials'], 0, 10) as $mat) {
                    $this->line("  * {$mat['name']}: {$mat['stock']} unit");
                }
                if (count($ont['materials']) > 10) {
                    $this->line("  * ... dan " . (count($ont['materials']) - 10) . " model ONT lainnya.");
                }
            } else {
                $this->warn("  " . ($ont['message'] ?? 'Tidak ada data ONT migrasi.'));
            }

            $this->info("\nSemua stok berhasil disinkronkan ke Material/Logistik, Stok Gudang per Area, dan Riwayat Transaksi!");
            return Command::SUCCESS;
        }

        $this->warn($result['message'] ?? 'Tidak ada data yang disinkronkan.');
        return Command::FAILURE;
    }
}
