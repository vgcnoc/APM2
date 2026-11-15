<?php

namespace App\Traits;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot trait BelongsToCompany
     */
    protected static function bootBelongsToCompany(): void
    {
        // 1. Terapkan Global Scope isolasi otomatis
        static::addGlobalScope(new CompanyScope);

        // 2. Saat create data baru, otomatis pasang company_id yang sedang aktif
        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $activeId = session('active_company_id');
                if ($activeId && $activeId !== 'all') {
                    $model->company_id = (int) $activeId;
                } elseif (Auth::check() && Auth::user()->company_id) {
                    $model->company_id = (int) Auth::user()->company_id;
                } else {
                    $model->company_id = 1; // Default Holding (Perusahaan Anda)
                }
            }
        });
    }

    /**
     * Relasi ke Master Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
