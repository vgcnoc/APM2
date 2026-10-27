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
    protected $description = 'Sinkronkan stok roll dan meter material secara otomatis dari seluruh data migrasi pelanggan per area';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Menghitung dan menyinkronkan stok material dari data migrasi...");

        $result = DataMigrationService::syncMigrationMaterialStocks();

        if (($result['status'] ?? '') === 'success') {
            $this->info("Berhasil sinkronisasi stok material migrasi:");
            $this->line("- Material: {$result['material_name']} (ID: {$result['material_id']})");
            $this->line("- Total Meter: " . number_format($result['total_meter'], 2, ',', '.') . " meter");
            $this->line("- Total Roll: " . number_format($result['total_rolls'], 2, ',', '.') . " Roll");
            $this->info("Distribusi Area:");
            foreach ($result['areas'] as $area) {
                $this->line("  * Area {$area['area_name']}: " . number_format($area['total_meter'], 2, ',', '.') . " m (~{$area['total_rolls']} Roll) dari {$area['customers_count']} pelanggan migrasi.");
            }
            return Command::SUCCESS;
        }

        $this->warn($result['message'] ?? 'Tidak ada data yang disinkronkan.');
        return Command::FAILURE;
    }
}
