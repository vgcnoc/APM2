<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                $user = $request->user();
                if (!$user) {
                    return ['user' => null];
                }

                $loginPortal = $request->session()->get('login_portal');
                $rbacVer = Cache::get('inertia_rbac_ver', 1);
                $updatedTs = $user->updated_at ? $user->updated_at->timestamp : 0;
                $cacheKey = "inertia_auth_u{$user->id}_t{$updatedTs}_v{$rbacVer}_p" . ($loginPortal ?: 'std');

                $cachedMeta = Cache::remember($cacheKey, 90, function () use ($user, $loginPortal) {
                    $customerData = null;
                    if ($user->role === 'customer') {
                        $customer = \App\Models\Customer::where('user_id', $user->id)
                            ->first(['id', 'is_reseller', 'package_id']);
                        if ($customer) {
                            $customerData = [
                                'is_reseller' => (bool) $customer->is_reseller,
                                'has_package' => !empty($customer->package_id),
                            ];
                        }
                    } elseif ($user->role === 'reseller' || $loginPortal === 'reseller') {
                        $hasReseller = \App\Models\Reseller::where('user_id', $user->id)->exists();
                        if ($hasReseller) {
                            $customerData = [
                                'is_reseller' => true,
                                'has_package' => false,
                            ];
                        }
                    }

                    return [
                        'roles' => $user->getRoleNames()->values()->toArray(),
                        'permissions' => $user->getAllPermissions()->pluck('name')->values()->toArray(),
                        'accessible_areas' => $user->getAccessibleAreaIds(),
                        'is_affiliate' => (bool) (
                            $user->role === 'sales'
                            || $user->role === 'affiliate'
                            || \App\Models\AffiliateProfile::where('user_id', $user->id)->exists()
                        ),
                        'customer' => $customerData,
                    ];
                });

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'login_portal' => $loginPortal,
                        'affiliate_portal' => \App\Services\AffiliateService::usesAffiliatePortal($request),
                        'roles' => $cachedMeta['roles'],
                        'permissions' => $cachedMeta['permissions'],
                        'area_id' => $user->area_id,
                        'accessible_areas' => $cachedMeta['accessible_areas'],
                        'is_on_duty' => (bool) $user->is_on_duty,
                        'is_affiliate' => $cachedMeta['is_affiliate'],
                        'customer' => $cachedMeta['customer'],
                        'is_gps_enabled' => (bool) $user->is_gps_enabled,
                        'last_location_name' => $user->last_location_name,
                        'last_location_accuracy' => $user->last_location_accuracy,
                    ],
                ];
            },
            'officers' => fn () => \App\Services\AffiliateService::usesAffiliatePortal($request)
                ? []
                : Cache::remember('inertia_on_duty_officers', 45, function () {
                    return \App\Models\User::where('is_on_duty', true)
                        ->get(['id', 'name', 'phone', 'role'])
                        ->toArray();
                }),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'billingSettings' => fn () => [
                'billing_cycle' => \App\Models\Setting::get('billing_cycle', 'anniversary'),
                'billing_type' => \App\Models\Setting::get('billing_type', 'prabayar'),
                'prorata_formula' => \App\Models\Setting::get('prorata_formula', 'exact_days'),
                'prorata_first_month' => \App\Models\Setting::get('prorata_first_month', '1') === '1',
                'invoice_issue_date' => (int) \App\Models\Setting::get('invoice_issue_date', '1'),
                'isolate_days' => (int) \App\Models\Setting::get('isolate_days', '3'),
                'isolate_day' => \App\Models\Setting::get('isolate_day'),
                'isolate_time' => \App\Models\Setting::get('isolate_time', '00:00'),
                'tax_ppn' => (float) \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => (float) \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => (float) \App\Models\Setting::get('tax_uso', '0'),
            ],
            'app_logo' => fn () => \App\Models\Setting::get('app_logo') ? asset('storage/' . \App\Models\Setting::get('app_logo')) : null,
            'app_favicon' => fn () => \App\Models\Setting::get('app_favicon') ? asset('storage/' . \App\Models\Setting::get('app_favicon')) : null,
            'company_logo' => fn () => \App\Models\Setting::get('company_logo') ? asset('storage/' . \App\Models\Setting::get('company_logo')) : null,
            'app_name' => fn () => \App\Models\Setting::get('app_name', 'ISP Manager'),
            'company_name' => fn () => \App\Models\Setting::get('company_name'),
            'company_address' => fn () => \App\Models\Setting::get('company_address'),
            'company_phone' => fn () => \App\Models\Setting::get('company_phone'),
            'company_email' => fn () => \App\Models\Setting::get('company_email'),
            'company_website' => fn () => \App\Models\Setting::get('company_website'),
        ];
    }
}
