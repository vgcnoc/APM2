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
        if (Schema::hasTable('onts')) {
            Schema::table('onts', function (Blueprint $table) {
                if (!Schema::hasColumn('onts', 'wifi_ssid')) {
                    $table->string('wifi_ssid')->nullable()->after('login_password');
                }
                if (!Schema::hasColumn('onts', 'wifi_password')) {
                    $table->string('wifi_password')->nullable()->after('wifi_ssid');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'wifi_ssid')) {
                    $table->string('wifi_ssid')->nullable()->after('referral_code');
                }
                if (!Schema::hasColumn('customers', 'wifi_password')) {
                    $table->string('wifi_password')->nullable()->after('wifi_ssid');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('onts')) {
            Schema::table('onts', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('onts', 'wifi_ssid')) $columns[] = 'wifi_ssid';
                if (Schema::hasColumn('onts', 'wifi_password')) $columns[] = 'wifi_password';
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('customers', 'wifi_ssid')) $columns[] = 'wifi_ssid';
                if (Schema::hasColumn('customers', 'wifi_password')) $columns[] = 'wifi_password';
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
