<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Computes the discounted base rental fee for full calendar-month (bulanan)
 * bookings using the fixed 25-day monthly multiplier.
 */
class MonthlyPricingStrategy implements PricingStrategyInterface
{
    private const MONTHLY_MULTIPLIER = 25;

    /**
     * Expose the fixed monthly billing multiplier so dependent services avoid duplicating it.
     *
     * @return int Number of daily-rate units billed per full calendar month.
     */
    public static function monthlyMultiplier(): int
    {
        return self::MONTHLY_MULTIPLIER;
    }

    /**
     * Calculate the monthly base rental fee at 25x the daily rate per full month.
     *
     * @param int   $days      Number of days in the full calendar month.
     * @param float $dailyRate Daily rate of the garage unit.
     *
     * @return float Discounted monthly base rental fee.
     */
    public function calculateBaseFee(int $days, float $dailyRate): float
    {
        return $dailyRate * self::MONTHLY_MULTIPLIER;
    }
}
