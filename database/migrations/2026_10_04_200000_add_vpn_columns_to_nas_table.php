<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun VPN (L2TP) + akun API per router agar script Mikrotik yang di-generate
 * selalu konsisten dan akun VPN-nya benar-benar terdaftar di server.
 * Kolom ini hanya dipakai aplikasi, tidak dibaca FreeRADIUS.
 */
return new class extends Migration
{
    protected function schema()
    {
        return Schema::connection(config('radius.connection', 'radius'));
    }

    public function up(): void
    {
        $schema = $this->schema();

        $schema->table('nas', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('nas', 'vpn_user')) {
                $table->string('vpn_user', 32)->nullable()->unique();
            }
            if (!$schema->hasColumn('nas', 'vpn_password')) {
                $table->string('vpn_password', 64)->nullable();
            }
            if (!$schema->hasColumn('nas', 'vpn_ip')) {
                $table->string('vpn_ip', 15)->nullable()->unique();
            }
            if (!$schema->hasColumn('nas', 'api_user')) {
                $table->string('api_user', 32)->nullable();
            }
            if (!$schema->hasColumn('nas', 'api_password')) {
                $table->string('api_password', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        $this->schema()->table('nas', function (Blueprint $table) {
            $table->dropColumn(['vpn_user', 'vpn_password', 'vpn_ip', 'api_user', 'api_password']);
        });
    }
};
