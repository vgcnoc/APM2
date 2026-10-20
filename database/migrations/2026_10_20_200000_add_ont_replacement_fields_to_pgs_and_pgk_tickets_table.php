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
        // Add ONT replacement tracking columns to pgs_tickets
        Schema::table('pgs_tickets', function (Blueprint $table) {
            $table->string('ont_replacement_status', 30)->default('none')->after('resolution_notes');
            $table->text('requested_ont_notes')->nullable()->after('ont_replacement_status');
            $table->timestamp('requested_ont_at')->nullable()->after('requested_ont_notes');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete()->after('requested_ont_at');
            $table->foreignId('prepared_ont_id')->nullable()->constrained('onts')->nullOnDelete()->after('requested_by');
            $table->timestamp('prepared_ont_at')->nullable()->after('prepared_ont_id');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete()->after('prepared_ont_at');
            $table->foreignId('old_ont_id')->nullable()->constrained('onts')->nullOnDelete()->after('prepared_by');
            $table->string('ont_replacement_action', 30)->nullable()->after('old_ont_id');
            $table->text('ont_replacement_notes')->nullable()->after('ont_replacement_action');
        });

        // Add ONT replacement tracking columns to pgk_tickets
        Schema::table('pgk_tickets', function (Blueprint $table) {
            $table->string('ont_replacement_status', 30)->default('none')->after('resolution_notes');
            $table->text('requested_ont_notes')->nullable()->after('ont_replacement_status');
            $table->timestamp('requested_ont_at')->nullable()->after('requested_ont_notes');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete()->after('requested_ont_at');
            $table->foreignId('prepared_ont_id')->nullable()->constrained('onts')->nullOnDelete()->after('requested_by');
            $table->timestamp('prepared_ont_at')->nullable()->after('prepared_ont_id');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete()->after('prepared_ont_at');
            $table->foreignId('old_ont_id')->nullable()->constrained('onts')->nullOnDelete()->after('prepared_by');
            $table->string('ont_replacement_action', 30)->nullable()->after('old_ont_id');
            $table->text('ont_replacement_notes')->nullable()->after('ont_replacement_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pgs_tickets', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['prepared_ont_id']);
            $table->dropForeign(['prepared_by']);
            $table->dropForeign(['old_ont_id']);
            $table->dropColumn([
                'ont_replacement_status',
                'requested_ont_notes',
                'requested_ont_at',
                'requested_by',
                'prepared_ont_id',
                'prepared_ont_at',
                'prepared_by',
                'old_ont_id',
                'ont_replacement_action',
                'ont_replacement_notes',
            ]);
        });

        Schema::table('pgk_tickets', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['prepared_ont_id']);
            $table->dropForeign(['prepared_by']);
            $table->dropForeign(['old_ont_id']);
            $table->dropColumn([
                'ont_replacement_status',
                'requested_ont_notes',
                'requested_ont_at',
                'requested_by',
                'prepared_ont_id',
                'prepared_ont_at',
                'prepared_by',
                'old_ont_id',
                'ont_replacement_action',
                'ont_replacement_notes',
            ]);
        });
    }
};
