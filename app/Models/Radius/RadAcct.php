<?php

namespace App\Models\Radius;

/**
 * Data accounting sesi (ditulis oleh FreeRADIUS).
 */
class RadAcct extends RadiusModel
{
    protected $table = 'radacct';

    protected $primaryKey = 'radacctid';

    protected function casts(): array
    {
        return [
            'acctstarttime' => 'datetime',
            'acctupdatetime' => 'datetime',
            'acctstoptime' => 'datetime',
            'acctsessiontime' => 'integer',
            'acctinputoctets' => 'integer',
            'acctoutputoctets' => 'integer',
        ];
    }

    public function scopeOnline($query)
    {
        return $query->whereNull('acctstoptime');
    }
}
