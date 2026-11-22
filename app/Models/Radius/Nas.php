<?php

namespace App\Models\Radius;

/**
 * NAS / Router yang diizinkan melakukan autentikasi ke FreeRADIUS.
 */
class Nas extends RadiusModel
{
    protected $table = 'nas';

    protected $hidden = [];

    public function accountingAddresses(): array
    {
        return array_values(array_unique(array_filter([
            \App\Support\NasAddress::ip($this->nasname),
            \App\Support\NasAddress::ip($this->vpn_ip),
        ])));
    }

    public function apiHost(): ?string
    {
        return \App\Support\NasAddress::ip($this->vpn_ip) ?: \App\Support\NasAddress::ip($this->nasname);
    }

    public function router(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Router::class, 'nas_id');
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    /**
     * Scope isolasi daftar NAS / Router berdasarkan mitra / company yang sedang aktif.
     */
    public function scopeForCurrentCompany($query)
    {
        $cId = 1;
        if (app()->has('forced_company_id')) {
            $cId = (int) app('forced_company_id');
        } elseif (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                $switchCompanyId = session('active_company_id');
                if ($switchCompanyId && $switchCompanyId !== 'all') {
                    $cId = (int) $switchCompanyId;
                } else {
                    $cId = 1;
                }
            } else {
                $cId = (int) ($user->company_id ?: 1);
            }
        }

        if ($cId === 1) {
            return $query->where(function ($q) {
                $q->where('nas.company_id', 1)
                  ->orWhereNull('nas.company_id')
                  ->orWhereHas('router', function ($rq) {
                      $rq->withoutGlobalScopes()->where(function ($sq) {
                          $sq->where('company_id', 1)->orWhereNull('company_id');
                      });
                  });
            });
        }

        return $query->where(function ($q) use ($cId) {
            $q->where('nas.company_id', $cId)
              ->orWhereHas('router', function ($rq) use ($cId) {
                  $rq->withoutGlobalScopes()->where('company_id', $cId);
              });
        });
    }

    protected function casts(): array
    {
        return [
            'ports' => 'integer',
            'coa_port' => 'integer',
        ];
    }
}
