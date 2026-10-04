<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SuspendOverdueCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:suspend-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis isolir / suspend pelanggan yang memiliki tagihan belum dibayar lewat dari jatuh tempo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mengecek pelanggan yang telat bayar (Isolir)...');
        
        $today = now()->startOfDay();

        // Cari pelanggan aktif yang memiliki invoice status "unpaid" dan jatuh temponya sudah lewat
        $overdueCustomers = Customer::where('status', 'active')
            ->whereHas('invoices', function ($query) use ($today) {
                $query->where('status', 'unpaid')
                      ->where('due_date', '<', $today);
            })
            ->get();

        if ($overdueCustomers->isEmpty()) {
            $this->info('Tidak ada pelanggan yang harus di-isolir hari ini.');
            return;
        }

        $count = 0;
        foreach ($overdueCustomers as $customer) {
            try {
                // Update status ke suspended.
                // Ini akan trigger CustomerObserver yang akan otomatis:
                // 1. Memindahkan user ke group "isolir" di database RADIUS.
                // 2. Mengirim command CoA disconnect ke NAS (Router) jika pelanggan sedang online.
                $customer->update(['status' => 'suspended']);
                
                $this->line("Pelanggan {$customer->name} ({$customer->customer_code}) berhasil di-isolir.");
                Log::info("[BILLING] Pelanggan {$customer->customer_code} otomatis di-isolir karena telat bayar.");
                
                $count++;
            } catch (\Exception $e) {
                $this->error("Gagal isolir pelanggan {$customer->customer_code}: " . $e->getMessage());
                Log::error("[BILLING] Gagal otomatis isolir {$customer->customer_code}: " . $e->getMessage());
            }
        }

        $this->info("Proses selesai. Sebanyak {$count} pelanggan berhasil di-isolir.");
    }
}
