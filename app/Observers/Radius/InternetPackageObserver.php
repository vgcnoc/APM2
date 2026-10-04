<?php

namespace App\Observers\Radius;

use App\Models\InternetPackage;
use App\Services\RadiusService;

class InternetPackageObserver
{
    public function __construct(protected RadiusService $radius) {}

    public function saved(InternetPackage $package): void
    {
        $this->radius->guard(fn (RadiusService $radius) => $radius->syncPackage($package));
    }

    public function deleted(InternetPackage $package): void
    {
        $this->radius->guard(fn (RadiusService $radius) => $radius->removeGroup($radius->packageGroup($package->id)));
    }
}
