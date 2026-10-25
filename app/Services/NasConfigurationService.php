<?php

namespace App\Services;

use App\Models\Radius\Nas;
use App\Support\NasAddress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Throwable;

class NasConfigurationService
{
    public function signature(): string
    {
        return hash('sha256', Nas::orderBy('id')->get(['id','nasname','shortname','type','secret','server'])->toJson());
    }

    public function status(): array
    {
        $state = Cache::get('radius:nas-applied', []);
        return ['enabled' => (bool) config('radius.enabled'), 'pending' => ($state['signature'] ?? null) !== $this->signature(), 'applied_at' => $state['applied_at'] ?? null];
    }

    public function afterMutation(): array
    {
        if (!config('radius.enabled') || !config('radius.nas_auto_apply', true)) {
            return ['success' => true, 'message' => 'Data tersimpan. Terapkan konfigurasi NAS saat layanan RADIUS siap.'];
        }
        return $this->apply();
    }

    public function apply(): array
    {
        if (!config('radius.enabled')) return ['success' => false, 'message' => 'Integrasi RADIUS belum diaktifkan.'];
        try {
            return Cache::lock(NasManagementService::LOCK, 120)->block(10, function () {
                $rows = Nas::all();
                if ($rows->contains(fn ($nas) => !NasAddress::ip($nas->nasname)) || $rows->pluck('nasname')->unique()->count() !== $rows->count()) {
                    return ['success' => false, 'message' => 'Data tersimpan, tetapi NAS belum diterapkan: masih ada alamat wildcard/tidak valid/duplikat. Jalankan pemeriksaan perbaikan NAS terlebih dahulu.'];
                }
                if (config('radius.vpn.enabled') && !app(VpnAccountService::class)->sync()) {
                    return ['success' => false, 'message' => 'Data tersimpan, tetapi file akun VPN belum berhasil disinkronkan. Konfigurasi RADIUS belum diterapkan.'];
                }
                if (!$this->status()['pending']) return ['success' => true, 'message' => 'Akun VPN tersinkron; konfigurasi NAS RADIUS sudah diterapkan.'];
                $check = Process::timeout(20)->run(config('radius.check_command'));
                // FreeRADIUS may exit 0 even when a duplicate SQL client was ignored.
                if (!$check->successful() || preg_match('/duplicate client|failed to add client|failed to add duplicate/i', $check->output().' '.$check->errorOutput())) {
                    return ['success' => false, 'message' => 'Data tersimpan, tetapi validasi FreeRADIUS gagal. Layanan tidak direstart; periksa log server dan pemetaan NAS.'];
                }
                $restart = Process::timeout(20)->run(config('radius.restart_command'));
                if (!$restart->successful()) return ['success' => false, 'message' => 'Data tersimpan dan valid, tetapi penerapan FreeRADIUS gagal. Periksa izin layanan di VPS.'];
                $health = Process::timeout(5)->run(config('radius.status_command'));
                if (!$health->successful() || trim($health->output()) !== 'active') return ['success' => false, 'message' => 'FreeRADIUS belum aktif setelah penerapan. Segera periksa layanan di VPS.'];
                Cache::forever('radius:nas-applied', ['signature' => $this->signature(), 'applied_at' => now()->toIso8601String()]);
                return ['success' => true, 'message' => 'Konfigurasi NAS tervalidasi dan berhasil diterapkan ke FreeRADIUS.'];
            });
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Data tersimpan, tetapi penerapan NAS belum berhasil. Tidak ada password yang diubah otomatis.'];
        }
    }
}
