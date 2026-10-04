<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema standar FreeRADIUS 3.2 (mods-config/sql/main/mysql/schema.sql)
 * dibuat via Laravel agar ikut ter-deploy bersama aplikasi.
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

        if (!$schema->hasTable('radacct')) {
            $schema->create('radacct', function (Blueprint $table) {
                $table->bigIncrements('radacctid');
                $table->string('acctsessionid', 64)->default('')->index();
                $table->string('acctuniqueid', 32)->default('')->unique();
                $table->string('username', 64)->default('')->index();
                $table->string('realm', 64)->nullable()->default('');
                $table->string('nasipaddress', 15)->default('')->index();
                $table->string('nasportid', 32)->nullable();
                $table->string('nasporttype', 32)->nullable();
                $table->dateTime('acctstarttime')->nullable()->index();
                $table->dateTime('acctupdatetime')->nullable();
                $table->dateTime('acctstoptime')->nullable()->index();
                $table->integer('acctinterval')->nullable()->index();
                $table->unsignedInteger('acctsessiontime')->nullable()->index();
                $table->string('acctauthentic', 32)->nullable();
                $table->string('connectinfo_start', 128)->nullable();
                $table->string('connectinfo_stop', 128)->nullable();
                $table->bigInteger('acctinputoctets')->nullable();
                $table->bigInteger('acctoutputoctets')->nullable();
                $table->string('calledstationid', 50)->default('');
                $table->string('callingstationid', 50)->default('');
                $table->string('acctterminatecause', 32)->default('');
                $table->string('servicetype', 32)->nullable();
                $table->string('framedprotocol', 32)->nullable();
                $table->string('framedipaddress', 15)->default('')->index();
                $table->string('framedipv6address', 45)->default('')->index();
                $table->string('framedipv6prefix', 45)->default('')->index();
                $table->string('framedinterfaceid', 44)->default('')->index();
                $table->string('delegatedipv6prefix', 45)->default('')->index();
                $table->string('class', 64)->nullable()->index();
            });
        }

        foreach (['radcheck' => 'username', 'radreply' => 'username', 'radgroupcheck' => 'groupname', 'radgroupreply' => 'groupname'] as $name => $key) {
            if (!$schema->hasTable($name)) {
                $schema->create($name, function (Blueprint $table) use ($key, $name) {
                    $table->increments('id');
                    $table->string($key, 64)->default('')->index();
                    $table->string('attribute', 64)->default('');
                    $table->char('op', 2)->default(str_ends_with($name, 'check') ? '==' : '=');
                    $table->string('value', 253)->default('');
                });
            }
        }

        if (!$schema->hasTable('radusergroup')) {
            $schema->create('radusergroup', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('groupname', 64)->default('');
                $table->integer('priority')->default(1);
            });
        }

        if (!$schema->hasTable('radpostauth')) {
            $schema->create('radpostauth', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('pass', 64)->default('');
                $table->string('reply', 32)->default('');
                $table->timestamp('authdate', 6)->useCurrent()->useCurrentOnUpdate();
                $table->string('class', 64)->nullable()->index();
            });
        }

        if (!$schema->hasTable('nas')) {
            $schema->create('nas', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nasname', 128)->index();
                $table->string('shortname', 32)->nullable();
                $table->string('type', 30)->nullable()->default('other');
                $table->integer('ports')->nullable();
                $table->string('secret', 60)->default('secret');
                $table->string('server', 64)->nullable();
                $table->string('community', 50)->nullable();
                $table->string('description', 200)->nullable()->default('RADIUS Client');
            });
        }

        // Kolom tambahan (tidak dipakai FreeRADIUS, hanya oleh aplikasi)
        if (!$schema->hasColumn('nas', 'coa_port')) {
            $schema->table('nas', function (Blueprint $table) {
                $table->unsignedInteger('coa_port')->default(3799)->after('ports');
            });
        }

        if (!$schema->hasTable('nasreload')) {
            $schema->create('nasreload', function (Blueprint $table) {
                $table->string('nasipaddress', 15)->primary();
                $table->dateTime('reloadtime');
            });
        }
    }

    public function down(): void
    {
        $schema = $this->schema();
        foreach (['nasreload', 'nas', 'radpostauth', 'radusergroup', 'radgroupreply', 'radgroupcheck', 'radreply', 'radcheck', 'radacct'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
