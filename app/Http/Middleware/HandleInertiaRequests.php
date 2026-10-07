<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => function () use ($request) {
                return [
                    'user' => $request->user() ? (function () use ($request) {
                        $customerData = null;
                        if ($request->user()->role === 'customer') {
                            $customer = \App\Models\Customer::where('user_id', $request->user()->id)->first();
                            if ($customer) {
                                $customerData = [
                                    'is_reseller' => (bool) $customer->is_reseller, // Although not used for auth anymore
                                    'has_package' => !empty($customer->package_id),
                                ];
                            }
                        } elseif ($request->user()->role === 'reseller') {
                            $reseller = \App\Models\Reseller::where('user_id', $request->user()->id)->first();
                            if ($reseller) {
                                $customerData = [
                                    'is_reseller' => true,
                                    'has_package' => false,
                                ];
                            }
                        }

                        return [
                            'id' => $request->user()->id,
                            'name' => $request->user()->name,
                            'email' => $request->user()->email,
                            'role' => $request->user()->role,
                            'roles' => $request->user()->getRoleNames()->values()->toArray(),
                            'permissions' => $request->user()->getAllPermissions()->pluck('name')->values()->toArray(),
                            'is_on_duty' => (bool) $request->user()->is_on_duty,
                            'customer' => $customerData,
                        ];
                    })() : null,
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app_logo' => \App\Models\Setting::get('app_logo') ? asset('storage/' . \App\Models\Setting::get('app_logo')) : null,
            'company_logo' => \App\Models\Setting::get('company_logo') ? asset('storage/' . \App\Models\Setting::get('company_logo')) : null,
            'app_name' => \App\Models\Setting::get('app_name', 'ISP Manager'),
            'company_name' => \App\Models\Setting::get('company_name'),
            'company_address' => \App\Models\Setting::get('company_address'),
            'company_phone' => \App\Models\Setting::get('company_phone'),
            'company_email' => \App\Models\Setting::get('company_email'),
            'company_website' => \App\Models\Setting::get('company_website'),
        ];
    }
}
