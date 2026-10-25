<?php

namespace App\Services;

use App\Models\Radius\Nas;
use App\Support\NasAddress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NasManagementService
{
    public const LOCK = 'radius:nas-configuration';

    public function __construct(private VpnAccountService $vpn) {}

    public function create(array $attributes, bool $useVpn): Nas
    {
        return Cache::lock(self::LOCK, 120)->block(10, function () use ($attributes, $useVpn) {
            return (new Nas)->getConnection()->transaction(function () use ($attributes, $useVpn) {
                unset($attributes['use_vpn']);
                $nas = new Nas($attributes);
                if ($useVpn) {
                    if (!config('radius.vpn.enabled')) throw ValidationException::withMessages(['nasname' => 'VPN server belum diaktifkan. Masukkan IP sumber router yang spesifik.']);
                    $this->vpn->prepareAccount($nas);
                    $nas->nasname = $nas->vpn_ip;
                }
                $nas->api_user = $nas->api_user ?: 'APM2'.Str::upper(Str::random(8));
                $nas->api_password = $nas->api_password ?: Str::random(24);
                $this->validateAddress($nas);
                $nas->save();
                return $nas;
            });
        });
    }

    public function update(Nas $nas, array $attributes): Nas
    {
        return Cache::lock(self::LOCK, 120)->block(10, function () use ($nas, $attributes) {
            unset($attributes['use_vpn']);
            // Existing VPN clients keep their IP and credentials. No silent renumbering.
            if ($nas->vpn_ip) {
                if (isset($attributes['nasname']) && NasAddress::ip($attributes['nasname']) !== NasAddress::ip($nas->vpn_ip)) {
                    throw ValidationException::withMessages(['nasname' => 'IP NAS harus sesuai IP VPN tetap router ini: '.$nas->vpn_ip]);
                }
                $attributes['nasname'] = $nas->vpn_ip;
            }
            $nas->fill($attributes);
            $this->validateAddress($nas);
            $nas->save();
            return $nas;
        });
    }

    public function delete(Nas $nas): void
    {
        Cache::lock(self::LOCK, 120)->block(10, fn () => $nas->delete());
    }

    public function regenerate(Nas $nas): void
    {
        Cache::lock(self::LOCK, 120)->block(10, function () use ($nas) {
            $this->vpn->prepareAccount($nas);
            $nas->nasname = $nas->vpn_ip;
            $this->validateAddress($nas);
            $nas->vpn_password = Str::random(24);
            $nas->api_password = Str::random(24);
            $nas->save();
        });
    }

    private function validateAddress(Nas $nas): void
    {
        $address = NasAddress::ip($nas->nasname);
        if (!$address) throw ValidationException::withMessages(['nasname' => 'Gunakan satu IP router yang valid. Wildcard 0.0.0.0/0 dan subnet tidak diperbolehkan.']);
        if (Nas::where('nasname', $address)->when($nas->exists, fn ($q) => $q->where('id', '!=', $nas->id))->exists()) {
            throw ValidationException::withMessages(['nasname' => 'IP NAS sudah digunakan router lain.']);
        }
        $nas->nasname = $address;
    }
}
