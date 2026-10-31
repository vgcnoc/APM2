<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;

class ProrataCalculator
{
    /**
     * Calculate the first calendar-month invoice from an activation date.
     */
    public static function firstMonthAmount(float $baseAmount, CarbonInterface $activationDate, ?string $formula = null): int
    {
        $daysInMonth = (int) $activationDate->daysInMonth;
        $daysUsed = $daysInMonth - (int) $activationDate->day + 1;

        return self::amountForDays($baseAmount, $daysUsed, $daysInMonth, $formula);
    }

    /**
     * Use one canonical formula everywhere invoices are generated.
     */
    public static function amountForDays(float $baseAmount, int $daysUsed, int $daysInMonth, ?string $formula = null): int
    {
        if ($baseAmount <= 0 || $daysUsed <= 0 || $daysInMonth <= 0) {
            return 0;
        }

        if ($daysUsed >= $daysInMonth) {
            return (int) round($baseAmount);
        }

        $formula ??= Setting::get('prorata_formula', 'exact_days');

        return match ($formula) {
            'fixed_30' => (int) round(($baseAmount / 30) * $daysUsed),
            'mid_month' => $daysUsed < ($daysInMonth - 14)
                ? (int) round($baseAmount / 2)
                : (int) round($baseAmount),
            default => (int) round(($baseAmount / $daysInMonth) * $daysUsed),
        };
    }
}
