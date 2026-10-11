<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('voucher_profiles', 'is_customer_free')) {
            Schema::table('voucher_profiles', function (Blueprint $table) {
                $table->boolean('is_customer_free')->default(false)->after('shared_users');
            });
        }

        if (!Schema::hasColumn('vouchers', 'customer_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('reseller_id')->constrained('customers')->nullOnDelete();
            });
        }

        // Buat atau update profil khusus hotspot gratis pelanggan jika belum ada
        $freeProfile = \App\Models\VoucherProfile::where('is_customer_free', true)->first();
        if (!$freeProfile) {
            $freeProfile = \App\Models\VoucherProfile::create([
                'name' => 'Hotspot Gratis Pelanggan',
                'price' => 0,
                'fee_admin' => 0,
                'fee_reseller' => 0,
                'fee_partner' => 0,
                'duration' => 'Mengikuti Pelanggan',
                'limit_rate' => '5M/5M',
                'shared_users' => 1,
                'description' => 'Voucher khusus gratis 1 user hotspot saat aktivasi pelanggan (masa aktif mengikuti data pelanggan)',
                'is_customer_free' => true,
            ]);
        }

        // Sinkronkan ke FreeRADIUS jika service aktif
        try {
            app(\App\Services\RadiusService::class)->syncVoucherProfile($freeProfile);
        } catch (\Throwable $e) {
            // Ignore jika koneksi radius belum aktif saat migrate lokal
        }

        // Buat data voucher untuk pelanggan yang sudah memiliki free_hotspot
        $customersWithHotspot = \App\Models\Customer::with('ont')
            ->whereHas('ont', function ($q) {
                $q->where('free_hotspot', true)->whereNotNull('hotspot_user');
            })->get();

        foreach ($customersWithHotspot as $c) {
            if ($c->ont && $c->ont->hotspot_user) {
                \App\Models\Voucher::updateOrCreate(
                    [
                        'customer_id' => $c->id,
                        'is_free' => true,
                    ],
                    [
                        'voucher_profile_id' => $freeProfile->id,
                        'code' => $c->ont->hotspot_user,
                        'username' => $c->ont->hotspot_user,
                        'password' => $c->ont->hotspot_password ?: $c->ont->hotspot_user,
                        'status' => $c->status === 'active' ? 'used' : 'expired',
                        'is_active' => $c->status === 'active',
                        'payment_method' => 'free',
                        'used_at' => $c->activation_date ?: now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('vouchers', 'customer_id')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('voucher_profiles', 'is_customer_free')) {
            Schema::table('voucher_profiles', function (Blueprint $table) {
                $table->dropColumn('is_customer_free');
            });
        }
    }
};
