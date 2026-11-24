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
                if ($forcedId === 1) {
                    $builder->where(function ($q) use ($model) {
                        $q->where($model->qualifyColumn('company_id'), 1)
                          ->orWhereNull($model->qualifyColumn('company_id'));
                    });
                } else {
                    $builder->where($model->qualifyColumn('company_id'), $forcedId);
                }
            }
            return;
        }

        // 2. Jika dieksekusi via CLI/Artisan tanpa user terautentikasi, biarkan terbuka agar cron holding aman
        if (app()->runningInConsole() && !Auth::check()) {
            return;
        }

        // 3. Jika user login (di browser web maupun sesi CLI)
        if (Auth::check()) {
            $user = Auth::user();

            // Jika Super Admin Holding:
            // Cek apakah sedang memilih tampilan khusus ("Switch Company")
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                $switchCompanyId = session('active_company_id');
                if ($switchCompanyId && $switchCompanyId !== 'all') {
                    $cId = (int) $switchCompanyId;
                    if ($cId === 1) {
                        $builder->where(function ($q) use ($model) {
                            $q->where($model->qualifyColumn('company_id'), 1)
                              ->orWhereNull($model->qualifyColumn('company_id'));
                        });
                    } else {
                        $builder->where($model->qualifyColumn('company_id'), $cId);
                    }
                    return;
                }

                // Default tampilan Pusat (belum memilih switch atau login biasa di Pusat):
                // Pastikan data milik Pusat terisolasi dari data Mitra (company_id = 1 atau null)
                // agar paket internet, tarif, dan master data mitra TIDAK bocor/masuk ke Pusat
                $builder->where(function ($q) use ($model) {
                    $q->where($model->qualifyColumn('company_id'), 1)
                      ->orWhereNull($model->qualifyColumn('company_id'));
                });
                return;
            }

            // Untuk akun Mitra / Karyawan Mitra:
            // Secara mutlak & otomatis hanya dapat melihat data perusahaannya sendiri
            $companyId = (int) ($user->company_id ?: 1);
            if ($companyId === 1) {
                $builder->where(function ($q) use ($model) {
                    $q->where($model->qualifyColumn('company_id'), 1)
                      ->orWhereNull($model->qualifyColumn('company_id'));
                });
            } else {
                $builder->where($model->qualifyColumn('company_id'), $companyId);
            }
        }
    }
}
