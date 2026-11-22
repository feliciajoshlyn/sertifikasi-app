<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Check RentalService booking flows, inline new vehicle saving, calendar-month rules,
 * EV charging calculation, overlap guards, and rental extensions.
 */
class RentalServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalService $rentalService;

    /**
     * Prepare a fresh RentalService instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->rentalService = new RentalService();
    }

    /**
     * Reset mocked Carbon test dates after each test so dates never leak to other tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Create a dummy approved Penyewa user for test bookings.
     *
     * @param string $suffix Unique suffix for email and NIK.
     *
     * @return User Saved Penyewa user model.
     */
    private function createRenter(string $suffix = '1'): User
    {
        return User::create([
            'nik' => str_pad($suffix, 16, '3', STR_PAD_LEFT),
            'name' => "Renter {$suffix}",
            'phone' => "0812000000{$suffix}",
            'email' => "renter{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role' => 'penyewa',
            'status' => 'approved',
        ]);
    }

    /**
     * Create a dummy vehicle owned by a renter for test bookings.
     *
     * @param User        $renter          Owner user model.
     * @param string      $type            Vehicle type key.
     * @param string|null $plateNumber     Plate number (null for bicycles).
     * @param float|null  $batteryCapacity EV full capacity in kWh when applicable.
     *
     * @return Vehicle Saved Vehicle model.
     */
    private function createVehicle(
        User $renter,
        string $type = 'mobil',
        ?string $plateNumber = 'L 1234 AB',
        ?float $batteryCapacity = null
    ): Vehicle {
        return Vehicle::create([
            'user_id' => $renter->id,
            'type' => $type,
            'plate_number' => $plateNumber,
            'brand_model' => "Model {$type}",
            'battery_capacity_kwh' => $batteryCapacity,
        ]);
    }

    /**
     * Create a dummy available garage unit for test bookings.
     *
     * @param string     $code      Unique garage code (S/C/E).
     * @param string     $type      Garage type key.
     * @param float      $dailyRate Daily rental tariff.
     * @param float|null $kwhPrice  Electricity price per kWh for EV garages.
     *
     * @return Garage Saved Garage model.
     */
    private function createGarage(
        string $code = 'C001',
        string $type = 'mobil',
        float $dailyRate = 50000.0,
        ?float $kwhPrice = null
    ): Garage {
        return Garage::create([
            'code' => $code,
            'type' => $type,
            'daily_rate' => $dailyRate,
            'kwh_price' => $kwhPrice,
            'status' => 'available',
        ]);
    }

    /**
     * Check normal daily booking saves the right end date, cost, and marks the garage as used.
     *
     * @return void
     */
    public function test_daily_rental_booking(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 10));

        $renter = $this->createRenter();
        $vehicle = $this->createVehicle($renter);
        $garage = $this->createGarage('C001', 'mobil', 50000.0);

        $rental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $vehicle->id,
            'garage_id' => $garage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-01-10',
            'duration_days' => 10,
            'payment_proof_path' => 'proofs/daily.jpg',
        ]);

        $this->assertSame('harian', $rental->rental_type);
        $this->assertSame('2026-01-20', $rental->end_date->toDateString());
        $this->assertEqualsWithDelta(500000.0, (float) $rental->base_rental_cost, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $rental->charging_cost, 0.001);
        $this->assertEqualsWithDelta(500000.0, (float) $rental->total_cost, 0.001);
        $this->assertSame('used', $garage->fresh()->status);
    }

    /**
     * Check EV charges battery fee in E garage, while EV in normal C garage pays Rp 0 charging fee.
     *
     * @return void
     */
    public function test_ev_charging_in_ev_vs_normal_garage(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 21));

        $renter = $this->createRenter();
        $evOne = $this->createVehicle($renter, 'mobil_listrik', 'L 5678 EV', 80.0);
        $evTwo = $this->createVehicle($renter, 'mobil_listrik', 'L 9012 EV', 80.0);
        $eGarage = $this->createGarage('E001', 'mobil_listrik', 75000.0, 2500.0);
        $cGarage = $this->createGarage('C001', 'mobil', 50000.0);

        $evRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $evOne->id,
            'garage_id' => $eGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-21',
            'duration_days' => 2,
            'remaining_battery_kwh' => 20.0,
            'payment_proof_path' => 'proofs/ev_e.jpg',
        ]);

        $normalCarRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $evTwo->id,
            'garage_id' => $cGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-21',
            'duration_days' => 2,
            'payment_proof_path' => 'proofs/ev_c.jpg',
        ]);

        $this->assertEqualsWithDelta(150000.0, (float) $evRental->base_rental_cost, 0.001);
        $this->assertEqualsWithDelta(150000.0, (float) $evRental->charging_cost, 0.001);
        $this->assertEqualsWithDelta(300000.0, (float) $evRental->total_cost, 0.001);

        $this->assertEqualsWithDelta(100000.0, (float) $normalCarRental->base_rental_cost, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $normalCarRental->charging_cost, 0.001);
        $this->assertNull($normalCarRental->remaining_battery_kwh);
        $this->assertEqualsWithDelta(100000.0, (float) $normalCarRental->total_cost, 0.001);
    }

    /**
     * Check inline vehicle registration saves uppercase motorbike plates and keeps bicycle plate/kWh null.
     *
     * @return void
     */
    public function test_add_new_vehicle_during_booking(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 21));

        $renter = $this->createRenter();
        $s1 = $this->createGarage('S001', 'sepeda', 15000.0);
        $s2 = $this->createGarage('S002', 'sepeda', 10000.0);

        $motorRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'new_vehicle' => [
                'type' => 'sepeda_motor',
                'plate_number' => '  l 7777 aa ',
                'brand_model' => 'Honda PCX',
            ],
            'garage_id' => $s1->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-21',
            'duration_days' => 3,
            'payment_proof_path' => 'proofs/motor.jpg',
        ]);

        $bikeRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'new_vehicle' => [
                'type' => 'sepeda',
                'brand_model' => 'Polygon Strattos',
            ],
            'garage_id' => $s2->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-21',
            'duration_days' => 2,
            'payment_proof_path' => 'proofs/bike.jpg',
        ]);

        $this->assertSame('L 7777 AA', $motorRental->vehicle->plate_number);
        $this->assertSame('used', $s1->fresh()->status);

        $this->assertNull($bikeRental->vehicle->plate_number);
        $this->assertNull($bikeRental->vehicle->battery_capacity_kwh);
    }

    /**
     * Check real calendar-month limits across leap Feb (29d), Jan (31d), and non-leap Feb (max 27d daily).
     *
     * @return void
     */
    public function test_calendar_month_limits(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 2, 1));
        $renter = $this->createRenter('1');
        $carOne = $this->createVehicle($renter, 'mobil', 'L 2024 AA');
        $c1 = $this->createGarage('C001', 'mobil', 50000.0);

        $leapMonthly = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $carOne->id,
            'garage_id' => $c1->id,
            'rental_type' => 'bulanan',
            'start_date' => '2024-02-01',
            'payment_proof_path' => 'proofs/leap.jpg',
        ]);

        $this->assertSame('bulanan', $leapMonthly->rental_type);
        $this->assertSame(29, $leapMonthly->duration_days);
        $this->assertSame('2024-03-01', $leapMonthly->end_date->toDateString());
        $this->assertEqualsWithDelta(1250000.0, (float) $leapMonthly->base_rental_cost, 0.001);

        Carbon::setTestNow(Carbon::create(2026, 1, 29));
        $carTwo = $this->createVehicle($renter, 'mobil', 'L 2026 BB');
        $c2 = $this->createGarage('C002', 'mobil', 50000.0);

        $janMonthly = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $carTwo->id,
            'garage_id' => $c2->id,
            'start_date' => '2026-01-29',
            'duration_days' => 31,
            'payment_proof_path' => 'proofs/jan31.jpg',
        ]);

        $this->assertSame('bulanan', $janMonthly->rental_type);
        $this->assertSame(31, $janMonthly->duration_days);
        $this->assertSame('2026-03-01', $janMonthly->end_date->toDateString());
        $this->assertEqualsWithDelta(1250000.0, (float) $janMonthly->base_rental_cost, 0.001);

        Carbon::setTestNow(Carbon::create(2026, 2, 1));
        $carThree = $this->createVehicle($renter, 'mobil', 'L 2026 CC');
        $c3 = $this->createGarage('C003', 'mobil', 50000.0);

        $febDailyMax = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $carThree->id,
            'garage_id' => $c3->id,
            'rental_type' => 'harian',
            'start_date' => '2026-02-01',
            'duration_days' => 27,
            'payment_proof_path' => 'proofs/feb27.jpg',
        ]);

        $this->assertSame(27, $febDailyMax->duration_days);
        $this->assertEqualsWithDelta(1350000.0, (float) $febDailyMax->base_rental_cost, 0.001);

        $carFour = $this->createVehicle($renter, 'mobil', 'L 2026 DD');
        $c4 = $this->createGarage('C004', 'mobil', 50000.0);

        $this->expectException(InvalidArgumentException::class);
        $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $carFour->id,
            'garage_id' => $c4->id,
            'rental_type' => 'harian',
            'start_date' => '2026-02-01',
            'duration_days' => 28,
            'payment_proof_path' => 'proofs/feb28.jpg',
        ]);
    }

    /**
     * Check Admin extensions have no max day limit for both daily and monthly contracts.
     *
     * @return void
     */
    public function test_extend_rental_without_day_limit(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 1));

        $renter = $this->createRenter();
        $carOne = $this->createVehicle($renter, 'mobil', 'L 1001 AA');
        $carTwo = $this->createVehicle($renter, 'mobil', 'L 1002 BB');
        $c1 = $this->createGarage('C001', 'mobil', 50000.0);
        $c2 = $this->createGarage('C002', 'mobil', 50000.0);

        $dailyRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $carOne->id,
            'garage_id' => $c1->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-01',
            'duration_days' => 10,
            'payment_proof_path' => 'proofs/daily.jpg',
        ]);

        $monthlyRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $carTwo->id,
            'garage_id' => $c2->id,
            'rental_type' => 'bulanan',
            'start_date' => '2026-11-01',
            'payment_proof_path' => 'proofs/monthly.jpg',
        ]);

        $dailyExtension = $this->rentalService->extendRental($dailyRental->id, '2026-12-26', 'proofs/ext1.jpg');
        $monthlyExtension = $this->rentalService->extendRental($monthlyRental->id, '2026-12-11', 'proofs/ext2.jpg');

        $this->assertSame(45, $dailyExtension->added_days);
        $this->assertEqualsWithDelta(2250000.0, (float) $dailyExtension->extension_cost, 0.001);
        $this->assertSame('2026-12-26', $dailyRental->fresh()->end_date->toDateString());

        // Monthly daily rate = 1,250,000 / 25 = 50,000/day -> 10 added days = 500,000.
        $this->assertSame(10, $monthlyExtension->added_days);
        $this->assertEqualsWithDelta(500000.0, (float) $monthlyExtension->extension_cost, 0.001);
    }

    /**
     * Check EV battery charging edge cases (full and empty) and invalid battery levels.
     *
     * @return void
     */
    public function test_ev_charging_formula_bounds(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->rentalService->calculateEvCharging(80.0, 80.0, 2500.0), 0.001);
        $this->assertEqualsWithDelta(200000.0, $this->rentalService->calculateEvCharging(80.0, 0.0, 2500.0), 0.001);

        $invalidCases = [
            fn (): float => $this->rentalService->calculateEvCharging(80.0, 85.0, 2500.0),
            fn (): float => $this->rentalService->calculateEvCharging(80.0, -1.0, 2500.0),
            fn (): float => $this->rentalService->calculateEvCharging(0.0, 0.0, 2500.0),
        ];

        foreach ($invalidCases as $case) {
            try {
                $case();
                $this->fail('Expected InvalidArgumentException for EV calculation.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    /**
     * Check invalid bookings and extensions throw errors cleanly without saving leftover inline vehicles.
     *
     * @return void
     */
    public function test_invalid_bookings_and_extensions_fail_cleanly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1));

        $renter = $this->createRenter('1');
        $otherRenter = $this->createRenter('2');
        $car = $this->createVehicle($renter, 'mobil', 'L 1111 AB');
        $ev = $this->createVehicle($renter, 'mobil_listrik', 'L 2222 EV', 80.0);
        $cGarage = $this->createGarage('C001', 'mobil', 50000.0);
        $cGarageTwo = $this->createGarage('C002', 'mobil', 50000.0);
        $eGarage = $this->createGarage('E001', 'mobil_listrik', 75000.0, 2500.0);

        $beforeVehicleCount = Vehicle::count();

        // Failed inline vehicles (wrong garage type or duplicate plate) must not leave leftover rows.
        try {
            $this->rentalService->createRental([
                'user_id' => $renter->id,
                'new_vehicle' => [
                    'type' => 'mobil',
                    'plate_number' => 'L 9999 ZZ',
                    'brand_model' => 'Honda Brio',
                ],
                'garage_id' => $eGarage->id,
                'rental_type' => 'harian',
                'start_date' => '2026-04-01',
                'duration_days' => 5,
                'payment_proof_path' => 'proofs/bad_inline.jpg',
            ]);
            $this->fail('Expected incompatible garage exception.');
        } catch (InvalidArgumentException) {
            $this->assertSame($beforeVehicleCount, Vehicle::count());
        }

        try {
            $this->rentalService->createRental([
                'user_id' => $otherRenter->id,
                'new_vehicle' => [
                    'type' => 'mobil',
                    'plate_number' => 'L 1111 AB',
                    'brand_model' => 'Honda Jazz',
                ],
                'garage_id' => $cGarageTwo->id,
                'rental_type' => 'harian',
                'start_date' => '2026-04-01',
                'duration_days' => 5,
                'payment_proof_path' => 'proofs/dup_plate.jpg',
            ]);
            $this->fail('Expected duplicate plate exception.');
        } catch (InvalidArgumentException) {
            $this->assertSame($beforeVehicleCount, Vehicle::count());
        }

        $invalidCreatePayloads = [
            [
                'user_id' => $renter->id,
                'vehicle_id' => $car->id,
                'garage_id' => $cGarage->id,
                'rental_type' => 'harian',
                'start_date' => '2026-03-31',
                'duration_days' => 2,
                'payment_proof_path' => 'proofs/past.jpg',
            ],
            [
                'user_id' => $renter->id,
                'vehicle_id' => $car->id,
                'garage_id' => $cGarage->id,
                'rental_type' => 'harian',
                'start_date' => '2026-04-01',
                'duration_days' => 31,
                'payment_proof_path' => 'proofs/too_long.jpg',
            ],
            [
                'user_id' => $renter->id,
                'vehicle_id' => $ev->id,
                'garage_id' => $eGarage->id,
                'rental_type' => 'harian',
                'start_date' => '2026-04-01',
                'duration_days' => 5,
                'payment_proof_path' => 'proofs/no_rem.jpg',
            ],
        ];

        foreach ($invalidCreatePayloads as $payload) {
            try {
                $this->rentalService->createRental($payload);
                $this->fail('Expected InvalidArgumentException on invalid booking payload.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        $activeRental = $this->rentalService->createRental([
            'user_id' => $renter->id,
            'vehicle_id' => $car->id,
            'garage_id' => $cGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-04-01',
            'duration_days' => 10,
            'payment_proof_path' => 'proofs/active.jpg',
        ]);

        // Future booking on C001 starting 2026-04-15 blocks overlapping extensions.
        $otherCar = $this->createVehicle($otherRenter, 'mobil', 'L 3333 CD');
        $this->rentalService->createRental([
            'user_id' => $otherRenter->id,
            'vehicle_id' => $otherCar->id,
            'garage_id' => $cGarage->id,
            'rental_type' => 'harian',
            'start_date' => '2026-04-15',
            'duration_days' => 3,
            'payment_proof_path' => 'proofs/future.jpg',
        ]);

        try {
            $this->rentalService->createRental([
                'user_id' => $otherRenter->id,
                'vehicle_id' => $otherCar->id,
                'garage_id' => $cGarage->id,
                'rental_type' => 'harian',
                'start_date' => '2026-04-05',
                'duration_days' => 3,
                'payment_proof_path' => 'proofs/g_overlap.jpg',
            ]);
            $this->fail('Expected garage overlap exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('garage', strtolower($exception->getMessage()));
        }

        try {
            $this->rentalService->createRental([
                'user_id' => $renter->id,
                'vehicle_id' => $car->id,
                'garage_id' => $cGarageTwo->id,
                'rental_type' => 'harian',
                'start_date' => '2026-04-05',
                'duration_days' => 3,
                'payment_proof_path' => 'proofs/v_overlap.jpg',
            ]);
            $this->fail('Expected vehicle overlap exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('vehicle', strtolower($exception->getMessage()));
        }

        try {
            $this->rentalService->extendRental($activeRental->id, '2026-04-11', 'proofs/ext_bad.jpg');
            $this->fail('Expected invalid extension end date exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->rentalService->extendRental($activeRental->id, '2026-04-16', 'proofs/ext_overlap.jpg');
    }
}
