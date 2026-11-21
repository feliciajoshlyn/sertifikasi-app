<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Garage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Handles garage batch creation with sequential prefix codes (S/C/E)
 * and availability filtering by compatible vehicle type and date range.
 */
class GarageService
{
    private const MAX_SEQUENCE_NUMBER = 999;

    /**
     * Maps each vehicle type to its compatible garage types.
     *
     * @var array<string, list<string>>
     */
    private const VEHICLE_TO_GARAGE_MAP = [
        'sepeda' => ['sepeda'],
        'sepeda_motor' => ['sepeda'],
        'mobil' => ['mobil'],
        'mobil_listrik' => ['mobil', 'mobil_listrik'],
    ];

    /**
     * Maps each garage type to its 1-letter code prefix.
     *
     * @var array<string, string>
     */
    private const TYPE_PREFIX_MAP = [
        'sepeda' => 'S',
        'mobil' => 'C',
        'mobil_listrik' => 'E',
    ];

    /**
     * Resolve all compatible garage types for a given vehicle type as a single source of truth.
     *
     * @param string $vehicleType Vehicle type ('sepeda', 'sepeda_motor', 'mobil', or 'mobil_listrik').
     *
     * @return list<string> Compatible garage types, or empty list when unsupported.
     */
    public static function garageTypesForVehicle(string $vehicleType): array
    {
        return self::VEHICLE_TO_GARAGE_MAP[$vehicleType] ?? [];
    }

    /**
     * Check whether a vehicle type can legally park in the target garage type.
     *
     * @param string $vehicleType Vehicle type key.
     * @param string $garageType  Target garage type key.
     *
     * @return bool True when the garage type accepts the vehicle type.
     */
    public static function isCompatible(string $vehicleType, string $garageType): bool
    {
        return in_array($garageType, self::garageTypesForVehicle($vehicleType), true);
    }

    /**
     * Batch-create garage units with sequential codes inside a database transaction.
     *
     * @param string     $type      Garage type ('sepeda', 'mobil', or 'mobil_listrik').
     * @param int        $count     Number of garage units to create.
     * @param float      $dailyRate Daily rental rate for each created garage.
     * @param float|null $kwhPrice  EV charging price per kWh (required for 'mobil_listrik').
     *
     * @return array<int, Garage> List of newly created Garage models.
     *
     * @throws InvalidArgumentException When type, count, daily rate, kWh price, or sequence limit is invalid.
     */
    public function createBatch(string $type, int $count, float $dailyRate, ?float $kwhPrice = null): array
    {
        if (!array_key_exists($type, self::TYPE_PREFIX_MAP)) {
            throw new InvalidArgumentException("Unsupported garage type: {$type}");
        }

        if ($count < 1 || $dailyRate <= 0) {
            throw new InvalidArgumentException('Count must be at least 1 and daily rate must be positive.');
        }

        if ($type === 'mobil_listrik' && ($kwhPrice === null || $kwhPrice <= 0)) {
            throw new InvalidArgumentException('EV garages require a positive kWh price.');
        }

        $normalizedKwhPrice = $type === 'mobil_listrik' ? $kwhPrice : null;

        return DB::transaction(function () use ($type, $count, $dailyRate, $normalizedKwhPrice): array {
            $lastCode = Garage::query()
                ->where('type', $type)
                ->orderByDesc('code')
                ->value('code');

            $lastSequence = $lastCode !== null ? (int) substr((string) $lastCode, 1) : 0;

            if ($lastSequence + $count > self::MAX_SEQUENCE_NUMBER) {
                throw new InvalidArgumentException('Garage code sequence cannot exceed ' . self::MAX_SEQUENCE_NUMBER . ' for a single type.');
            }

            $created = [];

            for ($i = 1; $i <= $count; $i++) {
                $created[] = Garage::create([
                    'code' => $this->generateNextCode($type, $lastSequence + $i),
                    'type' => $type,
                    'daily_rate' => $dailyRate,
                    'kwh_price' => $normalizedKwhPrice,
                    'status' => 'available',
                ]);
            }

            return $created;
        });
    }

    /**
     * Retrieve available garages compatible with the given vehicle type and date window,
     * sorted ascending by garage code using usort().
     *
     * @param string $vehicleType Vehicle type ('sepeda', 'sepeda_motor', 'mobil', or 'mobil_listrik').
     * @param string $startDate   Requested booking start date (Y-m-d).
     * @param string $endDate     Requested booking end date (Y-m-d).
     *
     * @return array<int, Garage> Sorted list of compatible available Garage models.
     *
     * @throws InvalidArgumentException When the vehicle type is unsupported.
     */
    public function getAvailableByVehicleType(string $vehicleType, string $startDate, string $endDate): array
    {
        $garageTypes = self::garageTypesForVehicle($vehicleType);
        if ($garageTypes === []) {
            throw new InvalidArgumentException("Unsupported vehicle type: {$vehicleType}");
        }

        $garages = Garage::query()
            ->whereIn('type', $garageTypes)
            ->whereDoesntHave('rentals', function ($query) use ($startDate, $endDate): void {
                $query->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->get()
            ->all();

        // Sort deterministically by code in PHP so callers receive S001..S999 order independent of DB collation.
        usort($garages, static fn (Garage $a, Garage $b): int => strcmp($a->code, $b->code));

        return $garages;
    }

    /**
     * Format the 4-character sequential garage code for a given type and sequence number.
     *
     * @param string $type     Garage type key.
     * @param int    $sequence Positive sequence number (1..999).
     *
     * @return string Formatted code such as 'S001', 'C001', or 'E001'.
     */
    private function generateNextCode(string $type, int $sequence): string
    {
        $prefix = self::TYPE_PREFIX_MAP[$type];

        return sprintf('%s%03d', $prefix, $sequence);
    }
}
