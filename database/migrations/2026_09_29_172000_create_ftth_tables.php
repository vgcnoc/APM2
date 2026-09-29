<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ftth_designs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('pic')->nullable();
            $table->string('status')->default('draft'); // draft, design, review, approved, construction, completed
            $table->decimal('total_distance', 12, 2)->default(0); // meter
            $table->decimal('slack_percentage', 5, 2)->default(5); // %
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ftth_cable_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('ftth_designs')->cascadeOnDelete();
            $table->string('start_type')->nullable(); // olt, odc, odp, customer
            $table->unsignedBigInteger('start_id')->nullable();
            $table->string('end_type')->nullable();
            $table->unsignedBigInteger('end_id')->nullable();
            $table->string('cable_type')->nullable();
            $table->integer('core_count')->default(12);
            $table->decimal('distance', 12, 2)->default(0); // meter
            $table->json('route_points')->nullable(); // [[lat,lng],...]
            $table->string('route_type')->default('planned'); // existing, planned
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('ftth_design_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('ftth_designs')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ftth_design_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('ftth_designs')->cascadeOnDelete();
            $table->string('device_type'); // olt, odc, odp, ont, tiang, closure, handhole, splitter
            $table->unsignedBigInteger('device_id')->nullable(); // FK to existing device
            $table->string('name')->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('meta')->nullable(); // extra data
            $table->string('status')->default('planned'); // existing, planned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftth_design_devices');
        Schema::dropIfExists('ftth_design_materials');
        Schema::dropIfExists('ftth_cable_routes');
        Schema::dropIfExists('ftth_designs');
    }
};
