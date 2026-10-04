<?php

namespace App\Models\Radius;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model untuk tabel-tabel FreeRADIUS (tanpa timestamps Laravel).
 */
abstract class RadiusModel extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function getConnectionName()
    {
        return config('radius.connection', 'radius');
    }
}
