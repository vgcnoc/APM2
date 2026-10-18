<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ticketPerms = [
            'menu_tickets',
            'tickets_card_summary',
            'tickets_tab_all',
            'tickets_tab_open',
            'tickets_tab_progress',
            'tickets_tab_closed',
            'tickets_filter_priority',
            'tickets_filter_area',
            'tickets_create',
            'tickets_edit',
            'tickets_delete',
            'tickets_assign',
            'tickets_close',
            'tickets_btn_whatsapp',
            'tickets_view_all',
            'tickets_view_area',
        ];

        // Revoke from all roles
        $roles = Role::all();
        foreach ($roles as $role) {
            $existing = array_intersect($role->permissions->pluck('name')->toArray(), $ticketPerms);
            if (!empty($existing)) {
                $role->revokePermissionTo($existing);
            }
        }

        // Delete obsolete permissions
        Permission::whereIn('name', $ticketPerms)->delete();

        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $ticketPerms = [
            'menu_tickets',
            'tickets_card_summary',
            'tickets_tab_all',
            'tickets_tab_open',
            'tickets_tab_progress',
            'tickets_tab_closed',
            'tickets_filter_priority',
            'tickets_filter_area',
            'tickets_create',
            'tickets_edit',
            'tickets_delete',
            'tickets_assign',
            'tickets_close',
            'tickets_btn_whatsapp',
            'tickets_view_all',
            'tickets_view_area',
        ];

        foreach ($ticketPerms as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
