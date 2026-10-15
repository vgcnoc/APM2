<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('disbursement_channel')->default('manual')->after('status'); // 'manual', 'xendit'
            $table->string('disbursement_id')->nullable()->after('disbursement_channel');
            $table->string('disbursement_reference')->nullable()->after('disbursement_id');
            $table->string('disbursement_status')->nullable()->after('disbursement_reference'); // 'PENDING', 'COMPLETED', 'FAILED'
            $table->string('disbursement_bank')->nullable()->after('disbursement_status');
            $table->string('disbursement_account_number')->nullable()->after('disbursement_bank');
            $table->text('disbursement_error')->nullable()->after('disbursement_account_number');
            $table->json('disbursement_payload')->nullable()->after('disbursement_error');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'disbursement_channel',
                'disbursement_id',
                'disbursement_reference',
                'disbursement_status',
                'disbursement_bank',
                'disbursement_account_number',
                'disbursement_error',
                'disbursement_payload',
            ]);
        });
    }
};
