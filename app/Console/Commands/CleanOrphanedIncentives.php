<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Incentive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOrphanedIncentives extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:clean-orphaned-incentives {--force : Jalankan pembersihan langsung tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pembersihan insentif otomatis yang pelanggannya sudah tidak ada di data pelanggan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pemindaian insentif otomatis tanpa pelanggan...');

        $custCodes = Customer::pluck('customer_code')->filter()->toArray();
        $custIds = Customer::pluck('id')->toArray();
        $custNames = Customer::pluck('name')->map(fn($n) => strtolower(trim($n)))->toArray();

        $this->info('Total Pelanggan Terdaftar Saat Ini: ' . count($custIds));

        $pendingAuto = Incentive::where('status', 'pending')
            ->where('type', '!=', 'manual')
            ->get();

        $toDeleteIds = [];
        $totalNominal = 0;

        foreach ($pendingAuto as $inc) {
            $matched = false;

            // 1. Cek reference_id (contoh: booking_cust_1177, survey_cust_1177, dll)
            if (!empty($inc->reference_id)) {
                if (preg_match('/cust_(\d+)/i', $inc->reference_id, $m)) {
                    if (in_array((int)$m[1], $custIds)) {
                        $matched = true;
                    }
                }
                if (!$matched) {
                    foreach ($custCodes as $code) {
                        if (str_contains($inc->reference_id, $code)) {
                            $matched = true;
                            break;
                        }
                    }
                }
            }

            // 2. Cek description untuk kode pelanggan (CUS-xxxx)
            if (!$matched && preg_match('/\((CUS-[^)]+)\)/i', $inc->description, $m)) {
                if (in_array(strtoupper($m[1]), array_map('strtoupper', $custCodes))) {
                    $matched = true;
                }
            }

            // 3. Cek nama pelanggan pada keterangan insentif
            if (!$matched) {
                $cleanName = trim(preg_replace('/^(Fee (Booking|Survey|Pasang|Aktivasi) Pelanggan:?\s*)/i', '', $inc->description));
                $cleanName = strtolower(trim(preg_replace('/\s*\(CUS-[^)]+\)/', '', $cleanName)));
                if (!empty($cleanName) && in_array($cleanName, $custNames)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $toDeleteIds[] = $inc->id;
                $totalNominal += (float) $inc->amount;
            }
        }

        $count = count($toDeleteIds);
        if ($count === 0) {
            $this->info('✓ Semua data insentif pending valid. Tidak ada data insentif yatim (orphaned).');
            return 0;
        }

        $this->warn("Ditemukan {$count} data insentif otomatis yang pelanggannya TIDAK DITEMUKAN di database.");
        $this->warn("Total Nominal: Rp " . number_format($totalNominal, 0, ',', '.'));

        if (!$this->option('force') && !$this->confirm('Apakah Anda yakin ingin menghapus data insentif ini?')) {
            $this->info('Operasi dibatalkan oleh pengguna.');
            return 0;
        }

        DB::beginTransaction();
        try {
            // Hapus dalam batch per 500 ID agar performa stabil
            foreach (array_chunk($toDeleteIds, 500) as $chunk) {
                Incentive::whereIn('id', $chunk)->delete();
            }

            DB::commit();
            $this->info("✓ Sukses menghapus {$count} data insentif tanpa pelanggan senilai Rp " . number_format($totalNominal, 0, ',', '.') . ".");
            Log::info("[CleanOrphanedIncentives] Berhasil menghapus {$count} insentif tanpa data pelanggan senilai Rp {$totalNominal}");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal menghapus data insentif: ' . $e->getMessage());
            Log::error('[CleanOrphanedIncentives] Gagal menghapus insentif: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
