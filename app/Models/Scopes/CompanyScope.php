<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Terapkan scope isolasi company secara otomatis ke Query Builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // 1. Jika ada pemaksaan company ID (misal saat cron job atau testing background)
        if (app()->has('forced_company_id')) {
            $forcedId = (int) app('forced_company_id');
            if ($forcedId > 0) {
                $builder->where($model->qualifyColumn('company_id'), $forcedId);
            }
            return;
        }

        // 2. Jika dieksekusi via CLI/Artisan (tanpa forced_company_id), biarkan terbuka agar cron holding aman
        if (app()->runningInConsole()) {
            return;
        }

        // 3. Jika user login di browser
        if (Auth::check()) {
            $user = Auth::user();

            // Jika Super Admin Holding:
            // Cek apakah sedang memilih tampilan khusus ("Switch Company")
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                $switchCompanyId = session('active_company_id');
                if ($switchCompanyId && $switchCompanyId !== 'all') {
                    $builder->where($model->qualifyColumn('company_id'), (int) $switchCompanyId);
                }
                // Jika 'all' atau belum memilih, Super Admin Pusat leluasa melihat data holding & konsolidasi
                return;
            }

            // Untuk akun Mitra / Karyawan Mitra:
            // Secara mutlak & otomatis hanya dapat melihat data perusahaannya sendiri
            $companyId = (int) ($user->company_id ?: 1);
            $builder->where($model->qualifyColumn('company_id'), $companyId);
        }
    }
}
