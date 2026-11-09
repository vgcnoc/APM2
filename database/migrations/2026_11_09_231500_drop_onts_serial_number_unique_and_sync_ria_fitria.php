<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use App\Models\Ont;
use App\Models\CustomerInstalledMaterial;
use App\Models\TechnicianSchedule;
use App\Services\SurveyNetworkService;

return new class extends Migration
{
    /**
     * Hapus constraint unique ketat pada onts.serial_number (tetap pertahankan index biasa)
     * karena unit ONT rekondisi/refurbished (seperti ZTE F660 / XPON) di lapangan dapat memiliki
     * PON Serial Number pabrikan yang identik antar pelanggan berbeda (ID unik ONT tetap dipegang oleh id & ont_id).
     */
    public function up(): void
    {
        $hasUnique = collect(DB::select("SHOW INDEX FROM onts WHERE Key_name = 'onts_serial_number_unique'"))->isNotEmpty();
        if ($hasUnique) {
            Schema::table('onts', function (Blueprint $table) {
                $table->dropUnique('onts_serial_number_unique');
            });
        }

        $hasIndex = collect(DB::select("SHOW INDEX FROM onts WHERE Column_name = 'serial_number'"))->isNotEmpty();
        if (!$hasIndex) {
            Schema::table('onts', function (Blueprint $table) {
                $table->index('serial_number');
            });
        }

        // Terapkan otomatis update laporan instalasi pelanggan Ria Fitria (ID: 602, ONT ID: 2110) yang sebelumnya tertahan constraint
        $ont2110 = Ont::find(2110);
        $customer602 = Customer::find(602);
        if ($ont2110 && $customer602 && (int) $ont2110->customer_id === 602 && empty($ont2110->odp_id)) {
            try {
                $payload = [
                    'odp_id' => 246,
                    'port_number' => 2,
                    'serial_number' => 'RTEGCA98A606',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'photo_odp' => 'installations/rSBP0EZBo59bEMEgTqYBCHW2N7LtyQEyjH8f6r0h.jpg',
                    'photo_installation' => 'installations/qDbpBcf161HTmmUrMTCjI3G9RxhHDdoRCl1ATaHI.jpg',
                    'photo_ont' => 'installations/blXmohMW9ZHuzX0eIYEW2fyWV67DqgyEcF1RsreM.jpg',
                    'photo_customer' => 'installations/JCOAO6SVtTSHj4Oj3VSXiDa8XtSpZT02PoZc4f3H.jpg',
                    'photo_redaman' => 'installations/t4KzSPXCLQtS6zMSyXJpnEFsxM0BpXTy3evZe0aG.jpg',
                    'wifi_ssid' => 'RIA FITRIA',
                    'wifi_password' => '12345678',
                ];

                $ont = app(SurveyNetworkService::class)->syncInstallation($customer602, $payload);

                $customer602->update([
                    'wifi_ssid' => 'RIA FITRIA',
                    'wifi_password' => '12345678',
                ]);

                CustomerInstalledMaterial::where('customer_id', 602)
                    ->where('status', 'installed')
                    ->where(function ($q) {
                        $q->where('item_name', 'like', '%ONT%')
                          ->orWhere('item_name', 'like', '%Modem%');
                    })
                    ->update(['serial_number' => 'RTEGCA98A606']);

                $schedules = TechnicianSchedule::where('customer_id', 602)
                    ->where('type', 'installation')
                    ->get();
                foreach ($schedules as $sch) {
                    $lines = explode("\n", $sch->notes ?? '');
                    $lines = array_map(function ($line) {
                        if (str_starts_with(trim($line), 'ONT:')) {
                            return preg_replace('/\(SN:\s*[^)]+\)/i', '(SN: RTEGCA98A606)', $line);
                        }
                        return $line;
                    }, $lines);
                    $lines = array_filter($lines, fn($l) => !str_starts_with(trim($l), 'WiFi:'));
                    $lines[] = "WiFi: SSID 'RIA FITRIA', Sandi '12345678' (Laporan Instalasi)";
                    $sch->update(['notes' => implode("\n", $lines)]);
                }
            } catch (\Throwable $e) {
                \Log::warning('[Migration 2026_11_09_231500] Sync Ria Fitria warning: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible schema relaxation
    }
};
