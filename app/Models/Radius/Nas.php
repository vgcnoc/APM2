<?php

namespace App\Models\Radius;

/**
 * NAS / Router yang diizinkan melakukan autentikasi ke FreeRADIUS.
 */
class Nas extends RadiusModel
{
    protected $table = 'nas';

    protected $hidden = [];

    public function accountingAddresses(): array
    {
        return array_values(array_unique(array_filter([
            \App\Support\NasAddress::ip($this->nasname),
            \App\Support\NasAddress::ip($this->vpn_ip),
        ])));
    }

    public function apiHost(): ?string
    {
        return \App\Support\NasAddress::ip($this->vpn_ip) ?: \App\Support\NasAddress::ip($this->nasname);
    }

    protected function casts(): array
    {
        return [
            'ports' => 'integer',
            'coa_port' => 'integer',
        ];
    }
}
