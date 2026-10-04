<?php

namespace App\Models\Radius;

/**
 * Log hasil autentikasi (Access-Accept / Access-Reject).
 */
class RadPostAuth extends RadiusModel
{
    protected $table = 'radpostauth';

    protected function casts(): array
    {
        return [
            'authdate' => 'datetime',
        ];
    }
}
