<?php

use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cari semua nama material yang duplikat (case-insensitive & trimmed)
        $duplicates = DB::table('materials')
            ->select(DB::raw('LOWER(TRIM(name)) as clean_name'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('LOWER(TRIM(name))'))
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $materials = Material::whereRaw('LOWER(TRIM(name)) = ?', [$dup->clean_name])
                ->orderByDesc('is_active')
                ->orderBy('id', 'asc')
                ->get();

            if ($materials->count() <= 1) {
                continue;
            }

            // Target adalah material pertama (canonical)
            $target = $materials->shift();

            // Gabungkan semua duplikat berikutnya ke target
            foreach ($materials as $source) {
                MaterialService::mergeMaterials($target, $source, [
                    'name' => $target->name,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Penggabungan data bersifat konsolidasi satu arah (one-way cleanup)
    }
};

