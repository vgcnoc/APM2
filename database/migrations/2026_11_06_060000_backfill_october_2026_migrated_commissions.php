<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\TargetMarketService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ambil seluruh pelanggan aktif per 1 Oktober 2026 yang memiliki sales_id (termasuk hasil migrasi/impor)
        $customers = Customer::where('status', 'active')
            ->whereNotNull('sales_id')
            ->whereNotIn('service_status', ['gratis', 'free_home'])
            ->where(function ($q) {
                $q->whereDate('activation_date', '>=', '2026-10-01')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('activation_date')
                          ->whereDate('registration_date', '>=', '2026-10-01');
                  });
            })
            ->get();

        foreach ($customers as $customer) {
            try {
                // Generate Fee Booking jika belum pernah dicatat
                AffiliateService::recordBookingCommission($customer);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("[Migration Backfill Booking] Cust #{$customer->id}: " . $e->getMessage());
            }

            // Generate Komisi Bulanan untuk setiap invoice yang sudah dibayar jika belum pernah dicatat
            try {
                $paidInvoices = Invoice::where('customer_id', $customer->id)
                    ->where('status', 'paid')
                    ->get();

                foreach ($paidInvoices as $invoice) {
                    AffiliateService::recordMonthlyCommission($invoice);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("[Migration Backfill Invoices] Cust #{$customer->id}: " . $e->getMessage());
            }

            // Catat progres ke Target Market Program jika ada program aktif
            try {
                TargetMarketService::processCustomerActivation($customer);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("[Migration Backfill TargetMarket] Cust #{$customer->id}: " . $e->getMessage());
            }
        }

        // 2. Sinkronisasi final total_earned di seluruh profil afiliasi
        $profiles = AffiliateProfile::all();
        foreach ($profiles as $p) {
            $commSum = (float) AffiliateCommission::where('affiliate_id', $p->user_id)
                ->whereIn('status', ['approved', 'paid', 'payroll'])
                ->sum('amount');

            if ($commSum > (float) $p->total_earned) {
                $p->total_earned = $commSum;
                $p->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe to leave untouched to prevent accidental loss of commission records
    }
};

