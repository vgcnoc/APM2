<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Income;
use App\Models\IncomeCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PsbPaymentService
{
    /**
     * Catat pembayaran PSB lunas ke pelanggan dan otomatis masukkan ke Buku Kas Umum (incomes)
     */
    public static function recordPayment(Customer $customer, array $options = []): ?Income
    {
        $fee = (float) ($customer->installation_fee ?? 0);
        
        // Jika biaya pasang 0 atau gratis
        if ($fee <= 0) {
            $customer->update([
                'psb_payment_status' => 'free',
                'installation_fee' => 0,
                'psb_paid_at' => null,
                'psb_payment_method' => null,
                'psb_received_by' => null,
            ]);
            return null;
        }

        return DB::transaction(function () use ($customer, $fee, $options) {
            $paidAt = !empty($options['paid_at']) ? Carbon::parse($options['paid_at']) : Carbon::now();
            $paymentMethod = $options['payment_method'] ?? 'cash';
            $receivedBy = $options['received_by'] ?? auth()->id();
            $notes = $options['notes'] ?? null;

            // 1. Dapatkan atau buat kategori PSB di master kategori pemasukan
            $category = IncomeCategory::firstOrCreate(
                ['code' => 'PSB'],
                [
                    'name' => 'Biaya Pasang Baru (PSB)',
                    'description' => 'Pemasukan dari biaya registrasi & pemasangan baru pelanggan internet',
                    'color' => 'emerald',
                    'is_active' => true,
                ]
            );

            // 2. Buat atau perbarui catatan mutasi kas di tabel incomes
            $refNo = 'INV-PSB-' . ($customer->customer_code ?: 'CUS-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT));
            $desc = 'Biaya Pasang Baru (PSB): ' . $customer->name . ' (' . ($customer->customer_code ?: 'CUS-' . $customer->id) . ')'
                . ($notes ? ' - ' . $notes : '');

            $incomeData = [
                'customer_id' => $customer->id,
                'amount' => (int) round($fee),
                'income_category_id' => $category->id,
                'area_id' => $customer->area_id,
                'area' => $customer->area,
                'source' => $customer->name . ' (' . ($customer->customer_code ?: 'CUS-' . $customer->id) . ')',
                'description' => $desc,
                'income_date' => $paidAt->toDateString(),
                'payment_method' => $paymentMethod,
                'reference_no' => $refNo,
                'user_id' => $receivedBy,
            ];

            $income = null;
            if (!empty($customer->psb_income_id)) {
                $income = Income::find($customer->psb_income_id);
            }

            if ($income) {
                $income->update($incomeData);
            } else {
                $income = Income::create($incomeData);
            }

            // 3. Perbarui status pelanggan menjadi lunas
            $customer->update([
                'psb_payment_status' => 'paid',
                'psb_paid_at' => $paidAt,
                'psb_payment_method' => $paymentMethod,
                'psb_received_by' => $receivedBy,
                'psb_income_id' => $income->id,
                'psb_notes' => $notes ?: $customer->psb_notes,
            ]);

            Log::info("[PsbPayment] Pembayaran PSB Pelanggan #{$customer->id} ({$customer->name}) sebesar Rp {$fee} berhasil dicatat ke Kas (Income #{$income->id})");

            return $income;
        });
    }

    /**
     * Tandai PSB belum lunas (menunggak) dan hapus mutasi kas jika sebelumnya pernah tercatat
     */
    public static function markUnpaid(Customer $customer, ?string $notes = null): void
    {
        DB::transaction(function () use ($customer, $notes) {
            // Jika ada mutasi kas masuk PSB yang tertaut, batalkan/hapus dari kas
            if (!empty($customer->psb_income_id)) {
                Income::where('id', $customer->psb_income_id)->delete();
            }

            $customer->update([
                'psb_payment_status' => 'unpaid',
                'psb_paid_at' => null,
                'psb_payment_method' => null,
                'psb_received_by' => null,
                'psb_income_id' => null,
                'psb_notes' => $notes ?: $customer->psb_notes,
            ]);

            Log::info("[PsbPayment] PSB Pelanggan #{$customer->id} ({$customer->name}) ditandai BELUM LUNAS (Piutang PSB)");
        });
    }

    /**
     * Tandai PSB bebas biaya (promo / gratis)
     */
    public static function markFree(Customer $customer, ?string $notes = null): void
    {
        DB::transaction(function () use ($customer, $notes) {
            if (!empty($customer->psb_income_id)) {
                Income::where('id', $customer->psb_income_id)->delete();
            }

            $customer->update([
                'psb_payment_status' => 'free',
                'installation_fee' => 0,
                'psb_paid_at' => null,
                'psb_payment_method' => null,
                'psb_received_by' => null,
                'psb_income_id' => null,
                'psb_notes' => $notes ?: $customer->psb_notes,
            ]);

            Log::info("[PsbPayment] PSB Pelanggan #{$customer->id} ({$customer->name}) ditandai BEBAS BIAYA (Gratis Promo)");
        });
    }
}
