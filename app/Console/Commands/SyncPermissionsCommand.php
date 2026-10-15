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
    protected $signature = 'permissions:sync {--fresh : Reset ulang seluruh izin role sesuai preset standar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh hak akses granular (menu, card, tab, filter, button, feature) ke database, update preset role, dan refresh cache';

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

        $this->info("Total Master Permissions: " . count($allNames));
        $this->info("Izin baru dibuat: {$createdCount}");
        $this->info("Izin sudah ada: {$existingCount}");

        // Ensure admin role has all permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions(Permission::all());
        $this->info('Role admin berhasil disinkronkan dengan SEMUA permissions.');

        // Sinkronisasi preset per role
        $presets = PermissionRegistry::getDefaultRolePresets();
        $isFresh = $this->option('fresh');

        $this->info($isFresh ? 'Mereset ulang izin role ke template standar (--fresh)...' : 'Memperbarui dan melengkapi izin role dari template standar...');

        foreach ($presets as $roleName => $perms) {
            if ($roleName === 'admin') continue;

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();

            if ($isFresh) {
                $role->syncPermissions($validPerms);
            } else {
                $role->givePermissionTo($validPerms);
            }

            $currentCount = $role->permissions()->count();
            $this->line("  ✓ Role '{$roleName}': {$currentCount} izin aktif");
        }

        // Refresh cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('✓ Sinkronisasi Permissions & Role Akses berhasil diselesaikan dengan sempurna!');
        return Command::SUCCESS;
    }
}
