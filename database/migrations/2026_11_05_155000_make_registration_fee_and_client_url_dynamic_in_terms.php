<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subscription_terms_templates')) {
            $templates = DB::table('subscription_terms_templates')->get();
            foreach ($templates as $tpl) {
                $content = $tpl->content;
                // Ganti biaya registrasi statis dengan token dinamis
                $content = str_replace(
                    ['Rp 120,000,-', 'Rp 120.000,-'],
                    '{biaya_registrasi}',
                    $content
                );
                // Ganti URL client area statis dengan token dinamis
                $content = str_replace(
                    ['https://viruzs.rlradius.com', 'http://viruzs.rlradius.com'],
                    '{client_area_url}',
                    $content
                );

                DB::table('subscription_terms_templates')
                    ->where('id', $tpl->id)
                    ->update(['content' => $content]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena token di-render otomatis oleh model
    }
};

