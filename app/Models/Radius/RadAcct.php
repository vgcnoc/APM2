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
        return $query->where(function ($q) {
            $q->whereNull('acctstoptime')
              ->orWhere('acctstoptime', '0000-00-00 00:00:00')
              ->orWhere('acctstoptime', '');
        });
    }

    public static function freshnessSeconds(): int
    {
        return max(180, (int) config('radius.interim_interval', 60) * 3);
    }

    public function scopeFresh($query)
    {
        return $query->where('acctupdatetime', '>=', now()->subSeconds(self::freshnessSeconds()));
    }

    /**
     * Durasi sesi "real-time".
     * acctsessiontime hanya diperbarui saat NAS mengirim Interim-Update,
     * sehingga untuk sesi aktif dihitung juga dari acctstarttime.
     */
    public function liveSessionTime(): int
    {
        $recorded = (int) ($this->acctsessiontime ?? 0);

        if (!empty($this->acctstarttime) && empty($this->acctstoptime)) {
            try {
                $startTs = $this->acctstarttime instanceof \DateTimeInterface 
                    ? $this->acctstarttime->getTimestamp() 
                    : strtotime((string) $this->acctstarttime);

                if ($startTs && $startTs > 0) {
                    $elapsed = (int) abs(now()->getTimestamp() - $startTs);
                    return max($recorded, $elapsed);
                }
            } catch (\Throwable $e) {
                return $recorded;
            }
        }

        return $recorded;
    }
}
