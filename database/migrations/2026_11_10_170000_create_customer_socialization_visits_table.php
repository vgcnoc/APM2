<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_socialization_visits')) {
            Schema::create('customer_socialization_visits', function (Blueprint $table) {
                $table->id();
                $table->string('visit_number', 50)->unique();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
                $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
                $table->string('purpose_category', 60)->default('gangguan_berulang');
                $table->string('title', 255);
                $table->text('issue_summary')->nullable();
                $table->text('admin_notes')->nullable();
                $table->date('scheduled_date');
                $table->string('scheduled_time', 20)->nullable();
                $table->string('priority', 20)->default('normal');
                $table->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, rescheduled, cancelled
                $table->string('start_time', 20)->nullable();
                $table->string('end_time', 20)->nullable();
                $table->string('met_with_name', 150)->nullable();
                $table->string('met_with_relation', 100)->nullable();
                $table->string('customer_response', 60)->nullable(); // puas_memahami, minta_pantauan, masih_keluhan, butuh_ganti_perangkat
                $table->json('socialization_points')->nullable();
                $table->text('report_notes')->nullable();
                $table->text('follow_up_action')->nullable();
                $table->text('photo_visit')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'status']);
                $table->index(['scheduled_date', 'status']);
                $table->index('area_id');
            });
        }

        if (!Schema::hasTable('customer_socialization_visit_user')) {
            Schema::create('customer_socialization_visit_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained('customer_socialization_visits')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->nullable();
                $table->timestamps();

                $table->unique(['visit_id', 'user_id'], 'csv_visit_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_socialization_visit_user');
        Schema::dropIfExists('customer_socialization_visits');
    }
};

