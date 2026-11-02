<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update ONTs yang sudah terpasang di pelanggan (memiliki customer_id) tapi statusnya masih 'Sudah Set'
        DB::table('onts')
            ->whereNotNull('customer_id')
            ->where('status', 'Sudah Set')
            ->update(['status' => 'active', 'updated_at' => now()]);

        // 2. Update ONTs yang sudah terpasang di pelanggan aktif tapi statusnya masih 'Belum Set/Baru Input'
        DB::table('onts')
            ->join('customers', 'onts.customer_id', '=', 'customers.id')
            ->where('customers.status', 'active')
            ->where('onts.status', 'Belum Set/Baru Input')
            ->update(['onts.status' => 'active', 'onts.updated_at' => now()]);
    }

    public function down(): void
    {
        // No-op
    }
};
