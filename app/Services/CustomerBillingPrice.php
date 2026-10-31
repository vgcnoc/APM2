<?php

namespace App\Services;

use App\Models\Customer;

class CustomerBillingPrice
{
    /**
     * Resolve the monthly pre-tax tariff used for billing.
     *
     * Imported customers follow the currently selected package tariff. Their
     * legacy base_amount can contain a tax-inclusive value from the source
     * workbook, so it must not be used as an additional billing base.
     */
    public static function monthly(Customer $customer): float
    {
        if ($customer->is_migrated && $customer->package) {
            return (float) $customer->package->current_price;
        }

        if ($customer->base_amount > 0) {
            return (float) $customer->base_amount;
        }

        return (float) ($customer->package?->current_price ?? 0);
    }
}
