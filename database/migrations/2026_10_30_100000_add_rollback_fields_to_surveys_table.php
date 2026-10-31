<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->timestamp('rolled_back_at')->nullable()->after('notes');
            $table->foreignId('rolled_back_by')->nullable()->after('rolled_back_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['rolled_back_by']);
            $table->dropColumn(['rolled_back_by', 'rolled_back_at']);
        });
    }
};
