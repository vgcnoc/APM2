<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Guard;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends SpatieRole
{
    protected $fillable = [
        'company_id',
        'name',
        'guard_name',
        'permissions_customized_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Resolve ID company yang sedang aktif (sesi switcher, user login, atau paksaan).
     */
    public static function resolveActiveCompanyId(): int
    {
        if (app()->has('forced_company_id')) {
            $forced = (int) app('forced_company_id');
            if ($forced > 0) return $forced;
        }

        if (session()->has('active_company_id') && session('active_company_id') !== 'all') {
            $active = (int) session('active_company_id');
            if ($active > 0) return $active;
        }

        if (auth()->check() && auth()->user()->company_id) {
            return (int) auth()->user()->company_id;
        }

        return 1;
    }

    /**
     * Scope isolasi role berdasarkan company yang aktif.
     */
    public function scopeForCurrentCompany($query)
    {
        $cId = static::resolveActiveCompanyId();

        if ($cId === 1) {
            return $query->where(function ($q) {
                $q->where('roles.company_id', 1)->orWhereNull('roles.company_id');
            });
        }

        return $query->where('roles.company_id', $cId);
    }

    /**
     * Override create agar pengecekan duplikat memperhitungkan company_id.
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);
        $attributes['company_id'] = !empty($attributes['company_id']) ? (int) $attributes['company_id'] : static::resolveActiveCompanyId();

        $params = [
            'name' => $attributes['name'],
            'guard_name' => $attributes['guard_name'],
            'company_id' => $attributes['company_id'],
        ];

        if (static::findByParam($params)) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * Override findByParam agar menyaring berdasarkan company_id.
     */
    protected static function findByParam(array $params = []): ?RoleContract
    {
        $query = static::query();

        if (isset($params[(new static)->getKeyName()])) {
            $query->where((new static)->getKeyName(), $params[(new static)->getKeyName()]);
        } else {
            if (isset($params['name'])) {
                $query->where('name', $params['name']);
            }
            if (isset($params['company_id'])) {
                $query->where('company_id', $params['company_id']);
            } else {
                $cId = static::resolveActiveCompanyId();
                if ($cId === 1) {
                    $query->where(fn($q) => $q->where('company_id', 1)->orWhereNull('company_id'));
                } else {
                    $query->where('company_id', $cId);
                }
            }
        }

        if (isset($params['guard_name'])) {
            $query->where('guard_name', $params['guard_name']);
        }

        return $query->first();
    }

    /**
     * Override findByName dengan mempertimbangkan company_id dan fallback ke holding.
     */
    public static function findByName(string $name, ?string $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);
        $cId = static::resolveActiveCompanyId();

        $role = static::where('company_id', $cId)
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role && $cId !== 1) {
            $role = static::where(function ($q) {
                    $q->where('company_id', 1)->orWhereNull('company_id');
                })
                ->where('name', $name)
                ->where('guard_name', $guardName)
                ->first();
        }

        if (!$role) {
            throw RoleDoesNotExist::named($name, $guardName);
        }

        return $role;
    }

    /**
     * Override findOrCreate dengan mengikat ke company_id aktif.
     */
    public static function findOrCreate(string $name, ?string $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);
        $cId = static::resolveActiveCompanyId();

        $role = static::where('company_id', $cId)
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            return static::create([
                'company_id' => $cId,
                'name' => $name,
                'guard_name' => $guardName,
            ]);
        }

        return $role;
    }
}
