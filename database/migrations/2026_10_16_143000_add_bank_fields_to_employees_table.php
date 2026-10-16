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
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'bank_name')) {
                    $table->string('bank_name')->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('employees', 'bank_account_number')) {
                    $table->string('bank_account_number')->nullable()->after('bank_name');
                }
                if (!Schema::hasColumn('employees', 'bank_account_holder')) {
                    $table->string('bank_account_holder')->nullable()->after('bank_account_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('employees', 'bank_name')) {
                    $columns[] = 'bank_name';
                }
                if (Schema::hasColumn('employees', 'bank_account_number')) {
                    $columns[] = 'bank_account_number';
                }
                if (Schema::hasColumn('employees', 'bank_account_holder')) {
                    $columns[] = 'bank_account_holder';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
