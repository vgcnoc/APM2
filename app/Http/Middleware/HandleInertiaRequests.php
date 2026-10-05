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
                        if (in_array($request->user()->role, ['customer', 'reseller'])) {
                            $customer = \App\Models\Customer::where('user_id', $request->user()->id)->first();
                            if ($customer) {
                                $customerData = [
                                    'is_reseller' => (bool) $customer->is_reseller,
                                    'has_package' => !empty($customer->package_id),
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
            'app_name' => \App\Models\Setting::get('app_name', 'ISP Manager'),
        ];
    }
}
