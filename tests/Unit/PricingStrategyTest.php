<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Pricing\DailyPricingStrategy;
use App\Domain\Pricing\MonthlyPricingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Check daily and monthly pricing formulas independently from the database
 * so base rental fee math stays accurate across durations.
 */
class PricingStrategyTest extends TestCase
{
    /**
     * Check normal daily rate calculation (days multiplied by garage daily rate).
     *
     * @return void
     */
    public function test_daily_rate_calculation(): void
    {
        $strategy = new DailyPricingStrategy();

        $this->assertEqualsWithDelta(500000.0, $strategy->calculateBaseFee(10, 50000.0), 0.001);
    }

    /**
     * Check the shortest valid booking boundary (1 day).
     *
     * @return void
     */
    public function test_single_day_rental(): void
    {
        $strategy = new DailyPricingStrategy();

        $this->assertEqualsWithDelta(15000.0, $strategy->calculateBaseFee(1, 15000.0), 0.001);
    }

    /**
     * Check monthly pricing always bills 25x daily rate no matter how many days are in the month (28-31).
     *
     * @return void
     */
    public function test_monthly_rate_always_uses_25x_multiplier(): void
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
