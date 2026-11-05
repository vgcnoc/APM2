<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['name', 'description', 'address', 'latitude', 'longitude', 'radius_meters'];

    public static function findByNameFlexible(?string $name): ?self
    {
        if (empty($name)) {
            return null;
        }
        $trimmed = trim($name);
        $direct = static::whereRaw('LOWER(name) = ?', [strtolower($trimmed)])->first();
        if ($direct) {
            return $direct;
        }

        $norm = preg_replace('/[^a-z0-9]/i', '', $trimmed);
        if ($norm === '') {
            return null;
        }

        return static::all()->first(function ($a) use ($norm) {
            return preg_replace('/[^a-z0-9]/i', '', $a->name) === $norm;
        });
    }

    public static function findOrCreateFlexible(string $name, array $attributes = []): self
    {
        $existing = static::findByNameFlexible($name);
        if ($existing) {
            return $existing;
        }
        return static::create(array_merge(['name' => trim($name)], $attributes));
    }

    protected static function booted(): void
    {
        static::updated(function (Area $area) {
            if ($area->wasChanged('name')) {
                Customer::where('area_id', $area->id)->update(['area' => $area->name]);
                MaterialTransaction::where('area_id', $area->id)->update(['area' => $area->name]);
                Expense::where('area_id', $area->id)->update(['area' => $area->name]);
                Income::where('area_id', $area->id)->update(['area' => $area->name]);
            }
        });
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function stocks()
    {
        return $this->hasMany(MaterialStock::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'area_id');
    }

    public function odps()
    {
        return $this->hasMany(Odp::class, 'area_id');
    }

    public function odcs()
    {
        return $this->hasMany(Odc::class, 'area_id');
    }

    public function olts()
    {
        return $this->belongsToMany(Olt::class, 'area_olt')->orderBy('olts.name');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'area_id');
    }
}
