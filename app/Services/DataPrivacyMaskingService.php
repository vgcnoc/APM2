<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerBillingPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataPrivacyMaskingService
{
    /**
     * Cache key for masked customer IDs
     */
    const CACHE_KEY_CUSTOMER_IDS = 'data_masking_active_customer_ids';
    const CACHE_KEY_SUMMARY = 'data_masking_active_summary';

    /**
     * Get all settings with defaults
     */
    public static function getSettings(): array
    {
        return [
            'enabled' => Setting::get('data_masking_enabled', '0') === '1',
            'mode' => Setting::get('data_masking_mode', 'omset'), // 'omset' or 'customer_count'
            'target_omset' => (float) Setting::get('data_masking_target_omset', '100000000'),
            'target_customers' => (int) Setting::get('data_masking_target_customers', '500'),
            'selection_method' => Setting::get('data_masking_selection_method', 'fifo'), // 'fifo', 'alphabetical', 'price_asc', 'price_desc'
            'scope' => Setting::get('data_masking_scope', 'all_except_super_admin'), // 'all_except_super_admin', 'specific_accounts', 'everyone'
            'target_roles' => json_decode(Setting::get('data_masking_target_roles', '[]'), true) ?: [],
            'target_users' => json_decode(Setting::get('data_masking_target_users', '[]'), true) ?: [],
            'hide_sidebar_menu' => Setting::get('data_masking_hide_sidebar_menu', '0') === '1',
            'modules' => json_decode(Setting::get('data_masking_modules', '["tax_report", "customers", "invoices", "billing", "financial_reports"]'), true) ?: [
                'tax_report', 'customers', 'invoices', 'billing', 'financial_reports'
            ],
            'summary' => json_decode(Setting::get('data_masking_cached_summary', '{}'), true) ?: [],
        ];
    }

    /**
     * Save settings and trigger recalculation
     */
    public static function updateSettings(array $data): array
    {
        if (isset($data['enabled'])) {
            Setting::set('data_masking_enabled', $data['enabled'] ? '1' : '0');
        }
        if (isset($data['mode'])) {
            Setting::set('data_masking_mode', in_array($data['mode'], ['omset', 'customer_count']) ? $data['mode'] : 'omset');
        }
        if (isset($data['target_omset'])) {
            $omset = (float) str_replace(['.', ',', ' '], '', (string) $data['target_omset']);
            Setting::set('data_masking_target_omset', (string) max(0, $omset));
        }
        if (isset($data['target_customers'])) {
            $custs = (int) $data['target_customers'];
            Setting::set('data_masking_target_customers', (string) max(1, $custs));
        }
        if (isset($data['selection_method'])) {
            Setting::set('data_masking_selection_method', $data['selection_method']);
        }
        if (isset($data['scope'])) {
            Setting::set('data_masking_scope', $data['scope']);
        }
        if (isset($data['target_roles'])) {
            Setting::set('data_masking_target_roles', json_encode(array_values($data['target_roles'])));
        }
        if (isset($data['target_users'])) {
            Setting::set('data_masking_target_users', json_encode(array_map('intval', array_values($data['target_users']))));
        }
        if (isset($data['hide_sidebar_menu'])) {
            Setting::set('data_masking_hide_sidebar_menu', $data['hide_sidebar_menu'] ? '1' : '0');
        }
        if (isset($data['modules'])) {
            Setting::set('data_masking_modules', json_encode(array_values($data['modules'])));
        }

        // Always recalculate and cache the result
        return self::recalculateAndCache();
    }

    /**
     * Recalculate customer IDs matching the current configuration and persist to cache
     */
    public static function recalculateAndCache(): array
    {
        $settings = self::getSettings();
        $simulation = self::simulate([
            'mode' => $settings['mode'],
            'target_omset' => $settings['target_omset'],
            'target_customers' => $settings['target_customers'],
            'selection_method' => $settings['selection_method'],
        ]);

        $customerIds = $simulation['selected_ids'] ?? [];
        
        Setting::set('data_masking_cached_customer_ids', json_encode($customerIds));
        
        $summary = [
            'selected_count' => count($customerIds),
            'total_active' => $simulation['total_active'] ?? 0,
            'reached_omset' => $simulation['reached_omset'] ?? 0,
            'target_omset' => $settings['target_omset'],
            'difference' => $simulation['difference'] ?? 0,
            'base_price' => $simulation['base_price'] ?? 0,
            'ppn' => $simulation['ppn'] ?? 0,
            'bhp_uso' => $simulation['bhp_uso'] ?? 0,
            'updated_at' => now()->toIso8601String(),
        ];
        
        Setting::set('data_masking_cached_summary', json_encode($summary));
        
        Cache::forever(self::CACHE_KEY_CUSTOMER_IDS, $customerIds);
        Cache::forever(self::CACHE_KEY_SUMMARY, $summary);

        return $summary;
    }

    /**
     * Simulate customer selection without altering database settings
     */
    public static function simulate(array $params): array
    {
        $mode = $params['mode'] ?? 'omset';
        $targetOmset = (float) str_replace(['.', ',', ' '], '', (string) ($params['target_omset'] ?? 100000000));
        $targetCustomers = (int) ($params['target_customers'] ?? 500);
        $selectionMethod = $params['selection_method'] ?? 'fifo';

        $globalTaxPpn = (float) Setting::get('tax_ppn', '0');
        $globalBhp = (float) Setting::get('tax_bhp', '1.25');
        $globalUso = (float) Setting::get('tax_uso', '0.5');

        $query = Customer::with(['package', 'invoices' => fn($q) => $q->where('is_reseller_balance', false)
            ->where('period_month', now()->month)
            ->where('period_year', now()->year)
            ->latest('id')])
            ->where('status', 'active');

        switch ($selectionMethod) {
            case 'alphabetical':
                $query->orderBy('name', 'asc');
                break;
            case 'price_asc':
                $query->leftJoin('internet_packages', 'customers.package_id', '=', 'internet_packages.id')
                    ->orderBy('internet_packages.price', 'asc')
                    ->orderBy('customers.id', 'asc')
                    ->select('customers.*');
                break;
            case 'price_desc':
                $query->leftJoin('internet_packages', 'customers.package_id', '=', 'internet_packages.id')
                    ->orderBy('internet_packages.price', 'desc')
                    ->orderBy('customers.id', 'asc')
                    ->select('customers.*');
                break;
            case 'fifo':
            default:
                $query->orderBy('id', 'asc');
                break;
        }

        $allCustomers = $query->get();
        $totalActive = $allCustomers->count();

        $selectedIds = [];
        $runningOmset = 0.0;
        $totalBasePrice = 0.0;
        $totalPpn = 0.0;
        $totalBhp = 0.0;
        $totalUso = 0.0;
        $sampleCustomers = [];

        foreach ($allCustomers as $cust) {
            $ppn_percent = $cust->tax_ppn !== null ? (float) $cust->tax_ppn : $globalTaxPpn;
            if ($cust->tax_bhp !== null && (float) $cust->tax_bhp > 0) {
                $bhp_percent = (float) $cust->tax_bhp;
            } elseif ($cust->service_status !== 'gratis' && $ppn_percent > 0) {
                $bhp_percent = $globalBhp;
            } else {
                $bhp_percent = 0.0;
            }

            if ($cust->tax_uso !== null && (float) $cust->tax_uso > 0) {
                $uso_percent = (float) $cust->tax_uso;
            } elseif ($cust->service_status !== 'gratis' && $ppn_percent > 0) {
                $uso_percent = $globalUso;
            } else {
                $uso_percent = 0.0;
            }

            $invoice = $cust->invoices->where('period_month', now()->month)->where('period_year', now()->year)->first();

            if ($cust->service_status === 'gratis') {
                $basePrice = 0;
                $ppn_amount = 0;
                $total_price = 0;
            } elseif ($invoice && $invoice->amount > 0) {
                if ($ppn_percent > 0) {
                    $basePrice = (int) round((float) $invoice->amount / (1 + ($ppn_percent / 100)));
                    $ppn_amount = (int) ($invoice->amount - $basePrice);
                } else {
                    $basePrice = (int) round((float) $invoice->amount);
                    $ppn_amount = 0;
                }
                $total_price = (int) $invoice->amount;
            } else {
                $basePrice = (int) round(CustomerBillingPrice::monthly($cust));
                $ppn_amount = (int) round($basePrice * ($ppn_percent / 100));
                $total_price = (int) ($basePrice + $ppn_amount);
            }

            $bhp_amount = (int) round($basePrice * ($bhp_percent / 100));
            $uso_amount = (int) round($basePrice * ($uso_percent / 100));

            // Mode: Customer Count
            if ($mode === 'customer_count') {
                $selectedIds[] = $cust->id;
                $runningOmset += $total_price;
                $totalBasePrice += $basePrice;
                $totalPpn += $ppn_amount;
                $totalBhp += $bhp_amount;
                $totalUso += $uso_amount;

                if (count($sampleCustomers) < 5 || count($selectedIds) <= $targetCustomers) {
                    $sampleCustomers[] = [
                        'id' => $cust->id,
                        'name' => $cust->name,
                        'customer_code' => $cust->customer_code,
                        'package_name' => $cust->package?->name ?? 'Default',
                        'base_price' => $basePrice,
                        'total_price' => $total_price,
                    ];
                }

                if (count($selectedIds) >= $targetCustomers) {
                    break;
                }
                continue;
            }

            // Mode: Omset Target
            if ($runningOmset + $total_price > $targetOmset && count($selectedIds) > 0) {
                $diffBefore = abs($targetOmset - $runningOmset);
                $diffAfter = abs($targetOmset - ($runningOmset + $total_price));
                if ($diffAfter < $diffBefore) {
                    $selectedIds[] = $cust->id;
                    $runningOmset += $total_price;
                    $totalBasePrice += $basePrice;
                    $totalPpn += $ppn_amount;
                    $totalBhp += $bhp_amount;
                    $totalUso += $uso_amount;
                    $sampleCustomers[] = [
                        'id' => $cust->id,
                        'name' => $cust->name,
                        'customer_code' => $cust->customer_code,
                        'package_name' => $cust->package?->name ?? 'Default',
                        'base_price' => $basePrice,
                        'total_price' => $total_price,
                    ];
                }
                break;
            }

            $selectedIds[] = $cust->id;
            $runningOmset += $total_price;
            $totalBasePrice += $basePrice;
            $totalPpn += $ppn_amount;
            $totalBhp += $bhp_amount;
            $totalUso += $uso_amount;

            if (count($sampleCustomers) < 8) {
                $sampleCustomers[] = [
                    'id' => $cust->id,
                    'name' => $cust->name,
                    'customer_code' => $cust->customer_code,
                    'package_name' => $cust->package?->name ?? 'Default',
                    'base_price' => $basePrice,
                    'total_price' => $total_price,
                ];
            }
        }

        return [
            'total_active' => $totalActive,
            'selected_count' => count($selectedIds),
            'target_omset' => $targetOmset,
            'target_customers' => $targetCustomers,
            'reached_omset' => $runningOmset,
            'difference' => $mode === 'omset' ? ($runningOmset - $targetOmset) : 0,
            'base_price' => $totalBasePrice,
            'ppn' => $totalPpn,
            'bhp_amount' => $totalBhp,
            'uso_amount' => $totalUso,
            'bhp_uso' => $totalBhp + $totalUso,
            'grand_total' => $runningOmset,
            'sample_customers' => array_slice($sampleCustomers, 0, 6),
            'selected_ids' => $selectedIds,
        ];
    }

    /**
     * Check if masking is active for a given user regardless of module
     */
    public static function isMaskingActiveForUser(?User $user = null): bool
    {
        $settings = self::getSettings();
        if (!$settings['enabled']) {
            return false;
        }

        if ($user === null) {
            $user = auth()->user();
        }

        if (!$user) {
            return false;
        }

        // Preview Mode for Super Admin: Super Admin can toggle preview on/off in session
        if (self::isSuperAdmin($user)) {
            return (bool) session('data_masking_preview_mode', false);
        }

        // Normal accounts: check scope
        $scope = $settings['scope'];
        if ($scope === 'everyone') {
            return true;
        }

        if ($scope === 'all_except_super_admin') {
            return !self::isSuperAdmin($user);
        }

        if ($scope === 'specific_accounts') {
            // Check specific user IDs
            if (in_array($user->id, $settings['target_users'])) {
                return true;
            }

            // Check roles
            if (!empty($settings['target_roles'])) {
                foreach ($settings['target_roles'] as $roleName) {
                    if (method_exists($user, 'hasRole') && $user->hasRole($roleName)) {
                        return true;
                    }
                    if (($user->role ?? '') === $roleName) {
                        return true;
                    }
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Check if masking should apply for a given user & module
     */
    public static function isEnabledForUser(?User $user = null, string $module = 'tax_report'): bool
    {
        if (!self::isMaskingActiveForUser($user)) {
            return false;
        }

        $settings = self::getSettings();
        if (!empty($settings['modules']) && !in_array($module, $settings['modules'])) {
            return false;
        }

        return true;
    }

    /**
     * Helper to verify if user is super admin
     */
    public static function isSuperAdmin(?User $user): bool
    {
        if (!$user) return false;
        if (method_exists($user, 'hasRole') && ($user->hasRole('super admin') || $user->hasRole('admin'))) {
            return true;
        }
        $roleLower = strtolower($user->role ?? '');
        return in_array($roleLower, ['super admin', 'admin']);
    }

    /**
     * Get masked customer IDs
     */
    public static function getMaskedCustomerIds(): array
    {
        $cached = Cache::get(self::CACHE_KEY_CUSTOMER_IDS);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $fromDb = json_decode(Setting::get('data_masking_cached_customer_ids', '[]'), true);
        if (is_array($fromDb) && !empty($fromDb)) {
            Cache::forever(self::CACHE_KEY_CUSTOMER_IDS, $fromDb);
            return $fromDb;
        }

        // Compute and cache if missing
        self::recalculateAndCache();
        return Cache::get(self::CACHE_KEY_CUSTOMER_IDS, []);
    }

    /**
     * Apply filter on Customer query
     */
    public static function applyCustomerFilter($query, string $idColumn = 'id', ?User $user = null, string $module = 'tax_report')
    {
        if (self::isEnabledForUser($user, $module)) {
            $ids = self::getMaskedCustomerIds();
            $query->whereIn($idColumn, !empty($ids) ? $ids : [0]);
        }
        return $query;
    }

    /**
     * Apply filter on Invoice query (by customer_id)
     */
    public static function applyInvoiceFilter($query, string $customerIdColumn = 'customer_id', ?User $user = null, string $module = 'invoices')
    {
        if (self::isEnabledForUser($user, $module)) {
            $ids = self::getMaskedCustomerIds();
            $query->whereIn($customerIdColumn, !empty($ids) ? $ids : [0]);
        }
        return $query;
    }

    /**
     * Check if a route/path should be hidden from sidebar for current user
     */
    public static function shouldHideFromSidebar(string $path, ?User $user = null): bool
    {
        $settings = self::getSettings();
        if (!$settings['hide_sidebar_menu']) {
            return false;
        }

        if (!self::isMaskingActiveForUser($user)) {
            return false;
        }

        $moduleMap = [
            '/customers/booking' => 'booking',
            '/customers/survey' => 'survey',
            '/customers/installed' => 'installed',
            '/customers/activation' => 'activation',
            '/customers' => 'customers',
            '/monitoring-pelanggan' => 'monitoring_pelanggan',
            '/invoices' => 'invoices',
            '/customer-billing' => 'billing',
            '/customer-settlements' => 'billing',
            '/tax-reports' => 'tax_report',
            '/financial-reports' => 'financial_reports',
            '/customer-reports' => 'financial_reports',
            '/network-data' => 'network_data',
            '/onts' => 'onts',
            '/network-map' => 'network_map',
            '/network-radar' => 'network_map',
            '/pgk' => 'pgk_tickets',
            '/monitoring-teknisi' => 'technician_monitoring',
        ];

        foreach ($moduleMap as $prefix => $mod) {
            if (str_contains($path, $prefix) && in_array($mod, $settings['modules'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Masking Info for current user/UI
     */
    public static function getMaskingInfoForUser(?User $user = null): array
    {
        if ($user === null) {
            $user = auth()->user();
        }

        $settings = self::getSettings();
        $isSuperAdmin = self::isSuperAdmin($user);
        $previewMode = (bool) session('data_masking_preview_mode', false);
        $isMasked = self::isMaskingActiveForUser($user);

        return [
            'is_masked' => $isMasked,
            'is_configured' => $settings['enabled'],
            'mode' => $settings['mode'],
            'target_omset' => $settings['target_omset'],
            'target_customers' => $settings['target_customers'],
            'selection_method' => $settings['selection_method'],
            'scope' => $settings['scope'],
            'is_super_admin' => $isSuperAdmin,
            'preview_mode' => $previewMode,
            'summary' => $settings['summary'],
            'modules' => $settings['modules'],
            'hide_sidebar_menu' => $settings['hide_sidebar_menu'],
            'masked_modules' => $isMasked ? $settings['modules'] : [],
        ];
    }
}

