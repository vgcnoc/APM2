<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected function schema()
    {
        return Schema::connection(config('radius.connection', 'radius'));
    }

    public function up(): void
    {
        $schema = $this->schema();

        if ($schema->hasTable('nas') && !$schema->hasColumn('nas', 'company_id')) {
            $schema->table('nas', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->default(1)->after('id')->index();
            });
        }

        // Sync company_id from routers table
        try {
            DB::connection(config('radius.connection', 'radius'))->statement("
                UPDATE nas n
                JOIN routers r ON r.nas_id = n.id
                SET n.company_id = r.company_id
                WHERE r.company_id IS NOT NULL
            ");
        } catch (\Throwable $e) {
            // Silently ignore if table routers is not accessible in this connection
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        if ($schema->hasTable('nas') && $schema->hasColumn('nas', 'company_id')) {
            $schema->table('nas', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }
};
