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
        Schema::table('affiliate_withdrawals', function (Blueprint $table) {
            $table->string('disbursement_channel', 50)->default('manual')->after('status');
            $table->string('disbursement_id', 100)->nullable()->after('disbursement_channel');
            $table->string('disbursement_reference', 100)->nullable()->after('disbursement_id');
            $table->string('disbursement_status', 50)->nullable()->after('disbursement_reference');
            $table->text('disbursement_error')->nullable()->after('disbursement_status');
            $table->longText('disbursement_payload')->nullable()->after('disbursement_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'disbursement_channel',
                'disbursement_id',
                'disbursement_reference',
                'disbursement_status',
                'disbursement_error',
                'disbursement_payload',
            ]);
        });
    }
};
