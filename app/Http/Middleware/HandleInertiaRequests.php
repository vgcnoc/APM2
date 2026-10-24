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
                            'affiliate_portal' => \App\Services\AffiliateService::usesAffiliatePortal($request),
                            'roles' => $request->user()->getRoleNames()->values()->toArray(),
                            'permissions' => $request->user()->getAllPermissions()->pluck('name')->values()->toArray(),
                            'area_id' => $request->user()->area_id,
                            'accessible_areas' => $request->user()->getAccessibleAreaIds(),
                            'is_on_duty' => (bool) $request->user()->is_on_duty,
                            'is_affiliate' => (bool) (
                                $request->user()->role === 'sales'
                                || $request->user()->role === 'affiliate'
                                || \App\Models\AffiliateProfile::where('user_id', $request->user()->id)->exists()
                            ),
                            'customer' => $customerData,
                        ];
                    })() : null,
                ];
            },
            'officers' => fn () => \App\Services\AffiliateService::usesAffiliatePortal($request)
                ? [] : \App\Models\User::where('is_on_duty', true)->get(['id', 'name', 'phone', 'role']),
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
                'isolate_time' => \App\Models\Setting::get('isolate_time', '00:00'),
                'tax_ppn' => (float) \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => (float) \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => (float) \App\Models\Setting::get('tax_uso', '0'),
            ],
            'app_logo' => \App\Models\Setting::get('app_logo') ? asset('storage/' . \App\Models\Setting::get('app_logo')) : null,
            'app_favicon' => \App\Models\Setting::get('app_favicon') ? asset('storage/' . \App\Models\Setting::get('app_favicon')) : null,
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
