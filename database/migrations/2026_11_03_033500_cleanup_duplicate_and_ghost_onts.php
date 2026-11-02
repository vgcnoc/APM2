<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Ont;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hapus 20 record ONT duplikat (Ghost Records) yang statusnya Belum Set tetapi MAC Address-nya sudah aktif di pelanggan lain
        $duplicateIds = [606, 612, 638, 673, 678, 703, 726, 730, 733, 738, 748, 750, 769, 779, 780, 783, 820, 834, 838, 926];
        Ont::whereIn('id', $duplicateIds)
            ->where('status', 'Belum Set/Baru Input')
            ->whereNull('customer_id')
            ->delete();

        // 2. Hapus 14 record ONT ghost yang tidak memiliki nomor serial valid (SN hanya nama orang dari pelanggan yang dibatalkan/dihapus saat migrasi)
        $nameOnlyIds = [544, 589, 600, 620, 672, 758, 766, 793, 804, 849, 882, 889, 896, 912];
        Ont::whereIn('id', $nameOnlyIds)
            ->where('status', 'Belum Set/Baru Input')
            ->whereNull('customer_id')
            ->delete();

        // 3. Rapikan 24 ONT bekas standby valid yang serial number-nya masih tercampur nama orang di belakangnya
        $standbyOnts = Ont::where('status', 'Belum Set/Baru Input')
            ->whereNull('customer_id')
            ->where('serial_number', 'not like', 'TBA-%')
            ->get();

        foreach ($standbyOnts as $ont) {
            $rawSn = $ont->serial_number;
            $cleanSn = $rawSn;

            // Ekstrak bagian SN murni sebelum tanda slash (/), kurung, atau spasi nama
            if (preg_match('/^([A-Za-z0-9:_-]+)/', $rawSn, $matches)) {
                $cleanSn = $matches[1];
            }
            $cleanSn = trim(str_replace([':', '-'], '', $cleanSn));

            // Jika diawali fc:f2 format mac, jadikan uppercase
            if (str_starts_with(strtolower($cleanSn), 'fcf2')) {
                $cleanSn = strtoupper($cleanSn);
            }

            if (!empty($cleanSn) && strlen($cleanSn) >= 6 && $cleanSn !== $rawSn) {
                // Pastikan tidak tabrakan dengan SN lain yang sudah ada
                $exists = Ont::where('serial_number', $cleanSn)->where('id', '!=', $ont->id)->exists();
                if (!$exists) {
                    $ont->update([
                        'serial_number' => $cleanSn,
                        'notes' => trim(($ont->notes ? $ont->notes . ' | ' : '') . 'SN Awal Migrasi: ' . $rawSn)
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data cleanup irreversible
    }
};
