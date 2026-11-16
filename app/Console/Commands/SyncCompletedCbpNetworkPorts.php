<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CbpRequest;
use App\Models\Customer;
use App\Models\Ont;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Survey;

class SyncCompletedCbpNetworkPorts extends Command
{
    protected $signature = 'cbp:sync-ports';
    protected $description = 'Bebaskan port ODP dan lepas ONT untuk seluruh pelanggan yang tiket CBP-nya sudah Selesai (Completed) atau Stop Permanen';

    public function handle()
    {
        $this->info('Memulai sinkronisasi dan pelepasan port ODP untuk tiket CBP yang sudah selesai...');

        $affectedOdpIds = collect();
        $freedOntCount = 0;
        $freedPortCount = 0;

        // 1. Tangani tiket CBP yang sudah berstatus 'completed'
        $completedCbps = CbpRequest::where('status', 'completed')
            ->whereNotNull('customer_id')
            ->with(['customer.ont'])
            ->get();

        foreach ($completedCbps as $cbp) {
            $customer = $cbp->customer;
            $customerOnts = Ont::where('customer_id', $cbp->customer_id)
                ->where(function ($q) {
                    $q->whereNotNull('odp_id')
                      ->orWhereNotNull('odp_port_id')
                      ->orWhereNotNull('port_number');
                })
                ->get();

            foreach ($customerOnts as $ont) {
                if ($ont->odp_id) {
                    $affectedOdpIds->push($ont->odp_id);
                }

                // Bebaskan OdpPort
                if ($ont->odp_port_id) {
                    OdpPort::where('id', $ont->odp_port_id)->update([
                        'status' => 'available',
                        'description' => null,
                    ]);
                    $freedPortCount++;
                }
                if ($ont->odp_id && $ont->port_number) {
                    OdpPort::where('odp_id', $ont->odp_id)
                        ->where('port_number', $ont->port_number)
                        ->update([
                            'status' => 'available',
                            'description' => null,
                        ]);
                    $freedPortCount++;
                }

                // Lepas ONT ke gudang
                $ont->update([
                    'customer_id' => null,
                    'odp_id' => null,
                    'odp_port_id' => null,
                    'port_number' => null,
                    'rx_power' => null,
                    'status' => 'Belum Set/Baru Input',
                    'description' => "Auto-sync pelepasan port dari CBP #{$cbp->cbp_number} tgl " . now()->format('d/m/Y H:i'),
                ]);
                $freedOntCount++;
            }

            // Bebaskan juga survey port jika masih tersangkut
            $surveys = Survey::where('customer_id', $cbp->customer_id)->whereNotNull('odp_id')->get();
            foreach ($surveys as $survey) {
                if ($survey->odp_id) {
                    $affectedOdpIds->push($survey->odp_id);
                    if (!empty($survey->port_number)) {
                        OdpPort::where('odp_id', $survey->odp_id)
                            ->where('port_number', $survey->port_number)
                            ->update(['status' => 'available', 'description' => null]);
                        $freedPortCount++;
                    }
                }
            }
        }

        // 2. Tangani pelanggan berstatus 'terminated' yang tidak punya tiket CBP aktif namun ONT masih menempel di ODP
        $terminatedCustomers = Customer::where('status', 'terminated')
            ->whereHas('ont', function ($q) {
                $q->whereNotNull('odp_id');
            })
            ->with(['ont'])
            ->get();

        foreach ($terminatedCustomers as $cust) {
            // Cek apakah ada tiket CBP terbuka (pending/assigned). Jika tidak ada atau sudah completed, lepaskan!
            $hasActiveCbp = CbpRequest::where('customer_id', $cust->id)
                ->whereIn('status', ['pending', 'assigned'])
                ->exists();

            if (!$hasActiveCbp && $cust->ont) {
                $ont = $cust->ont;
                if ($ont->odp_id) {
                    $affectedOdpIds->push($ont->odp_id);
                }

                if ($ont->odp_port_id) {
                    OdpPort::where('id', $ont->odp_port_id)->update(['status' => 'available', 'description' => null]);
                    $freedPortCount++;
                }
                if ($ont->odp_id && $ont->port_number) {
                    OdpPort::where('odp_id', $ont->odp_id)
                        ->where('port_number', $ont->port_number)
                        ->update(['status' => 'available', 'description' => null]);
                    $freedPortCount++;
                }

                $ont->update([
                    'customer_id' => null,
                    'odp_id' => null,
                    'odp_port_id' => null,
                    'port_number' => null,
                    'rx_power' => null,
                    'status' => 'Belum Set/Baru Input',
                    'description' => "Auto-sync pelepasan port pelanggan terminated {$cust->name} ({$cust->customer_code}) tgl " . now()->format('d/m/Y H:i'),
                ]);
                $freedOntCount++;
            }
        }

        // 3. Bersihkan port hantu (status 'used' tapi tidak ada ONT atau Survey aktif yang memegang)
        $ghostPortsUpdated = OdpPort::where('status', 'used')
            ->whereDoesntHave('ont')
            ->whereDoesntHave('survey', function ($sq) {
                $sq->whereHas('customer', fn ($cq) => $cq->whereIn('status', ['survey', 'installing']));
            })
            ->update([
                'status' => 'available',
                'description' => null,
            ]);

        // 4. Sinkronisasi total used_ports & status untuk seluruh ODP
        $allOdps = Odp::all();
        $odpUpdatedCount = 0;
        foreach ($allOdps as $odp) {
            $usedCount = Ont::where('odp_id', $odp->id)
                ->whereNotIn('status', ['in_stock', 'damaged', 'maintenance', 'returned'])
                ->count();
            $newStatus = ($odp->total_ports > 0 && $usedCount >= $odp->total_ports) ? 'full' : 'active';
            if ($odp->used_ports !== $usedCount || $odp->status !== $newStatus) {
                $odp->update([
                    'used_ports' => $usedCount,
                    'status' => $newStatus,
                ]);
                $odpUpdatedCount++;
            }
        }

        $this->info("Sinkronisasi Selesai!");
        $this->line("- ONT dilepas ke gudang : {$freedOntCount}");
        $this->line("- Port ODP dibebaskan   : {$freedPortCount}");
        $this->line("- Port hantu dinetralkan: {$ghostPortsUpdated}");
        $this->line("- ODP dihitung ulang    : {$odpUpdatedCount} dari {$allOdps->count()} ODP");

        return 0;
    }
}
