<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reseller;
use App\Models\ResellerTransaction;
use App\Models\ResellerBalanceRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Incentive;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanResellerOrphans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:clean-orphans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pembersihan cerdas data mutasi, permohonan saldo, tagihan kasbon, dan voucher reseller yang terputus / tanpa relasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan cerdas data yatim (orphaned) reseller & keuangan...');

        DB::beginTransaction();
        try {
            // 1. Bersihkan transaksi mutasi saldo tanpa reseller
            $orphanTxCount = ResellerTransaction::whereDoesntHave('reseller')->count();
            if ($orphanTxCount > 0) {
                ResellerTransaction::whereDoesntHave('reseller')->delete();
                $this->info("✓ Dihapus {$orphanTxCount} transaksi mutasi saldo tanpa reseller.");
            } else {
                $this->info("✓ Transaksi mutasi saldo bersih (0 data yatim).");
            }

            // 2. Bersihkan permohonan saldo tanpa reseller
            $orphanReqCount = ResellerBalanceRequest::whereDoesntHave('reseller')->count();
            if ($orphanReqCount > 0) {
                ResellerBalanceRequest::whereDoesntHave('reseller')->delete();
                $this->info("✓ Dihapus {$orphanReqCount} permohonan saldo tanpa reseller.");
            } else {
                $this->info("✓ Permohonan saldo bersih (0 data yatim).");
            }

            // 3. Bersihkan tagihan kasbon (is_reseller_balance = true) yang pelanggannya sudah tidak ada atau bukan reseller lagi
            $orphanInvoices = Invoice::where('is_reseller_balance', true)
                ->where(function ($q) {
                    $q->whereDoesntHave('customer')
                      ->orWhereHas('customer', fn ($c) => $c->where('is_reseller', false));
                })->get();

            $invCount = 0;
            foreach ($orphanInvoices as $inv) {
                foreach ($inv->payments as $payment) {
                    Incentive::where('reference_id', 'payment_' . $payment->id)->delete();
                    $payment->delete();
                }
                $inv->delete();
                $invCount++;
            }

            if ($invCount > 0) {
                $this->info("✓ Dihapus {$invCount} tagihan kasbon tanpa pelanggan/reseller valid beserta pembayarannya.");
            } else {
                $this->info("✓ Tagihan kasbon reseller bersih (0 data yatim).");
            }

            // 4. Bersihkan pembayaran kasbon yang invoice-nya sudah tidak ada
            $orphanPayments = Payment::whereNull('invoice_id')
                ->orWhereDoesntHave('invoice')
                ->where('notes', 'like', '%Kasbon%')
                ->get();

            $payCount = 0;
            foreach ($orphanPayments as $p) {
                Incentive::where('reference_id', 'payment_' . $p->id)->delete();
                $p->delete();
                $payCount++;
            }
            if ($payCount > 0) {
                $this->info("✓ Dihapus {$payCount} pembayaran kasbon yatim.");
            }

            // 5. Bersihkan voucher yang terikat reseller_id yang sudah tidak ada
            $orphanVouchers = Voucher::whereNotNull('reseller_id')
                ->whereDoesntHave('reseller')
                ->delete();
            if ($orphanVouchers > 0) {
                $this->info("✓ Dihapus {$orphanVouchers} voucher tanpa reseller valid.");
            } else {
                $this->info("✓ Data voucher reseller bersih (0 data yatim).");
            }

            DB::commit();
            $this->info('✓ Pembersihan cerdas selesai dengan sukses!');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal membersihkan data: ' . $e->getMessage());
            Log::error('[CleanResellerOrphans] ' . $e->getMessage());
            return 1;
        }
    }
}
