<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Computes the base rental fee for daily (harian) bookings by multiplying
 * the exact number of rented days by the garage daily rate.
 */
class DailyPricingStrategy implements PricingStrategyInterface
{
    /**
     * Calculate the daily base rental fee.
     *
     * @param int   $days      Number of rented days.
     * @param float $dailyRate Daily rate of the garage unit.
     *
     * @return float Base rental fee for the given days.
     */
    public function calculateBaseFee(int $days, float $dailyRate): float
    {
        return $days * $dailyRate;
    }
}
