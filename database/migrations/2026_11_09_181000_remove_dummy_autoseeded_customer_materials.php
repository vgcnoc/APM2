<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus catatan material fiktif hasil auto-seed fallback lama
        DB::table('customer_installed_materials')
            ->where('notes', 'like', '%Material terpasang standar instalasi awal pelanggan%')
            ->delete();

        // Perbaiki label generic ONT yang tidak memiliki serial number dengan data ONT riil pelanggan jika ada
        $genericOnts = DB::table('customer_installed_materials')
            ->where('item_name', 'Unit ONT Modem FTTH Standar')
            ->whereNull('serial_number')
            ->get();

        foreach ($genericOnts as $gOnt) {
            $realOnt = DB::table('onts')->where('customer_id', $gOnt->customer_id)->first();
            if ($realOnt && !empty($realOnt->serial_number)) {
                $brand = $realOnt->brand ?: 'ONT';
                $model = $realOnt->model ?: 'Modem';
                DB::table('customer_installed_materials')
                    ->where('id', $gOnt->id)
                    ->update([
                        'item_name' => "ONT {$brand} {$model}",
                        'serial_number' => $realOnt->serial_number,
                        'notes' => "Unit ONT aktif pelanggan (SN: {$realOnt->serial_number})",
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for purged dummy records
    }
};
