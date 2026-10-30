<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah replaced_materials ke network_maintenances jika belum ada
        if (Schema::hasTable('network_maintenances') && !Schema::hasColumn('network_maintenances', 'replaced_materials')) {
            Schema::table('network_maintenances', function (Blueprint $table) {
                $table->json('replaced_materials')->nullable()->after('description');
            });
        }

        // 2. Modifikasi material_transaction_items agar material_id nullable dan ada material_name & notes
        if (Schema::hasTable('material_transaction_items')) {
            Schema::table('material_transaction_items', function (Blueprint $table) {
                if (!Schema::hasColumn('material_transaction_items', 'material_name')) {
                    $table->string('material_name')->nullable()->after('material_id');
                }
                if (!Schema::hasColumn('material_transaction_items', 'notes')) {
                    $table->text('notes')->nullable()->after('condition');
                }
            });

            // Ubah foreign key / nullable pada material_id
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `material_transaction_items` MODIFY `material_id` BIGINT UNSIGNED NULL;");
            } catch (\Throwable $e) {
                // Abaikan jika sudah nullable atau driver sqlite
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('network_maintenances') && Schema::hasColumn('network_maintenances', 'replaced_materials')) {
            Schema::table('network_maintenances', function (Blueprint $table) {
                $table->dropColumn('replaced_materials');
            });
        }

        if (Schema::hasTable('material_transaction_items')) {
            Schema::table('material_transaction_items', function (Blueprint $table) {
                if (Schema::hasColumn('material_transaction_items', 'material_name')) {
                    $table->dropColumn('material_name');
                }
                if (Schema::hasColumn('material_transaction_items', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }
    }
};

