<?php
// c:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\sync_roles.php
App\Models\User::all()->each(function($u) { 
    if($u->role) { 
        try {
            $role = Spatie\Permission\Models\Role::firstOrCreate(['name' => $u->role, 'guard_name' => 'web']);
            $u->assignRole($role); 
            echo "Assigned {$u->role} to {$u->email}\n";
        } catch (\Exception $e) {
            echo "Error assigning {$u->role} to {$u->email}: " . $e->getMessage() . "\n";
        }
    } 
});
