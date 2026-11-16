<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Artisan::call('cbp:sync-ports');
            Log::info('[Migration] cbp:sync-ports executed successfully: ' . Artisan::output());
        } catch (\Throwable $e) {
            Log::warning('[Migration] cbp:sync-ports encountered an issue: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback necessary as this only synchronizes port availability
    }
};
