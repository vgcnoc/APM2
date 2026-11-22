<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            // 1. Tambah kolom company_id jika belum ada
            if (!Schema::hasColumn('roles', 'company_id')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->default(1)->after('id')->index();
                });
            }

            // 2. Pastikan seluruh role yang ada saat ini diikat ke company_id = 1 (Holding Pusat)
            DB::table('roles')->whereNull('company_id')->orWhere('company_id', 0)->update(['company_id' => 1]);

            // 3. Ubah unique constraint dari (name, guard_name) menjadi (company_id, name, guard_name)
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->dropUnique('roles_name_guard_name_unique');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unique(['company_id', 'name', 'guard_name'], 'roles_company_name_guard_unique');
                });
            } catch (\Throwable $e) {}

            // 4. Migrasi user mitra yang sudah ada agar terhubung ke role perusahaannya sendiri
            try {
                $mitraUsers = DB::table('users')->where('company_id', '>', 1)->get();
                foreach ($mitraUsers as $mu) {
                    $userRoles = DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('model_has_roles.model_id', $mu->id)
                        ->where('model_has_roles.model_type', 'App\\Models\\User')
                        ->select('roles.id as role_id', 'roles.name as role_name', 'roles.guard_name')
                        ->get();

                    foreach ($userRoles as $ur) {
                        $companyRole = DB::table('roles')
                            ->where('company_id', $mu->company_id)
                            ->where('name', $ur->role_name)
                            ->first();

                        if (!$companyRole) {
                            $newRoleId = DB::table('roles')->insertGetId([
                                'company_id' => $mu->company_id,
                                'name' => $ur->role_name,
                                'guard_name' => $ur->guard_name ?: 'web',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Salin seluruh permissions dari role asal
                            $perms = DB::table('role_has_permissions')->where('role_id', $ur->role_id)->get();
                            foreach ($perms as $p) {
                                DB::table('role_has_permissions')->insertOrIgnore([
                                    'permission_id' => $p->permission_id,
                                    'role_id' => $newRoleId,
                                ]);
                            }

                            $targetRoleId = $newRoleId;
                        } else {
                            $targetRoleId = $companyRole->id;
                        }

                        DB::table('model_has_roles')
                            ->where('model_id', $mu->id)
                            ->where('model_type', 'App\\Models\\User')
                            ->where('role_id', $ur->role_id)
                            ->update(['role_id' => $targetRoleId]);
                    }
                }
            } catch (\Throwable $e) {
                // Log atau lanjutkan jika ada kendala
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->dropUnique('roles_company_name_guard_unique');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
                });
            } catch (\Throwable $e) {}

            if (Schema::hasColumn('roles', 'company_id')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
