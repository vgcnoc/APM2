<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            foreach ($templates as $t) {
                if (!empty($t->content)) {
                    $updated = str_replace(
                        [
                            'Jl.Perintis Kemerdekaan No.12A Kel.Sukamulya Kec.Cikembar Sukabumi',
                            'Jl. Perintis Kemerdekaan No. 12A, Kel. Sukamulya, Kec. Cikembar, Kab. Sukabumi, Jawa Barat 43157',
                            'Jl. Perintis Kemerdekaan No.12A Kel.Sukamulya Kec.Cikembar Sukabumi',
                        ],
                        '{alamat_perusahaan}',
                        $t->content
                    );

                    if ($updated !== $t->content) {
                        DB::table('subscription_terms_templates')
                            ->where('id', $t->id)
                            ->update(['content' => $updated]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
