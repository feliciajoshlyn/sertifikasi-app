<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Pricing\DailyPricingStrategy;
use App\Domain\Pricing\MonthlyPricingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies daily and monthly pricing strategy calculations (normal and boundary cases)
 * to ensure base rental fee formulas remain isolated from persistence logic.
 */
class PricingStrategyTest extends TestCase
{
    /**
     * Verify DailyPricingStrategy multiplies rented days by the garage daily rate for normal durations.
     *
     * @return void
     */
    public function test_daily_pricing_calculates_exact_day_rate_total(): void
    {
        $strategy = new DailyPricingStrategy();

        $this->assertEqualsWithDelta(500000.0, $strategy->calculateBaseFee(10, 50000.0), 0.001);
    }

    /**
     * Verify DailyPricingStrategy accurately calculates the minimum 1-day rental boundary.
     *
     * @return void
     */
    public function test_daily_pricing_handles_single_day_minimum_boundary(): void
    {
        $strategy = new DailyPricingStrategy();

        $this->assertEqualsWithDelta(15000.0, $strategy->calculateBaseFee(1, 15000.0), 0.001);
    }

    /**
     * Verify MonthlyPricingStrategy bills a flat 25x multiplier regardless of calendar month length (28-31 days).
     *
     * @return void
     */
    public function test_monthly_pricing_applies_twenty_five_day_multiplier_across_month_lengths(): void
    {
        $strategy = new MonthlyPricingStrategy();

        $this->assertSame(25, MonthlyPricingStrategy::monthlyMultiplier());

        foreach ([28, 29, 30, 31] as $monthDays) {
            $this->assertEqualsWithDelta(
                1250000.0,
                $strategy->calculateBaseFee($monthDays, 50000.0),
                0.001,
                "Monthly rate mismatch for {$monthDays}-day month."
            );
        }
    }
}
