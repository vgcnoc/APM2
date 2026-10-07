<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'type',
        'amount',
        'description',
        'reference_id',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
