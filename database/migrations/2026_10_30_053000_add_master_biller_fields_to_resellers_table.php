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
        Schema::table('resellers', function (Blueprint $table) {
            $table->string('tier')->default('regular')->after('is_active'); // 'regular', 'master'
            $table->foreignId('parent_id')->nullable()->after('tier')->constrained('resellers')->nullOnDelete();
            $table->string('referral_code', 30)->nullable()->unique()->after('parent_id');
            $table->decimal('override_fee', 12, 2)->nullable()->after('referral_code'); // Royalti kustom (null = ikuti default setting)
            $table->string('status', 30)->default('active')->after('override_fee'); // 'active', 'pending'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['tier', 'parent_id', 'referral_code', 'override_fee', 'status']);
        });
    }
};

