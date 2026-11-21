<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Verifies GarageService sequential batch creation (S/C/E codes), EV-to-garage compatibility rules,
 * availability sorting via usort(), and invalid-input exception guards.
 */
class GarageServiceTest extends TestCase
{
    use RefreshDatabase;

    private GarageService $garageService;

    /**
     * Initialize a fresh GarageService instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->garageService = new GarageService();
    }

    /**
     * Verify createBatch generates sequential S/C/E codes and only stores kwh_price for EV garages.
     *
     * @return void
     */
    public function test_create_batch_generates_sequential_codes_and_normalizes_kwh_price(): void
    {
        $firstBatch = $this->garageService->createBatch('sepeda', 3, 15000.0, 2500.0);
        $secondBatch = $this->garageService->createBatch('sepeda', 2, 15000.0);
        $carBatch = $this->garageService->createBatch('mobil', 2, 50000.0, 1500.0);
        $evBatch = $this->garageService->createBatch('mobil_listrik', 2, 75000.0, 2500.0);

        $this->assertSame(['S001', 'S002', 'S003'], array_map(static fn (Garage $g): string => $g->code, $firstBatch));
        $this->assertSame(['S004', 'S005'], array_map(static fn (Garage $g): string => $g->code, $secondBatch));
        $this->assertSame(['C001', 'C002'], array_map(static fn (Garage $g): string => $g->code, $carBatch));
        $this->assertSame(['E001', 'E002'], array_map(static fn (Garage $g): string => $g->code, $evBatch));

        $this->assertNull($firstBatch[0]->kwh_price);
        $this->assertNull($carBatch[0]->kwh_price);
        $this->assertEqualsWithDelta(2500.0, (float) $evBatch[0]->kwh_price, 0.001);
        $this->assertSame('available', $evBatch[0]->status);
    }

    /**
     * Verify vehicle-to-garage compatibility allows EVs in both standard car (C) and EV (E) garages
     * while restricting standard cars to C garages and bikes/motorcycles to S garages.
     *
     * @return void
     */
    public function test_compatibility_mapping_enforces_vehicle_and_garage_rules(): void
    {
        $this->assertSame(['sepeda'], GarageService::garageTypesForVehicle('sepeda'));
        $this->assertSame(['sepeda'], GarageService::garageTypesForVehicle('sepeda_motor'));
        $this->assertSame(['mobil'], GarageService::garageTypesForVehicle('mobil'));
        $this->assertSame(['mobil', 'mobil_listrik'], GarageService::garageTypesForVehicle('mobil_listrik'));

        $this->assertTrue(GarageService::isCompatible('mobil_listrik', 'mobil'));
        $this->assertTrue(GarageService::isCompatible('mobil_listrik', 'mobil_listrik'));
        $this->assertTrue(GarageService::isCompatible('sepeda_motor', 'sepeda'));
        $this->assertFalse(GarageService::isCompatible('mobil', 'mobil_listrik'));
        $this->assertFalse(GarageService::isCompatible('sepeda', 'mobil'));
        $this->assertFalse(GarageService::isCompatible('sepeda_motor', 'mobil_listrik'));
    }

    /**
     * Verify getAvailableByVehicleType excludes overlapping bookings, includes non-overlapping ones,
     * includes both C and E garages for EVs, and returns results sorted by code ascending.
     *
     * @return void
     */
    public function test_get_available_by_vehicle_type_filters_overlaps_and_sorts_by_code(): void
    {
        $renter = User::create([
            'nik' => '3578000000000001',
            'name' => 'Test Renter',
            'phone' => '081200000001',
            'email' => 'renter@test.local',
            'password' => Hash::make('password'),
            'role' => 'penyewa',
            'status' => 'approved',
        ]);

        $carVehicle = Vehicle::create([
            'user_id' => $renter->id,
            'type' => 'mobil',
            'plate_number' => 'L 1111 AA',
            'brand_model' => 'Toyota Avanza',
        ]);

        [$e1, $e2] = $this->garageService->createBatch('mobil_listrik', 2, 75000.0, 2500.0);
        [$c1, $c2] = $this->garageService->createBatch('mobil', 2, 50000.0);

        // Overlaps 2026-11-10..2026-11-15 so C001 must be excluded.
        Rental::create([
            'user_id' => $renter->id,
            'vehicle_id' => $carVehicle->id,
            'garage_id' => $c1->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'duration_days' => 5,
            'base_rental_cost' => 250000.0,
            'charging_cost' => 0.0,
            'total_cost' => 250000.0,
            'payment_proof_path' => 'proofs/overlap.jpg',
        ]);

        // Non-overlapping past booking so C002 remains available for 2026-11-10..2026-11-15.
        Rental::create([
            'user_id' => $renter->id,
            'vehicle_id' => $carVehicle->id,
            'garage_id' => $c2->id,
            'rental_type' => 'harian',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'duration_days' => 4,
            'base_rental_cost' => 200000.0,
            'charging_cost' => 0.0,
            'total_cost' => 200000.0,
            'payment_proof_path' => 'proofs/free.jpg',
        ]);

        $evAvailable = $this->garageService->getAvailableByVehicleType('mobil_listrik', '2026-11-12', '2026-11-14');
        $carAvailable = $this->garageService->getAvailableByVehicleType('mobil', '2026-11-12', '2026-11-14');

        $this->assertSame([$c2->code, $e1->code, $e2->code], array_map(static fn (Garage $g): string => $g->code, $evAvailable));
        $this->assertSame([$c2->code], array_map(static fn (Garage $g): string => $g->code, $carAvailable));
    }

    /**
     * Verify sequence boundary 999 succeeds and getAvailableByVehicleType returns empty when all compatible units are booked.
     *
     * @return void
     */
    public function test_boundary_sequence_999_succeeds_and_fully_booked_returns_empty_array(): void
    {
        $lastValid = Garage::create([
            'code' => 'S998',
            'type' => 'sepeda',
            'daily_rate' => 10000.0,
            'status' => 'available',
        ]);

        $created = $this->garageService->createBatch('sepeda', 1, 10000.0);
        $this->assertSame('S999', $created[0]->code);

        $renter = User::create([
            'nik' => '3578000000000002',
            'name' => 'Bike Renter',
            'phone' => '081200000002',
            'email' => 'bike@test.local',
            'password' => Hash::make('password'),
            'role' => 'penyewa',
            'status' => 'approved',
        ]);
        $bike = Vehicle::create([
            'user_id' => $renter->id,
            'type' => 'sepeda',
            'brand_model' => 'Polygon',
        ]);

        foreach ([$lastValid, $created[0]] as $garage) {
            Rental::create([
                'user_id' => $renter->id,
                'vehicle_id' => $bike->id,
                'garage_id' => $garage->id,
                'rental_type' => 'harian',
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-22',
                'duration_days' => 2,
                'base_rental_cost' => 20000.0,
                'charging_cost' => 0.0,
                'total_cost' => 20000.0,
                'payment_proof_path' => 'proofs/b.jpg',
            ]);
        }

        $this->assertSame([], $this->garageService->getAvailableByVehicleType('sepeda', '2026-11-20', '2026-11-21'));
    }

    /**
     * Verify createBatch rejects invalid counts, non-positive daily rates, missing/zero EV kWh prices,
     * and batch additions that would exceed the 999 sequence code limit.
     *
     * @return void
     */
    public function test_create_batch_rejects_invalid_inputs_and_sequence_overflow(): void
    {
        $invalidCalls = [
            fn (): array => $this->garageService->createBatch('sepeda', 0, 10000.0),
            fn (): array => $this->garageService->createBatch('mobil', 1, 0.0),
            fn (): array => $this->garageService->createBatch('mobil_listrik', 1, 75000.0, null),
            fn (): array => $this->garageService->createBatch('mobil_listrik', 1, 75000.0, 0.0),
        ];

        foreach ($invalidCalls as $call) {
            try {
                $call();
                $this->fail('Expected InvalidArgumentException was not thrown.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }

        Garage::create([
            'code' => 'C998',
            'type' => 'mobil',
            'daily_rate' => 50000.0,
            'status' => 'available',
        ]);

        try {
            $this->garageService->createBatch('mobil', 2, 50000.0);
            $this->fail('Expected sequence overflow InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('999', $exception->getMessage());
        }

        $this->assertSame(1, Garage::where('type', 'mobil')->count());
    }
}
