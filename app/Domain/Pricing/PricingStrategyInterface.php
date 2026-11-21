<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * Defines the contract for garage rental base fee calculation strategies
 * so daily and monthly pricing rules can be swapped polymorphically.
 */
interface PricingStrategyInterface
{
    /**
     * Calculate the base garage rental fee before any EV charging costs.
     *
     * @param int   $days      Number of rental days in the billing period.
     * @param float $dailyRate Daily rate of the assigned garage unit.
     *
     * @return float Calculated base rental fee.
     */
    public function calculateBaseFee(int $days, float $dailyRate): float;
}
