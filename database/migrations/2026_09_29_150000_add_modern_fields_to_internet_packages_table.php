<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->string('color')->default('#4f46e5')->after('description');
            $table->boolean('is_promo')->default(false)->after('price');
            $table->decimal('promo_price', 15, 2)->nullable()->after('is_promo');
            $table->boolean('is_mikrotik_group_custom')->default(false)->after('color');
            $table->string('mikrotik_group')->nullable()->after('is_mikrotik_group_custom');
            $table->boolean('is_mikrotik_address_list_custom')->default(false)->after('mikrotik_group');
            $table->string('mikrotik_address_list')->nullable()->after('is_mikrotik_address_list_custom');
            $table->integer('shared_device')->default(1)->after('mikrotik_address_list');
            $table->string('rate_limit')->nullable()->after('shared_device');
            $table->integer('active_period')->default(1)->after('rate_limit');
            $table->string('active_period_unit')->default('Bulan')->after('active_period');
            $table->decimal('fee_admin', 15, 2)->default(0)->after('price');
            $table->decimal('fee_reseller', 15, 2)->default(0)->after('fee_admin');
            $table->decimal('fee_partner', 15, 2)->default(0)->after('fee_reseller');
        });
    }

    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn([
                'color', 'is_promo', 'promo_price', 'is_mikrotik_group_custom', 
                'mikrotik_group', 'is_mikrotik_address_list_custom', 'mikrotik_address_list', 
                'shared_device', 'rate_limit', 'active_period', 'active_period_unit', 
                'fee_admin', 'fee_reseller', 'fee_partner'
            ]);
        });
    }
};
