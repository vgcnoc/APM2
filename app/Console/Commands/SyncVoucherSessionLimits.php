<?php

namespace App\Console\Commands;

use App\Models\VoucherProfile;
use App\Services\RadiusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncVoucherSessionLimits extends Command
{
    protected $signature = 'radius:sync-voucher-limits {--apply : Tulis batas profil ke RADIUS}';
    protected $description = 'Periksa/sinkronkan Shared Users dan Port-Limit voucher; hotspot gratis pelanggan selalu satu sesi';

    public function handle(RadiusService $radius): int
    {
        if (!$radius->enabled()) {
            $this->error('Integrasi RADIUS belum diaktifkan. Tidak ada perubahan.');
            return self::FAILURE;
        }

        return Cache::lock('radius:sync-voucher-limits', 120)->block(10, function () use ($radius) {
            $profiles = VoucherProfile::orderBy('id')->get();
            $this->table(['ID', 'Profil', 'Shared Users tersimpan', 'Batas sesi diterapkan'], $profiles->map(
                fn ($profile) => [$profile->id, $profile->name, $profile->shared_users, $profile->sessionLimit()]
            )->all());
            if (!$this->option('apply')) {
                $this->info('Pemeriksaan saja. Gunakan --apply untuk menulis atribut, tanpa memutus sesi pelanggan.');
                return self::SUCCESS;
            }
            foreach ($profiles as $profile) {
                // Only normalize limits; do not resync credentials, customer status or voucher validity.
                $profile->getConnection()->transaction(function () use ($profile, $radius) {
                    $profile = VoucherProfile::lockForUpdate()->findOrFail($profile->id);
                    $limit = $profile->sessionLimit();
                    if (($profile->is_customer_free || $profile->duration === 'Mengikuti Pelanggan') && $profile->shared_users !== $limit) {
                        $profile->forceFill(['shared_users' => $limit])->saveQuietly();
                    }
                    $radius->syncVoucherProfile($profile);
                });
            }
            $this->info('Batas sesi profil tersinkron. Tidak ada sesi yang diputus.');
            return self::SUCCESS;
        });
    }
}
