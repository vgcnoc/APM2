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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'customer_promise_signature')) {
                $table->longText('customer_promise_signature')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('invoices', 'collector_promise_signature')) {
                $table->longText('collector_promise_signature')->nullable()->after('customer_promise_signature');
            }
            if (!Schema::hasColumn('invoices', 'promise_signer_name')) {
                $table->string('promise_signer_name')->nullable()->after('collector_promise_signature');
            }
            if (!Schema::hasColumn('invoices', 'promise_collector_name')) {
                $table->string('promise_collector_name')->nullable()->after('promise_signer_name');
            }
            if (!Schema::hasColumn('invoices', 'promise_signed_at')) {
                $table->timestamp('promise_signed_at')->nullable()->after('promise_collector_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'customer_promise_signature',
                'collector_promise_signature',
                'promise_signer_name',
                'promise_collector_name',
                'promise_signed_at',
            ]);
        });
    }
};
