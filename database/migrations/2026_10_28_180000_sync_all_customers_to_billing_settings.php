<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $billingType = Setting::get('billing_type', 'pascabayar');
        Customer::query()->update(['billing_type' => $billingType]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

