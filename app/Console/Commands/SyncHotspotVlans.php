<?php

namespace App\Console\Commands;

use App\Models\Ont;
use Illuminate\Console\Command;

class SyncHotspotVlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:sync-hotspot-vlans {--dry-run : Only display what would be updated without saving} {--force : Force sync even if not empty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan VLAN ID Hotspot & PPPoE otomatis dari konfigurasi OLT PON pada topologi jaringan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Memulai sinkronisasi VLAN ID Hotspot dari konfigurasi OLT PON...");

        $query = Ont::with(['odp.odc.olt', 'customer.surveys.odp.odc.olt']);

        if (!$force) {
            $query->where(function ($q) {
                $q->where('free_hotspot', true)
                  ->orWhereIn('access_mode', ['HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER']);
            })->where(function ($q) {
                $q->whereNull('hotspot_vlan_id')
                  ->orWhere('hotspot_vlan_id', '');
            });
        }

        $onts = $query->get();

        $this->info("Ditemukan {$onts->count()} ONT kandidat untuk sinkronisasi.");

        $updatedCount = 0;

        foreach ($onts as $ont) {
            $resolvedHsVlan = $ont->resolveHotspotVlanId();
            $resolvedPppoeVlan = $ont->resolvePppoeVlanId();

            $needsUpdate = false;
            $updates = [];

            if (!empty($resolvedHsVlan) && (empty($ont->hotspot_vlan_id) || $force)) {
                if ($ont->hotspot_vlan_id !== $resolvedHsVlan) {
                    $updates['hotspot_vlan_id'] = $resolvedHsVlan;
                    $needsUpdate = true;
                }
            }

            if (!empty($resolvedPppoeVlan) && empty($ont->vlan_id)) {
                $updates['vlan_id'] = $resolvedPppoeVlan;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $custName = $ont->customer ? $ont->customer->name . " ({$ont->customer->customer_code})" : 'Tanpa Pelanggan';
                $oltName = $ont->odp?->odc?->olt?->name ?? 'OLT Tidak Diketahui';
                $ponPort = $ont->odp?->odc?->pon_port ?? '-';

                $this->line("ONT #{$ont->id} (SN: {$ont->serial_number}) - {$custName} | OLT: {$oltName} (PON: {$ponPort}) => " . json_encode($updates));

                if (!$dryRun) {
                    $ont->update($updates);
                }
                $updatedCount++;
            }
        }

        $actionWord = $dryRun ? 'dapat disinkronkan (dry-run)' : 'berhasil disinkronkan';
        $this->info("Selesai! Sebanyak {$updatedCount} ONT {$actionWord}.");

        return 0;
    }
}

