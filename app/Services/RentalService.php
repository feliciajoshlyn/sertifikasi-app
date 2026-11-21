<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Pricing\DailyPricingStrategy;
use App\Domain\Pricing\MonthlyPricingStrategy;
use App\Domain\Pricing\PricingStrategyInterface;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates rental booking creation (with optional inline new vehicle registration),
 * calendar-month pricing strategy resolution, EV battery charging, and Admin extensions.
 */
class RentalService
{
    /**
     * Create a new garage rental booking with validation, optional inline vehicle creation,
     * pricing strategy calculation, optional EV charging fee, and atomic garage status update.
     *
     * @param array{
     *     user_id: int,
     *     vehicle_id?: int|null,
     *     new_vehicle?: array{
     *         type: string,
     *         plate_number?: ?string,
     *         brand_model: string,
     *         battery_capacity_kwh?: float|int|null
     *     }|null,
     *     garage_id: int,
     *     rental_type?: string,
     *     start_date: string,
     *     duration_days?: int|null,
     *     remaining_battery_kwh?: float|int|null,
     *     payment_proof_path: string
     * } $data Validated booking payload.
     *
     * @return Rental Newly persisted Rental model.
     *
     * @throws InvalidArgumentException When dates, rental type, duration, vehicle, compatibility, overlap, or EV battery inputs are invalid.
     */
    public function createRental(array $data): Rental
    {
        $startDate = Carbon::parse((string) ($data['start_date'] ?? ''))->startOfDay();
        if ($startDate->lt(Carbon::today())) {
            throw new InvalidArgumentException('Start date cannot be in the past.');
        }

        [$rentalType, $durationDays] = $this->resolveRentalTypeAndDuration($data, $startDate->daysInMonth);

        $userId = (int) ($data['user_id'] ?? 0);
        $vehicle = $this->resolveOrBuildVehicle($data, $userId);
        $garage = Garage::find((int) ($data['garage_id'] ?? 0));
        if ($garage === null) {
            throw new InvalidArgumentException('Garage not found.');
        }

        if (!GarageService::isCompatible($vehicle->type, $garage->type)) {
            throw new InvalidArgumentException('Selected garage is not compatible with the vehicle type.');
        }

        $endDate = $startDate->copy()->addDays($durationDays);
        $this->assertNoBookingConflicts(
            $garage->id,
            $vehicle->exists ? $vehicle->id : null,
            $startDate,
            $endDate
        );

        $strategy = $this->resolveStrategy($rentalType);
        $baseRentalCost = $strategy->calculateBaseFee($durationDays, (float) $garage->daily_rate);
        [$chargingCost, $remainingBattery] = $this->resolveEvCharging($vehicle, $garage, $data);
        $totalCost = $baseRentalCost + $chargingCost;

        // Persist any new vehicle, save the rental, and mark the garage 'used' atomically.
        return DB::transaction(function () use (
            $data,
            $vehicle,
            $garage,
            $rentalType,
            $startDate,
            $endDate,
            $durationDays,
            $remainingBattery,
            $baseRentalCost,
            $chargingCost,
            $totalCost
        ): Rental {
            if (!$vehicle->exists) {
                $vehicle->save();
            }

            $rental = Rental::create([
                'user_id' => $vehicle->user_id,
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => $rentalType,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_days' => $durationDays,
                'remaining_battery_kwh' => $remainingBattery,
                'base_rental_cost' => $baseRentalCost,
                'charging_cost' => $chargingCost,
                'total_cost' => $totalCost,
                'payment_proof_path' => (string) $data['payment_proof_path'],
            ]);

            $garage->update(['status' => 'used']);

            return $rental;
        });
    }

    /**
     * Extend an active rental on behalf of an Admin with no maximum day limit,
     * computing the extension fee from the original rental's historical daily rate snapshot.
     *
     * @param int    $rentalId   Target rental primary key.
     * @param string $newEndDate Requested new end date (Y-m-d), strictly after current end_date.
     * @param string $proofPath  Stored payment proof file path.
     *
     * @return RentalExtension Newly persisted RentalExtension model.
     *
     * @throws InvalidArgumentException When the rental is missing, date is invalid, or an overlap exists.
     */
    public function extendRental(int $rentalId, string $newEndDate, string $proofPath): RentalExtension
    {
        $rental = Rental::find($rentalId);
        if ($rental === null) {
            throw new InvalidArgumentException('Rental record not found.');
        }

        $previousEndDate = Carbon::parse($rental->end_date)->startOfDay();
        $targetEndDate = Carbon::parse($newEndDate)->startOfDay();

        if ($targetEndDate->lte($previousEndDate)) {
            throw new InvalidArgumentException('New end date must be after the current rental end date.');
        }

        $this->assertNoBookingConflicts(
            $rental->garage_id,
            $rental->vehicle_id,
            $previousEndDate,
            $targetEndDate,
            $rental->id
        );

        $addedDays = (int) $previousEndDate->diffInDays($targetEndDate);

        // Derive the daily rate from the original rental snapshot so subsequent garage rate edits do not alter active contracts.
        $historicalDailyRate = $rental->rental_type === 'bulanan'
            ? ((float) $rental->base_rental_cost / MonthlyPricingStrategy::monthlyMultiplier())
            : ((float) $rental->base_rental_cost / (int) $rental->duration_days);

        $extensionCost = $historicalDailyRate * $addedDays;

        return DB::transaction(function () use (
            $rental,
            $previousEndDate,
            $targetEndDate,
            $addedDays,
            $extensionCost,
            $proofPath
        ): RentalExtension {
            $extension = RentalExtension::create([
                'rental_id' => $rental->id,
                'previous_end_date' => $previousEndDate->toDateString(),
                'new_end_date' => $targetEndDate->toDateString(),
                'added_days' => $addedDays,
                'extension_cost' => $extensionCost,
                'payment_proof_path' => $proofPath,
            ]);

            $rental->update(['end_date' => $targetEndDate->toDateString()]);

            return $extension;
        });
    }

    /**
     * Calculate the EV battery charging fee from full capacity, remaining charge, and kWh rate.
     *
     * @param float $fullKwh      Vehicle full battery capacity in kWh.
     * @param float $remainingKwh Current remaining battery level in kWh.
     * @param float $kwhPrice     Garage electricity price per kWh.
     *
     * @return float Calculated charging fee.
     *
     * @throws InvalidArgumentException When battery or price values are out of valid bounds.
     */
    public function calculateEvCharging(float $fullKwh, float $remainingKwh, float $kwhPrice): float
    {
        if ($fullKwh <= 0 || $kwhPrice < 0) {
            throw new InvalidArgumentException('Battery capacity must be positive and kWh price cannot be negative.');
        }

        if ($remainingKwh < 0 || $remainingKwh > $fullKwh) {
            throw new InvalidArgumentException('Remaining battery kWh must be between 0 and full battery capacity.');
        }

        return ($fullKwh - $remainingKwh) * $kwhPrice;
    }

    /**
     * Load an existing owned vehicle or build an unsaved Vehicle model for atomic creation
     * only after all rental compatibility, pricing, and overlap checks pass.
     *
     * @param array<string, mixed> $data   Booking payload containing either vehicle_id or new_vehicle.
     * @param int                  $userId Authenticated renter user ID.
     *
     * @return Vehicle Persisted or unsaved validated Vehicle instance.
     *
     * @throws InvalidArgumentException When vehicle lookup, ownership, or new vehicle fields are invalid.
     */
    private function resolveOrBuildVehicle(array $data, int $userId): Vehicle
    {
        if (isset($data['new_vehicle']) && is_array($data['new_vehicle'])) {
            $new = $data['new_vehicle'];
            $type = (string) ($new['type'] ?? '');
            if (GarageService::garageTypesForVehicle($type) === []) {
                throw new InvalidArgumentException("Unsupported vehicle type: {$type}");
            }

            $brandModel = trim((string) ($new['brand_model'] ?? ''));
            if ($brandModel === '') {
                throw new InvalidArgumentException('Brand and model are required for a new vehicle.');
            }

            $rawPlate = $new['plate_number'] ?? null;
            $plate = is_string($rawPlate) && trim($rawPlate) !== ''
                ? strtoupper(trim($rawPlate))
                : null;

            if ($type === 'sepeda') {
                $plate = null;
            } elseif ($plate === null) {
                throw new InvalidArgumentException('Plate number is required for motorcycles, cars, and electric vehicles.');
            }

            if ($plate !== null && Vehicle::query()->where('plate_number', $plate)->exists()) {
                throw new InvalidArgumentException('Nomor plat ini sudah terdaftar pada kendaraan lain.');
            }

            $battery = $type === 'mobil_listrik'
                ? (float) ($new['battery_capacity_kwh'] ?? 0)
                : null;

            if ($type === 'mobil_listrik' && ($battery === null || $battery <= 0)) {
                throw new InvalidArgumentException('Battery capacity (kWh) must be positive for electric vehicles.');
            }

            return new Vehicle([
                'user_id' => $userId,
                'type' => $type,
                'plate_number' => $plate,
                'brand_model' => $brandModel,
                'battery_capacity_kwh' => $battery,
            ]);
        }

        $vehicle = Vehicle::find((int) ($data['vehicle_id'] ?? 0));
        if ($vehicle === null) {
            throw new InvalidArgumentException('Vehicle not found.');
        }

        if ($vehicle->user_id !== $userId) {
            throw new InvalidArgumentException('Selected vehicle does not belong to the renter.');
        }

        if ($vehicle->type !== 'sepeda' && ($vehicle->plate_number === null || trim($vehicle->plate_number) === '')) {
            throw new InvalidArgumentException('Plate number is required for motorcycles, cars, and electric vehicles.');
        }

        return $vehicle;
    }

    /**
     * Resolve the rental type ('harian' or 'bulanan') and validate duration bounds for the start month.
     *
     * @param array<string, mixed> $data             Booking payload.
     * @param int                  $daysInStartMonth Total calendar days in the start date's month.
     *
     * @return array{0: string, 1: int} Pair of [rentalType, durationDays].
     *
     * @throws InvalidArgumentException When rental type is invalid or daily days exceed daysInStartMonth - 1.
     */
    private function resolveRentalTypeAndDuration(array $data, int $daysInStartMonth): array
    {
        if (isset($data['rental_type']) && $data['rental_type'] !== '') {
            $rentalType = (string) $data['rental_type'];
            if (!in_array($rentalType, ['harian', 'bulanan'], true)) {
                throw new InvalidArgumentException('Rental type must be harian or bulanan.');
            }
        } else {
            $rentalType = ((int) ($data['duration_days'] ?? 0) === $daysInStartMonth) ? 'bulanan' : 'harian';
        }

        if ($rentalType === 'bulanan') {
            return [$rentalType, $daysInStartMonth];
        }

        $durationDays = (int) ($data['duration_days'] ?? 0);
        $maxDailyDays = $daysInStartMonth - 1;
        if ($durationDays < 1 || $durationDays > $maxDailyDays) {
            throw new InvalidArgumentException(
                "Daily rental duration must be between 1 and {$maxDailyDays} days for this month (choose bulanan for a full {$daysInStartMonth}-day month)."
            );
        }

        return [$rentalType, $durationDays];
    }

    /**
     * Verify neither the target garage nor vehicle already has an overlapping booking in [startDate, endDate].
     *
     * @param int      $garageId        Target garage ID.
     * @param int|null $vehicleId       Target vehicle ID (null for an unsaved inline new vehicle).
     * @param Carbon   $startDate       Window start date.
     * @param Carbon   $endDate         Window end date.
     * @param int|null $excludeRentalId Rental ID to exclude when checking extension overlaps.
     *
     * @return void
     *
     * @throws InvalidArgumentException When either the garage or vehicle has an overlapping booking.
     */
    private function assertNoBookingConflicts(
        int $garageId,
        ?int $vehicleId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeRentalId = null
    ): void {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $garageHasOverlap = Rental::query()
            ->where('garage_id', $garageId)
            ->when($excludeRentalId !== null, static fn ($query) => $query->where('id', '!=', $excludeRentalId))
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();

        if ($garageHasOverlap) {
            $message = $excludeRentalId !== null
                ? 'Cannot extend rental due to an overlapping booking on this garage.'
                : 'Selected garage already has an overlapping booking in this date range.';

            throw new InvalidArgumentException($message);
        }

        // An unsaved inline new vehicle cannot have prior overlapping rentals yet.
        if ($vehicleId === null) {
            return;
        }

        $vehicleHasOverlap = Rental::query()
            ->where('vehicle_id', $vehicleId)
            ->when($excludeRentalId !== null, static fn ($query) => $query->where('id', '!=', $excludeRentalId))
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();

        if ($vehicleHasOverlap) {
            $message = $excludeRentalId !== null
                ? 'Cannot extend rental because this vehicle has another booking during this date range.'
                : 'Selected vehicle is already parked in another active rental during this date range.';

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Calculate EV charging cost and normalized remaining kWh when parking an EV inside an EV garage.
     *
     * @param Vehicle              $vehicle Selected vehicle model.
     * @param Garage               $garage  Selected garage model.
     * @param array<string, mixed> $data    Booking payload containing remaining_battery_kwh.
     *
     * @return array{0: float, 1: float|null} Pair of [chargingCost, remainingBatteryKwh].
     *
     * @throws InvalidArgumentException When EV remaining battery kWh is missing or out of bounds.
     */
    private function resolveEvCharging(Vehicle $vehicle, Garage $garage, array $data): array
    {
        if (!$vehicle->isElectric() || $garage->type !== 'mobil_listrik') {
            return [0.0, null];
        }

        if (!isset($data['remaining_battery_kwh']) || $data['remaining_battery_kwh'] === '') {
            throw new InvalidArgumentException('Remaining battery kWh is required for EV garage rentals.');
        }

        $remainingBattery = (float) $data['remaining_battery_kwh'];
        $chargingCost = $this->calculateEvCharging(
            (float) $vehicle->battery_capacity_kwh,
            $remainingBattery,
            (float) $garage->kwh_price
        );

        return [$chargingCost, $remainingBattery];
    }

    /**
     * Select the appropriate pricing strategy based on the rental type.
     *
     * @param string $rentalType Resolved rental type ('harian' or 'bulanan').
     *
     * @return PricingStrategyInterface Resolved pricing strategy instance.
     */
    private function resolveStrategy(string $rentalType): PricingStrategyInterface
    {
        if ($rentalType === 'bulanan') {
            return new MonthlyPricingStrategy();
        }

        return new DailyPricingStrategy();
    }
}
