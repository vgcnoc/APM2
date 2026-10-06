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

    /**
     * Durasi sesi "real-time".
     * acctsessiontime hanya diperbarui saat NAS mengirim Interim-Update,
     * sehingga untuk sesi aktif dihitung juga dari acctstarttime.
     */
    public function liveSessionTime(): int
    {
        $recorded = (int) ($this->acctsessiontime ?? 0);

        if ($this->acctstarttime && empty($this->acctstoptime)) {
            $elapsed = (int) abs(now()->getTimestamp() - $this->acctstarttime->getTimestamp());
            return max($recorded, $elapsed);
        }

        return $recorded;
    }
}
