<?php

namespace App\Models\Radius;

/**
 * NAS / Router yang diizinkan melakukan autentikasi ke FreeRADIUS.
 */
class Nas extends RadiusModel
{
    protected $table = 'nas';

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'ports' => 'integer',
            'coa_port' => 'integer',
        ];
    }
}
