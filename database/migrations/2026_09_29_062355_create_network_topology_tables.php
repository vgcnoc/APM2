<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create OLT PONs table
        Schema::create('olt_pons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->integer('port_number');
            $table->string('name')->nullable();
            $table->integer('capacity')->default(64);
            $table->enum('status', ['active', 'inactive', 'full', 'maintenance'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['olt_id', 'port_number']);
        });

        // 2. Add pon_id to ODCs table
        Schema::table('odcs', function (Blueprint $table) {
            $table->foreignId('pon_id')->nullable()->after('olt_id')->constrained('olt_pons')->nullOnDelete();
            $table->string('splitter_ratio')->nullable()->after('capacity')->comment('e.g. 1:4, 1:8, 1:16');
        });

        // 3. Create ODP Ports table
        Schema::create('odp_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odp_id')->constrained('odps')->cascadeOnDelete();
            $table->integer('port_number');
            $table->enum('status', ['available', 'used', 'reserved', 'fault', 'stop'])->default('available');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['odp_id', 'port_number']);
        });

        // 4. Add odp_port_id to ONTs table
        Schema::table('onts', function (Blueprint $table) {
            $table->foreignId('odp_port_id')->nullable()->after('odp_id')->constrained('odp_ports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('onts', function (Blueprint $table) {
            $table->dropForeign(['odp_port_id']);
            $table->dropColumn('odp_port_id');
        });

        Schema::dropIfExists('odp_ports');

        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['pon_id']);
            $table->dropColumn(['pon_id', 'splitter_ratio']);
        });

        Schema::dropIfExists('olt_pons');
    }
};
