<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbp_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('cbp_requests', 'rescheduled_at')) {
                $table->timestamp('rescheduled_at')->nullable()->after('scheduled_date');
            }
            if (!Schema::hasColumn('cbp_requests', 'rescheduled_by')) {
                $table->foreignId('rescheduled_by')->nullable()->after('rescheduled_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('cbp_requests', 'reschedule_reason')) {
                $table->text('reschedule_reason')->nullable()->after('rescheduled_by');
            }
            if (!Schema::hasColumn('cbp_requests', 'reschedule_count')) {
                $table->integer('reschedule_count')->default(0)->after('reschedule_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cbp_requests', function (Blueprint $table) {
            if (Schema::hasColumn('cbp_requests', 'rescheduled_by')) {
                $table->dropForeign(['rescheduled_by']);
                $table->dropColumn('rescheduled_by');
            }
            $cols = [];
            foreach (['rescheduled_at', 'reschedule_reason', 'reschedule_count'] as $col) {
                if (Schema::hasColumn('cbp_requests', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

