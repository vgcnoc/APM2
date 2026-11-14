<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbp_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('cbp_requests', 'scheduled_date')) {
                $table->date('scheduled_date')->nullable()->after('status');
                $table->index('scheduled_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cbp_requests', function (Blueprint $table) {
            if (Schema::hasColumn('cbp_requests', 'scheduled_date')) {
                $table->dropIndex(['scheduled_date']);
                $table->dropColumn('scheduled_date');
            }
        });
    }
};

