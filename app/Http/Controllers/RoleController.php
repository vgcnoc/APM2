<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Master list of all permissions grouped by module.
     * Each module has: menu, features, actions.
     */
    private function getPermissionRegistry(): array
    {
        return [
            [
                'group' => 'Dashboard',
                'icon' => 'dashboard',
                'permissions' => [
                    ['name' => 'menu_dashboard', 'type' => 'menu', 'label' => 'Akses Menu Dashboard'],
                    ['name' => 'dashboard_view_stats', 'type' => 'feature', 'label' => 'Lihat Statistik'],
                    ['name' => 'dashboard_view_charts', 'type' => 'feature', 'label' => 'Lihat Grafik'],
                ],
            ],
            [
                'group' => 'Data Booking',
                'icon' => 'document-add',
                'permissions' => [
                    ['name' => 'menu_customers_booking', 'type' => 'menu', 'label' => 'Akses Menu Booking'],
                    ['name' => 'customers_booking_create', 'type' => 'action', 'label' => 'Tambah Booking'],
                    ['name' => 'customers_booking_edit', 'type' => 'action', 'label' => 'Edit Booking'],
                    ['name' => 'customers_booking_delete', 'type' => 'action', 'label' => 'Hapus Booking'],
                    ['name' => 'customers_booking_request_survey', 'type' => 'feature', 'label' => 'Request Survey'],
                    ['name' => 'customers_booking_whatsapp', 'type' => 'feature', 'label' => 'Kirim WhatsApp'],
                    ['name' => 'customers_booking_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_booking_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Survey',
                'icon' => 'clipboard-check',
                'permissions' => [
                    ['name' => 'menu_customers_survey', 'type' => 'menu', 'label' => 'Akses Menu Survey'],
                    ['name' => 'customers_survey_tab_semua', 'type' => 'feature', 'label' => 'Tab: Semua Survey'],
                    ['name' => 'customers_survey_tab_jadwalkan', 'type' => 'feature', 'label' => 'Tab: Jadwalkan Survey'],
                    ['name' => 'customers_survey_tab_laporan', 'type' => 'feature', 'label' => 'Tab: Isi Laporan Survey'],
                    ['name' => 'customers_survey_tab_ready', 'type' => 'feature', 'label' => 'Tab: Ready Install'],
                    ['name' => 'customers_survey_tab_unfeasible', 'type' => 'feature', 'label' => 'Tab: Unfeasible'],
                    ['name' => 'customers_survey_assign', 'type' => 'action', 'label' => 'Jadwalkan Survey'],
                    ['name' => 'customers_survey_reschedule', 'type' => 'action', 'label' => 'Reschedule Survey'],
                    ['name' => 'customers_survey_report', 'type' => 'feature', 'label' => 'Laporan Hasil Survey'],
                    ['name' => 'customers_survey_mark_ready', 'type' => 'feature', 'label' => 'Tandai Ready Install'],
                    ['name' => 'customers_survey_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_survey_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Instalasi',
                'icon' => 'cog',
                'permissions' => [
                    ['name' => 'menu_customers_installed', 'type' => 'menu', 'label' => 'Akses Menu Instalasi'],
                    ['name' => 'customers_installed_tab_semua', 'type' => 'feature', 'label' => 'Tab: Semua Instalasi'],
                    ['name' => 'customers_installed_tab_jadwal', 'type' => 'feature', 'label' => 'Tab: Jadwal Pasang'],
                    ['name' => 'customers_installed_tab_laporan', 'type' => 'feature', 'label' => 'Tab: Laporan Pasang'],
                    ['name' => 'customers_installed_tab_audit', 'type' => 'feature', 'label' => 'Tab: Audit'],
                    ['name' => 'customers_installed_tab_selesai', 'type' => 'feature', 'label' => 'Tab: Selesai Instalasi'],
                    ['name' => 'customers_installed_assign', 'type' => 'action', 'label' => 'Jadwalkan Pasang'],
                    ['name' => 'customers_installed_report', 'type' => 'feature', 'label' => 'Laporan Instalasi'],
                    ['name' => 'customers_installed_audit', 'type' => 'feature', 'label' => 'Audit Instalasi'],
                    ['name' => 'customers_installed_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_installed_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Aktivasi',
                'icon' => 'key',
                'permissions' => [
                    ['name' => 'menu_customers_activation', 'type' => 'menu', 'label' => 'Akses Menu Aktivasi'],
                    ['name' => 'customers_activation_activate', 'type' => 'action', 'label' => 'Aktivasi Pelanggan'],
                    ['name' => 'customers_activation_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_activation_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Pelanggan Aktif',
                'icon' => 'badge-check',
                'permissions' => [
                    ['name' => 'menu_customers_active', 'type' => 'menu', 'label' => 'Akses Menu Pelanggan Aktif'],
                    ['name' => 'customers_active_edit', 'type' => 'action', 'label' => 'Edit Data Pelanggan'],
                    ['name' => 'customers_active_suspend', 'type' => 'action', 'label' => 'Suspend Pelanggan'],
                    ['name' => 'customers_active_terminate', 'type' => 'action', 'label' => 'Terminate Pelanggan'],
                    ['name' => 'customers_active_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_active_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Semua Pelanggan',
                'icon' => 'users',
                'permissions' => [
                    ['name' => 'menu_customers_all', 'type' => 'menu', 'label' => 'Akses Menu Semua Pelanggan'],
                    ['name' => 'customers_all_export', 'type' => 'feature', 'label' => 'Export Data'],
                    ['name' => 'customers_delete', 'type' => 'action', 'label' => 'Hapus Pelanggan'],
                    ['name' => 'customers_all_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'customers_all_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Ticketing / Gangguan',
                'icon' => 'alert-circle',
                'permissions' => [
                    ['name' => 'menu_tickets', 'type' => 'menu', 'label' => 'Akses Menu Ticketing'],
                    ['name' => 'tickets_create', 'type' => 'action', 'label' => 'Buat Tiket'],
                    ['name' => 'tickets_edit', 'type' => 'action', 'label' => 'Edit Tiket'],
                    ['name' => 'tickets_delete', 'type' => 'action', 'label' => 'Hapus Tiket'],
                    ['name' => 'tickets_assign', 'type' => 'feature', 'label' => 'Assign Teknisi'],
                    ['name' => 'tickets_close', 'type' => 'feature', 'label' => 'Tutup Tiket'],
                    ['name' => 'tickets_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'tickets_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Network Topology',
                'icon' => 'globe',
                'permissions' => [
                    ['name' => 'menu_network_topology', 'type' => 'menu', 'label' => 'Akses Network Topology'],
                ],
            ],
            [
                'group' => 'Data Jaringan',
                'icon' => 'globe',
                'permissions' => [
                    ['name' => 'menu_network_data', 'type' => 'menu', 'label' => 'Akses Data Jaringan'],
                ],
            ],
            [
                'group' => 'OLT',
                'icon' => 'server',
                'permissions' => [
                    ['name' => 'menu_network_olt', 'type' => 'menu', 'label' => 'Akses Menu OLT'],
                    ['name' => 'network_olt_create', 'type' => 'action', 'label' => 'Tambah OLT'],
                    ['name' => 'network_olt_edit', 'type' => 'action', 'label' => 'Edit OLT'],
                    ['name' => 'network_olt_delete', 'type' => 'action', 'label' => 'Hapus OLT'],
                    ['name' => 'network_olt_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_olt_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'ODC',
                'icon' => 'box',
                'permissions' => [
                    ['name' => 'menu_network_odc', 'type' => 'menu', 'label' => 'Akses Menu ODC'],
                    ['name' => 'network_odc_create', 'type' => 'action', 'label' => 'Tambah ODC'],
                    ['name' => 'network_odc_edit', 'type' => 'action', 'label' => 'Edit ODC'],
                    ['name' => 'network_odc_delete', 'type' => 'action', 'label' => 'Hapus ODC'],
                    ['name' => 'network_odc_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_odc_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'ODP',
                'icon' => 'git-branch',
                'permissions' => [
                    ['name' => 'menu_network_odp', 'type' => 'menu', 'label' => 'Akses Menu ODP'],
                    ['name' => 'network_odp_create', 'type' => 'action', 'label' => 'Tambah ODP'],
                    ['name' => 'network_odp_edit', 'type' => 'action', 'label' => 'Edit ODP'],
                    ['name' => 'network_odp_delete', 'type' => 'action', 'label' => 'Hapus ODP'],
                    ['name' => 'network_odp_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_odp_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'ONT',
                'icon' => 'wifi',
                'permissions' => [
                    ['name' => 'menu_network_ont', 'type' => 'menu', 'label' => 'Akses Menu ONT'],
                    ['name' => 'network_ont_create', 'type' => 'action', 'label' => 'Tambah ONT'],
                    ['name' => 'network_ont_edit', 'type' => 'action', 'label' => 'Edit ONT'],
                    ['name' => 'network_ont_delete', 'type' => 'action', 'label' => 'Hapus ONT'],
                    ['name' => 'network_ont_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'network_ont_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Find ONU',
                'icon' => 'search',
                'permissions' => [
                    ['name' => 'menu_network_find_onu', 'type' => 'menu', 'label' => 'Akses Menu Find ONU'],
                ],
            ],
            [
                'group' => 'Material / Barang',
                'icon' => 'archive',
                'permissions' => [
                    ['name' => 'menu_materials', 'type' => 'menu', 'label' => 'Akses Menu Material'],
                    ['name' => 'materials_create', 'type' => 'action', 'label' => 'Tambah Material'],
                    ['name' => 'materials_edit', 'type' => 'action', 'label' => 'Edit Material'],
                    ['name' => 'materials_delete', 'type' => 'action', 'label' => 'Hapus Material'],
                    ['name' => 'materials_add_stock', 'type' => 'feature', 'label' => 'Tambah Stok'],
                    ['name' => 'materials_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'materials_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Order / Pengambilan',
                'icon' => 'shopping-cart',
                'permissions' => [
                    ['name' => 'menu_material_transactions', 'type' => 'menu', 'label' => 'Akses Menu Order'],
                    ['name' => 'material_transactions_create', 'type' => 'action', 'label' => 'Buat Transaksi'],
                    ['name' => 'material_transactions_delete', 'type' => 'action', 'label' => 'Hapus Transaksi'],
                    ['name' => 'material_transactions_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'material_transactions_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Data Karyawan',
                'icon' => 'users',
                'permissions' => [
                    ['name' => 'menu_hr_employees', 'type' => 'menu', 'label' => 'Akses Menu Karyawan'],
                    ['name' => 'hr_employees_create', 'type' => 'action', 'label' => 'Tambah Karyawan'],
                    ['name' => 'hr_employees_edit', 'type' => 'action', 'label' => 'Edit Karyawan'],
                    ['name' => 'hr_employees_delete', 'type' => 'action', 'label' => 'Hapus Karyawan'],
                    ['name' => 'hr_employees_view_all', 'type' => 'feature', 'label' => 'Tampilkan All Data'],
                    ['name' => 'hr_employees_view_area', 'type' => 'feature', 'label' => 'Tampilkan Data Area'],
                ],
            ],
            [
                'group' => 'Posisi / Jabatan',
                'icon' => 'briefcase',
                'permissions' => [
                    ['name' => 'menu_hr_positions', 'type' => 'menu', 'label' => 'Akses Menu Jabatan'],
                    ['name' => 'hr_positions_create', 'type' => 'action', 'label' => 'Tambah Jabatan'],
                    ['name' => 'hr_positions_edit', 'type' => 'action', 'label' => 'Edit Jabatan'],
                    ['name' => 'hr_positions_delete', 'type' => 'action', 'label' => 'Hapus Jabatan'],
                ],
            ],
            [
                'group' => 'Master Area',
                'icon' => 'map',
                'permissions' => [
                    ['name' => 'menu_settings_areas', 'type' => 'menu', 'label' => 'Akses Master Area'],
                    ['name' => 'settings_areas_create', 'type' => 'action', 'label' => 'Tambah Area'],
                    ['name' => 'settings_areas_edit', 'type' => 'action', 'label' => 'Edit Area'],
                    ['name' => 'settings_areas_delete', 'type' => 'action', 'label' => 'Hapus Area'],
                ],
            ],
            [
                'group' => 'Manajemen Role & Akses',
                'icon' => 'lock-closed',
                'permissions' => [
                    ['name' => 'menu_settings_roles', 'type' => 'menu', 'label' => 'Akses Manajemen Role'],
                    ['name' => 'settings_roles_create', 'type' => 'action', 'label' => 'Tambah Role'],
                    ['name' => 'settings_roles_edit', 'type' => 'action', 'label' => 'Edit Role'],
                    ['name' => 'settings_roles_delete', 'type' => 'action', 'label' => 'Hapus Role'],
                ],
            ],
            [
                'group' => 'Branding Aplikasi',
                'icon' => 'color-swatch',
                'permissions' => [
                    ['name' => 'menu_settings_branding', 'type' => 'menu', 'label' => 'Akses Branding'],
                    ['name' => 'settings_branding_edit', 'type' => 'action', 'label' => 'Ubah Branding'],
                ],
            ],
            [
                'group' => 'API & Integrasi',
                'icon' => 'code',
                'permissions' => [
                    ['name' => 'menu_settings_api', 'type' => 'menu', 'label' => 'Akses API Setting'],
                    ['name' => 'settings_api_generate_token', 'type' => 'feature', 'label' => 'Generate Token'],
                    ['name' => 'settings_api_sync', 'type' => 'feature', 'label' => 'Sinkronisasi Data'],
                ],
            ],
            [
                'group' => 'Manajemen User',
                'icon' => 'users',
                'permissions' => [
                    ['name' => 'menu_users', 'type' => 'menu', 'label' => 'Akses Menu User'],
                    ['name' => 'users_create', 'type' => 'action', 'label' => 'Tambah User'],
                    ['name' => 'users_edit', 'type' => 'action', 'label' => 'Edit User'],
                    ['name' => 'users_delete', 'type' => 'action', 'label' => 'Hapus User'],
                    ['name' => 'users_assign_role', 'type' => 'feature', 'label' => 'Assign Role'],
                ],
            ],
            [
                'group' => 'Paket Internet',
                'icon' => 'package',
                'permissions' => [
                    ['name' => 'menu_internet_packages', 'type' => 'menu', 'label' => 'Akses Menu Paket'],
                    ['name' => 'internet_packages_create', 'type' => 'action', 'label' => 'Tambah Paket'],
                    ['name' => 'internet_packages_edit', 'type' => 'action', 'label' => 'Edit Paket'],
                    ['name' => 'internet_packages_delete', 'type' => 'action', 'label' => 'Hapus Paket'],
                ],
            ],
        ];
    }

    /**
     * Ensure all permissions from registry exist in DB.
     */
    private function syncPermissionsToDb(): void
    {
        $registry = $this->getPermissionRegistry();
        foreach ($registry as $group) {
            foreach ($group['permissions'] as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm['name'],
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    public function index(Request $request)
    {
        // Auto-sync permissions to DB on page load
        $this->syncPermissionsToDb();

        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'users_count' => $role->users()->count(),
            ];
        });

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $this->getPermissionRegistry(),
            'positions' => \App\Models\Position::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array',
        ]);

        if ($role->name === 'admin' && $request->name !== 'admin') {
            return redirect()->back()->with('error', 'Nama role Admin tidak dapat diubah.');
        }

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil diperbarui.");
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'Role Admin tidak dapat dihapus.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', "Role '{$role->name}' masih digunakan oleh {$role->users()->count()} user. Pindahkan user terlebih dahulu.");
        }

        $role->delete();

        return redirect()->back()->with('success', "Role berhasil dihapus.");
    }
}
