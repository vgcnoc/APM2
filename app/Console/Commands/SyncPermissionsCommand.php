<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Support\PermissionRegistry;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh hak akses granular (menu, card, tab, filter, button, feature) ke database dan refresh cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi master permissions dari PermissionRegistry...');

        // Clear permission cache first
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allNames = PermissionRegistry::allNames();
        $createdCount = 0;
        $existingCount = 0;

        foreach ($allNames as $name) {
            $perm = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            if ($perm->wasRecentlyCreated) {
                $createdCount++;
            } else {
                $existingCount++;
            }
        }

        $this->info("Total Permissions: " . count($allNames));
        $this->info("Izin baru dibuat: {$createdCount}");
        $this->info("Izin sudah ada: {$existingCount}");

        // Ensure admin role has all permissions
        $roleAdmin = Role::where('name', 'admin')->first();
        if ($roleAdmin) {
            $roleAdmin->syncPermissions(Permission::all());
            $this->info('Role admin berhasil disinkronkan dengan semua permissions.');
        }

        // Refresh cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('✓ Sinkronisasi Permissions berhasil diselesaikan dengan sempurna!');
        return Command::SUCCESS;
    }
}
