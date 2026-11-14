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

        // ── PENGAMAN ATURAN BISNIS RBAC & HARMONISASI KHUSUS ─────────
        // 1. Role teknisi & teknisi-2: Bersihkan menu OSP/Kapasitas & Master Paket jika masih tersisa
        $forbiddenTeknisiPerms = [
            'menu_network_capacity', 'network_capacity_card_stats', 'network_capacity_tab_odp',
            'network_capacity_tab_odc', 'network_capacity_tab_expansion', 'network_capacity_filter',
            'network_capacity_view_area', 'menu_network_olt', 'menu_network_odc', 'menu_network_odp',
            'menu_network_ont', 'menu_network_find_onu', 'menu_infrastructure_work_orders',
            'menu_internet_packages'
        ];

        $roleTeknisi = Role::where('name', 'teknisi')->first();
        if ($roleTeknisi) {
            $existingForbidden = $roleTeknisi->permissions()->whereIn('name', $forbiddenTeknisiPerms)->pluck('name')->toArray();
            if (!empty($existingForbidden)) {
                $roleTeknisi->revokePermissionTo($existingForbidden);
                $this->line("  ✓ Role 'teknisi': dibersihkan dari " . count($existingForbidden) . " hak akses OSP/Paket yang tidak diizinkan.");
            }
        }

        // 2. Samakan role teknisi-2 persis dengan teknisi (Yahya Maulana)
        $roleTeknisi2 = Role::where('name', 'teknisi-2')->first();
        if ($roleTeknisi && $roleTeknisi2) {
            $teknisiPerms = $roleTeknisi->permissions->pluck('name')->toArray();
            $roleTeknisi2->syncPermissions($teknisiPerms);
            if ($hasLockColumn) {
                $roleTeknisi2->update(['permissions_customized_at' => now()]);
            }
            $this->line("  ✓ Role 'teknisi-2': disinkronkan persis identik dengan role 'teknisi' (" . count($teknisiPerms) . " izin).");
        }

        // 3. Role sales: Bersihkan menu_network_capacity & network_capacity_view_area
        $roleSales = Role::where('name', 'sales')->first();
        if ($roleSales) {
            $salesForbidden = $roleSales->permissions()->whereIn('name', ['menu_network_capacity', 'network_capacity_view_area'])->pluck('name')->toArray();
            if (!empty($salesForbidden)) {
                $roleSales->revokePermissionTo($salesForbidden);
                $this->line("  ✓ Role 'sales': dibersihkan dari menu_network_capacity & network_capacity_view_area.");
            }
        }

        // 4. Role maintenance & maintenance-1: Pastikan tetap memiliki modul afiliasi
        $maintRoles = Role::whereIn('name', ['maintenance', 'maintenance-1'])->get();
        foreach ($maintRoles as $mRole) {
            $affiliatePerms = array_intersect(['menu_marketing_affiliates', 'menu_affiliate_portal'], $allNames);
            if (!empty($affiliatePerms)) {
                $mRole->givePermissionTo($affiliatePerms);
            }
        }
        $this->line("  ✓ Role 'maintenance' & 'maintenance-1': modul marketing afiliasi dipastikan aktif.");

        // 5. Role CS & Customer Care: Pastikan memiliki seluruh modul siklus PSB (Booking, Survey, Instalasi)
        $csRoles = Role::whereIn('name', ['cs', 'CS', 'customer care', 'Customer Care'])->get();
        $psbInstalledPerms = [
            'menu_customers_installed',
            'customers_installed_view_all',
            'customers_installed_view_area',
            'customers_installed_card_stats',
            'customers_installed_filter',
            'customers_installed_assign',
            'customers_installed_whatsapp',
            'customers_installed_audit',
            'customers_installed_report',
            'customers_installed_edit',
            'customers_installed_cancel',
        ];
        foreach ($csRoles as $cRole) {
            $cRole->givePermissionTo(array_intersect($psbInstalledPerms, $allNames));
        }
        $this->line("  ✓ Role 'cs': modul instalasi jaringan dipastikan aktif.");

        // 6. Role CS, Admin CS, Staff Administrasi, Finance & HRD: Pastikan memiliki izin Stop Permanen & Stop Sementara di Invoice
        $billingStaffRoles = Role::whereIn('name', [
            'cs', 'CS', 'admin cs', 'Admin CS', 'customer care', 'Customer Care',
            'staff administrasi', 'Staff Administrasi',
            'finance', 'Finance', 'hrd', 'HRD', 'hrd & finance', 'HRD & Finance', 'HRD & FINANCE', 'hrd & keuangan',
            'manager', 'Manager'
        ])->get();
        $stopInvoicePerms = [
            'invoices_stop_permanen',
            'invoices_stop_sementara',
            'customers_active_terminate',
            'customers_active_suspend',
        ];
        foreach ($billingStaffRoles as $bRole) {
            $bRole->givePermissionTo(array_intersect($stopInvoicePerms, $allNames));
        }
        $this->line("  ✓ Role CS, Staff Administrasi, Finance & HRD: hak akses Stop Permanen & Stop Sementara tagihan dipastikan aktif.");

        // Refresh cache (Spatie + versi RBAC Inertia agar sidebar langsung mengikuti DB)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        $this->info('✓ Sinkronisasi Permissions & Role Akses berhasil diselesaikan dengan sempurna!');
        return Command::SUCCESS;
    }
}
