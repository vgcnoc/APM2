<?php

namespace App\Console\Commands;

use App\Models\Radius\Nas;
use App\Services\NasConfigurationService;
use App\Services\NasManagementService;
use App\Support\NasAddress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class RepairNasAddresses extends Command
{
    protected $signature = 'radius:repair-nas {--apply : Simpan koreksi setelah membuat backup alamat} {--reload : Validasi dan terapkan FreeRADIUS setelah koreksi}';
    protected $description = 'Periksa/koreksi NAS wildcard ke IP VPN tetap tanpa mengganti kredensial atau menghapus router';

    public function handle(NasConfigurationService $configuration): int
    {
        if ($this->option('reload') && !$this->option('apply')) {
            $this->error('--reload memerlukan --apply.');
            return self::FAILURE;
        }
        $ok = Cache::lock(NasManagementService::LOCK, 120)->block(10, function () {
            $rows = Nas::orderBy('id')->get();
            $changes = []; $used = [];
            foreach ($rows as $nas) {
                $address = NasAddress::ip($nas->nasname) ?: NasAddress::ip($nas->vpn_ip);
                if (!$address || isset($used[$address])) {
                    $this->error('NAS ID '.$nas->id.' belum memiliki alamat spesifik yang unik. Tidak ada data diubah.');
                    return false;
                }
                $used[$address] = $nas->id;
                if ($nas->nasname !== $address) $changes[] = ['id' => $nas->id, 'old' => $nas->nasname, 'new' => $address];
            }
            $this->table(['ID', 'Alamat lama', 'Alamat baru'], array_map('array_values', $changes));
            if (!$this->option('apply')) { $this->info('Mode pemeriksaan saja; gunakan --apply untuk menyimpan.'); return true; }
            if (!$changes) { $this->info('Semua alamat NAS sudah unik dan spesifik.'); return true; }
            $path = 'nas-backups/addresses-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
            Storage::disk('local')->put($path, json_encode(['created_at' => now()->toIso8601String(), 'changes' => $changes], JSON_PRETTY_PRINT));
            if (!Storage::disk('local')->exists($path)) throw new \RuntimeException('Backup tidak berhasil; koreksi dibatalkan.');
            @chmod(Storage::disk('local')->path($path), 0600);
            (new Nas)->getConnection()->transaction(function () use ($changes) {
                foreach ($changes as $change) {
                    $nas = Nas::lockForUpdate()->findOrFail($change['id']);
                    if ($nas->nasname !== $change['old']) throw new \RuntimeException('Data berubah selama pemeriksaan; koreksi dibatalkan.');
                    $nas->nasname = $change['new'];
                    $nas->save();
                }
            });
            $this->info('Alamat diperbaiki; kredensial tetap. Backup: '.Storage::disk('local')->path($path));
            return true;
        });
        if (!$ok) return self::FAILURE;
        if ($this->option('reload')) {
            $state = $configuration->apply();
            $this->{$state['success'] ? 'info' : 'error'}($state['message']);
            return $state['success'] ? self::SUCCESS : self::FAILURE;
        }
        return self::SUCCESS;
    }
}
