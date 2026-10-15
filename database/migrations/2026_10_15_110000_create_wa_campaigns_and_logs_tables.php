<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('target_type')->default('all_customers');
            $table->foreignId('target_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->text('message');
            $table->string('media_url')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->string('status')->default('completed'); // draft, processing, completed, canceled
            $table->integer('delay_seconds')->default(5);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('wa_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_campaign_id')->nullable()->constrained('wa_campaigns')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_type')->default('customer');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->text('message_sent');
            $table->string('status')->default('sent'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_logs');
        Schema::dropIfExists('wa_campaigns');
    }
};
