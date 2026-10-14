<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('infrastructure_work_order_technicians')) {
            Schema::create('infrastructure_work_order_technicians', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_order_id')->constrained('infrastructure_work_orders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('technician'); // 'lead_splicer', 'technician', 'helper'
                $table->timestamps();

                $table->unique(['work_order_id', 'user_id']);
            });

            // Backfill existing assigned lead technicians
            $existing = DB::table('infrastructure_work_orders')->whereNotNull('technician_id')->get();
            foreach ($existing as $wo) {
                DB::table('infrastructure_work_order_technicians')->insertOrIgnore([
                    'work_order_id' => $wo->id,
                    'user_id' => $wo->technician_id,
                    'role' => 'lead_splicer',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_work_order_technicians');
    }
};
