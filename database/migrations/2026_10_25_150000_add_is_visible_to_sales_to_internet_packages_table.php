<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            // Paket lama sengaja disembunyikan sampai administrator memilih paket
            // yang memang boleh ditawarkan melalui Portal Sales/Afiliasi.
            $table->boolean('is_visible_to_sales')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn('is_visible_to_sales');
        });
    }
};
