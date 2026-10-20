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
        Schema::table('material_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('material_transactions', 'source_type')) {
                $table->string('source_type', 40)->nullable()->after('purpose')->index();
            }
            if (!Schema::hasColumn('material_transactions', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
            }
            if (!Schema::hasColumn('material_transactions', 'source_reference')) {
                $table->string('source_reference', 100)->nullable()->after('source_id')->index();
            }
            if (!Schema::hasColumn('material_transactions', 'handover_status')) {
                $table->string('handover_status', 30)->default('none')->after('status')->index();
            }
            if (!Schema::hasColumn('material_transactions', 'received_by')) {
                $table->foreignId('received_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('material_transactions', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by');
            }
            if (!Schema::hasColumn('material_transactions', 'received_notes')) {
                $table->text('received_notes')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('material_transactions', 'request_type')) {
                $table->string('request_type', 30)->default('regular')->after('handover_status')->index();
            }
            if (!Schema::hasColumn('material_transactions', 'old_ont_id')) {
                $table->foreignId('old_ont_id')->nullable()->after('request_type')->constrained('onts')->nullOnDelete();
            }
            if (!Schema::hasColumn('material_transactions', 'replacement_ont_id')) {
                $table->foreignId('replacement_ont_id')->nullable()->after('old_ont_id')->constrained('onts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_transactions', function (Blueprint $table) {
            $colsToDrop = [];
            if (Schema::hasColumn('material_transactions', 'received_by')) {
                $table->dropForeign(['received_by']);
                $colsToDrop[] = 'received_by';
            }
            if (Schema::hasColumn('material_transactions', 'old_ont_id')) {
                $table->dropForeign(['old_ont_id']);
                $colsToDrop[] = 'old_ont_id';
            }
            if (Schema::hasColumn('material_transactions', 'replacement_ont_id')) {
                $table->dropForeign(['replacement_ont_id']);
                $colsToDrop[] = 'replacement_ont_id';
            }
            foreach (['source_type', 'source_id', 'source_reference', 'handover_status', 'received_at', 'received_notes', 'request_type'] as $c) {
                if (Schema::hasColumn('material_transactions', $c)) {
                    $colsToDrop[] = $c;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }
};
