<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Customer;
use App\Models\Incentive;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bersihkan insentif yang terlanjur terbentuk prematur untuk pelanggan yang belum aktif (masih tahap booking / survey / installing).
     * Seluruh insentif (Booking, Survey, Pasang) akan dibentuk serentak saat tombol aktivasi ditekan.
     */
    public function up(): void
    {
        $nonActiveCustomers = Customer::where('status', '!=', 'active')->get();
        $deletedCount = 0;

        foreach ($nonActiveCustomers as $customer) {
            $deleted = Incentive::where('status', 'pending')
                ->where(function ($q) use ($customer) {
                    $q->where('reference_id', 'like', 'booking_cust_' . $customer->id)
                      ->orWhere('reference_id', 'like', 'survey_cust_' . $customer->id . '_%')
                      ->orWhere('reference_id', 'like', 'install_cust_' . $customer->id . '_%')
                      ->orWhere(function ($sub) use ($customer) {
                          $sub->where('type', 'auto')
                              ->where(function ($desc) use ($customer) {
                                  $desc->where('description', 'like', 'Fee Booking Pelanggan: ' . $customer->name . '%')
                                       ->orWhere('description', 'like', 'Fee Survey Pelanggan: ' . $customer->name . '%')
                                       ->orWhere('description', 'like', 'Fee Pasang Pelanggan: ' . $customer->name . '%');
                              });
                      });
                })
                ->delete();

            $deletedCount += $deleted;
        }

        Log::info("[Migration] Cleaned up {$deletedCount} premature incentives for non-active customers.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};

