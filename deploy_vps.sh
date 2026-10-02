#!/bin/bash
cd /var/www/APM2
git pull origin main
php artisan optimize:clear
php artisan tinker --execute="App\Models\User::all()->each(function(\$u) { if(\$u->role) { \Spatie\Permission\Models\Role::firstOrCreate(['name' => \$u->role, 'guard_name' => 'web']); \$u->syncRoles([\$u->role]); } });"
