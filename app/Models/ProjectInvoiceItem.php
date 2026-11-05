<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_invoice_id',
        'description',
        'qty',
        'unit',
        'unit_price',
        'total_price',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'total_price' => 'float',
        'sort_order' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(ProjectInvoice::class, 'project_invoice_id');
    }
}

