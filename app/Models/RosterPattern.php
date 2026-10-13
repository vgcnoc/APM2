<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RosterPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pattern_data',
        'description',
    ];

    protected $casts = [
        'pattern_data' => 'array',
    ];
}
