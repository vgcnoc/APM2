<?php

use App\Support\NasAddress;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config('radius.connection', 'radius');
        $addresses = DB::connection($connection)->table('nas')->pluck('nasname');
        if ($addresses->contains(fn ($ip) => NasAddress::ip($ip) !== $ip) || $addresses->unique()->count() !== $addresses->count()) {
            throw new RuntimeException('Jalankan php artisan radius:repair-nas --apply sebelum memasang constraint NAS unik.');
        }
        $schema = Schema::connection($connection);
        if (!$schema->hasIndex('nas', 'nas_nasname_unique')) $schema->table('nas', fn (Blueprint $table) => $table->unique('nasname', 'nas_nasname_unique'));
    }

    public function down(): void
    {
        Schema::connection(config('radius.connection', 'radius'))->table('nas', fn (Blueprint $table) => $table->dropUnique('nas_nasname_unique'));
    }
};
