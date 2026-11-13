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
    protected $signature = 'permissions:sync {--fresh : Reset ulang seluruh izin role sesuai preset standar} {--roles= : Sinkronkan ulang role tertentu ke preset standar (pisahkan koma)} {--force : Timpa paksa role yang hak aksesnya sudah dikunci/diatur manual oleh admin}';

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
        $targetRoles = [];
        if ($this->option('roles')) {
            $targetRoles = array_map('trim', explode(',', strtolower($this->option('roles'))));
            $this->info("Menyinkronkan ulang role spesifik: " . implode(', ', $targetRoles));
        }

        if ($isFresh) {
            $this->warn('Mereset ulang izin seluruh role ke template standar (--fresh)...');
        } elseif (empty($targetRoles)) {
            $this->info('Memeriksa role dan mempertahankan konfigurasi hak akses kustom admin...');
        }

        $isForce = (bool) $this->option('force');
        $rolesTable = config('permission.table_names.roles', 'roles');
        $hasLockColumn = \Illuminate\Support\Facades\Schema::hasColumn($rolesTable, 'permissions_customized_at');

        foreach ($presets as $roleName => $perms) {
            if ($roleName === 'admin') continue;

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $validPerms = Permission::whereIn('name', $perms)->pluck('name')->toArray();

            $forceSyncThisRole = in_array(strtolower($roleName), $targetRoles);
            $isLockedByAdmin = $hasLockColumn && !empty($role->permissions_customized_at);

            if (($isFresh || $forceSyncThisRole) && $isLockedByAdmin && !$isForce) {
                // Role sudah diatur manual oleh admin lewat menu Role & Akses → JANGAN DITIMPA.
                $this->warn("  ⚠ Role '{$roleName}': dilewati, hak akses sudah dikunci admin (gunakan --force untuk menimpa paksa)");
            } elseif ($isFresh || $forceSyncThisRole) {
                // Timpa seluruh izin hanya jika --fresh / --roles dipanggil sengaja pada role yang belum dikunci admin
                $role->syncPermissions($validPerms);
                $this->line("  ✓ Role '{$roleName}': disinkronkan ke template standar ({$role->permissions()->count()} izin)");
            } elseif ($role->wasRecentlyCreated) {
                // HANYA role yang benar-benar baru dibuat yang diinisialisasi dengan preset awal.
                // (Role lama yang izinnya sengaja dikosongkan admin TIDAK diisi ulang.)
                $role->syncPermissions($validPerms);
                $this->line("  ✓ Role baru '{$roleName}': diinisialisasi dengan preset awal ({$role->permissions()->count()} izin)");
            } else {
                // Role yang sudah ada dan telah disetting oleh admin: PERTAHANKAN dan JANGAN DIUBAH!
                $currentCount = $role->permissions()->count();
                $this->line("  ✓ Role '{$roleName}': izin dipertahankan sesuai konfigurasi admin ({$currentCount} izin aktif)");
            }
        }

        // Refresh cache (Spatie + versi RBAC Inertia agar sidebar langsung mengikuti DB)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        $this->info('✓ Sinkronisasi Permissions & Role Akses berhasil diselesaikan dengan sempurna!');
        return Command::SUCCESS;
    }
}
