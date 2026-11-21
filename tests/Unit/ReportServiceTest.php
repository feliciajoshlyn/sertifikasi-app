<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Verifies ReportService SQL JOIN aggregations (monthly, daily, garage type, and renter),
 * non-duplication of parent totals when multiple extensions exist, empty states, and invalid month guards.
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;

    /**
     * Initialize a fresh ReportService instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService();
    }

    /**
     * Seed a realistic set of multi-month rentals and extensions across renters and garage types.
     *
     * @return void
     */
    private function seedReportDataset(): void
    {
        $renterA = User::create([
            'nik' => '3578000000000101',
            'name' => 'Alpha Renter',
            'phone' => '081200000101',
            'email' => 'alpha@test.local',
            'password' => Hash::make('password'),
            'role' => 'penyewa',
            'status' => 'approved',
        ]);
        $renterB = User::create([
            'nik' => '3578000000000102',
            'name' => 'Beta Renter',
            'phone' => '081200000102',
            'email' => 'beta@test.local',
            'password' => Hash::make('password'),
            'role' => 'penyewa',
            'status' => 'approved',
        ]);

        $car = Vehicle::create([
            'user_id' => $renterA->id,
            'type' => 'mobil',
            'plate_number' => 'L 1010 AA',
            'brand_model' => 'Toyota Avanza',
        ]);
        $ev = Vehicle::create([
            'user_id' => $renterB->id,
            'type' => 'mobil_listrik',
            'plate_number' => 'L 2020 EV',
            'brand_model' => 'Hyundai Ioniq 5',
            'battery_capacity_kwh' => 80.0,
        ]);
        $bike = Vehicle::create([
            'user_id' => $renterB->id,
            'type' => 'sepeda',
            'brand_model' => 'Polygon',
        ]);

        $cGarage = Garage::create(['code' => 'C001', 'type' => 'mobil', 'daily_rate' => 50000.0, 'status' => 'used']);
        $eGarage = Garage::create(['code' => 'E001', 'type' => 'mobil_listrik', 'daily_rate' => 75000.0, 'kwh_price' => 2500.0, 'status' => 'used']);
        $sGarage = Garage::create(['code' => 'S001', 'type' => 'sepeda', 'daily_rate' => 10000.0, 'status' => 'used']);

        // Jan rental by Alpha with TWO extensions (100k + 150k = 250k) to verify no double-counting.
        $janRental = Rental::create([
            'user_id' => $renterA->id,
            'vehicle_id' => $car->id,
            'garage_id' => $cGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-25',
            'duration_days' => 10,
            'base_rental_cost' => 500000.0,
            'charging_cost' => 0.0,
            'total_cost' => 500000.0,
            'payment_proof_path' => 'proofs/jan.jpg',
        ]);

        RentalExtension::create([
            'rental_id' => $janRental->id,
            'previous_end_date' => '2026-01-20',
            'new_end_date' => '2026-01-22',
            'added_days' => 2,
            'extension_cost' => 100000.0,
            'payment_proof_path' => 'proofs/jan_ext1.jpg',
        ]);
        RentalExtension::create([
            'rental_id' => $janRental->id,
            'previous_end_date' => '2026-01-22',
            'new_end_date' => '2026-01-25',
            'added_days' => 3,
            'extension_cost' => 150000.0,
            'payment_proof_path' => 'proofs/jan_ext2.jpg',
        ]);

        // Two November rentals by Beta (EV + Sepeda).
        Rental::create([
            'user_id' => $renterB->id,
            'vehicle_id' => $ev->id,
            'garage_id' => $eGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-15',
            'duration_days' => 10,
            'remaining_battery_kwh' => 20.0,
            'base_rental_cost' => 750000.0,
            'charging_cost' => 150000.0,
            'total_cost' => 900000.0,
            'payment_proof_path' => 'proofs/nov_ev.jpg',
        ]);

        Rental::create([
            'user_id' => $renterB->id,
            'vehicle_id' => $bike->id,
            'garage_id' => $sGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-12',
            'end_date' => '2026-11-22',
            'duration_days' => 10,
            'base_rental_cost' => 100000.0,
            'charging_cost' => 0.0,
            'total_cost' => 100000.0,
            'payment_proof_path' => 'proofs/nov_bike.jpg',
        ]);
    }

    /**
     * Verify monthly report aggregates correctly by month without duplicating totals across multiple extensions.
     *
     * @return void
     */
    public function test_get_monthly_report_aggregates_without_extension_duplication(): void
    {
        $this->seedReportDataset();

        $rows = $this->reportService->getMonthlyReport(2026);

        $this->assertCount(2, $rows);

        $this->assertSame(1, (int) $rows[0]->month);
        $this->assertSame(1, (int) $rows[0]->total_rentals);
        $this->assertEqualsWithDelta(500000.0, (float) $rows[0]->base_revenue, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $rows[0]->charging_revenue, 0.001);
        $this->assertEqualsWithDelta(250000.0, (float) $rows[0]->extension_revenue, 0.001);
        $this->assertEqualsWithDelta(750000.0, (float) $rows[0]->total_revenue, 0.001);

        $this->assertSame(11, (int) $rows[1]->month);
        $this->assertSame(2, (int) $rows[1]->total_rentals);
        $this->assertEqualsWithDelta(850000.0, (float) $rows[1]->base_revenue, 0.001);
        $this->assertEqualsWithDelta(150000.0, (float) $rows[1]->charging_revenue, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $rows[1]->extension_revenue, 0.001);
        $this->assertEqualsWithDelta(1000000.0, (float) $rows[1]->total_revenue, 0.001);
    }

    /**
     * Verify daily report returns chronological per-date rows scoped to the requested year and month.
     *
     * @return void
     */
    public function test_get_daily_report_filters_month_and_orders_chronologically(): void
    {
        $this->seedReportDataset();

        $rows = $this->reportService->getDailyReport(2026, 11);

        $this->assertCount(2, $rows);
        $this->assertSame('2026-11-05', $rows[0]->rental_date);
        $this->assertEqualsWithDelta(900000.0, (float) $rows[0]->total_revenue, 0.001);
        $this->assertSame('2026-11-12', $rows[1]->rental_date);
        $this->assertEqualsWithDelta(100000.0, (float) $rows[1]->total_revenue, 0.001);
    }

    /**
     * Verify garage-type and renter reports join related tables and rank renters by descending revenue.
     *
     * @return void
     */
    public function test_get_report_by_garage_type_and_renter(): void
    {
        $this->seedReportDataset();

        $byGarageType = $this->reportService->getReportByGarageType();
        $byRenter = $this->reportService->getReportByRenter();

        $this->assertCount(3, $byGarageType);
        $this->assertSame('mobil', $byGarageType[0]->garage_type);
        $this->assertEqualsWithDelta(750000.0, (float) $byGarageType[0]->total_revenue, 0.001);
        $this->assertSame('mobil_listrik', $byGarageType[1]->garage_type);
        $this->assertEqualsWithDelta(900000.0, (float) $byGarageType[1]->total_revenue, 0.001);
        $this->assertSame('sepeda', $byGarageType[2]->garage_type);
        $this->assertEqualsWithDelta(100000.0, (float) $byGarageType[2]->total_revenue, 0.001);

        $this->assertCount(2, $byRenter);
        $this->assertSame('Beta Renter', $byRenter[0]->renter_name);
        $this->assertSame('beta@test.local', $byRenter[0]->renter_email);
        $this->assertEqualsWithDelta(1000000.0, (float) $byRenter[0]->total_revenue, 0.001);
        $this->assertSame('Alpha Renter', $byRenter[1]->renter_name);
        $this->assertEqualsWithDelta(750000.0, (float) $byRenter[1]->total_revenue, 0.001);
    }

    /**
     * Verify all report methods return an empty array when the database has no matching rentals,
     * and getDailyReport rejects months outside 1..12.
     *
     * @return void
     */
    public function test_empty_dataset_edge_case_and_invalid_month_guard(): void
    {
        $this->assertSame([], $this->reportService->getMonthlyReport(2026));
        $this->assertSame([], $this->reportService->getDailyReport(2026, 11));
        $this->assertSame([], $this->reportService->getReportByGarageType());
        $this->assertSame([], $this->reportService->getReportByRenter());

        foreach ([0, 13] as $invalidMonth) {
            try {
                $this->reportService->getDailyReport(2026, $invalidMonth);
                $this->fail("Expected InvalidArgumentException for month {$invalidMonth}.");
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('between 1 and 12', $exception->getMessage());
            }
        }
    }
}
