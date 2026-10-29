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
        // 1. Index optimasi pada tabel customers
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'phone')) {
                $table->index('phone', 'customers_phone_idx');
            }
            if (Schema::hasColumn('customers', 'service_status')) {
                $table->index('service_status', 'customers_service_status_idx');
            }
            if (Schema::hasColumn('customers', 'status') && Schema::hasColumn('customers', 'connection_disabled')) {
                $table->index(['status', 'connection_disabled'], 'customers_status_conn_idx');
            }
            if (Schema::hasColumn('customers', 'registration_date')) {
                $table->index('registration_date', 'customers_reg_date_idx');
            }
        });

        // 2. Index optimasi pada tabel invoices
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'status') && Schema::hasColumn('invoices', 'due_date')) {
                $table->index(['status', 'due_date'], 'invoices_status_due_date_idx');
            }
            if (Schema::hasColumn('invoices', 'period_year') && Schema::hasColumn('invoices', 'period_month') && Schema::hasColumn('invoices', 'status')) {
                $table->index(['period_year', 'period_month', 'status'], 'invoices_period_status_idx');
            }
        });

        // 3. Index optimasi pada tabel odps
        Schema::table('odps', function (Blueprint $table) {
            if (Schema::hasColumn('odps', 'name')) {
                $table->index('name', 'odps_name_idx');
            }
            if (Schema::hasColumn('odps', 'status')) {
                $table->index('status', 'odps_status_idx');
            }
        });

        // 4. Index optimasi pada tabel technician_schedules
        if (Schema::hasTable('technician_schedules')) {
            Schema::table('technician_schedules', function (Blueprint $table) {
                if (Schema::hasColumn('technician_schedules', 'technician_id') && Schema::hasColumn('technician_schedules', 'type') && Schema::hasColumn('technician_schedules', 'status')) {
                    $table->index(['technician_id', 'type', 'status'], 'tech_sched_tech_type_status_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_phone_idx');
            $table->dropIndex('customers_service_status_idx');
            $table->dropIndex('customers_status_conn_idx');
            $table->dropIndex('customers_reg_date_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_due_date_idx');
            $table->dropIndex('invoices_period_status_idx');
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->dropIndex('odps_name_idx');
            $table->dropIndex('odps_status_idx');
        });

        if (Schema::hasTable('technician_schedules')) {
            Schema::table('technician_schedules', function (Blueprint $table) {
                $table->dropIndex('tech_sched_tech_type_status_idx');
            });
        }
    }
};

