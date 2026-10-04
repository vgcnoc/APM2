<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Register Observers for RADIUS Synchronization
        \App\Models\Customer::observe(\App\Observers\Radius\CustomerObserver::class);
        \App\Models\Ont::observe(\App\Observers\Radius\OntObserver::class);
        \App\Models\InternetPackage::observe(\App\Observers\Radius\InternetPackageObserver::class);
        \App\Models\VoucherProfile::observe(\App\Observers\Radius\VoucherProfileObserver::class);
        \App\Models\Voucher::observe(\App\Observers\Radius\VoucherObserver::class);
    }
}
