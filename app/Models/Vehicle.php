<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a renter's registered vehicle (bicycle, motorcycle, car, or EV)
 * used to match compatible garage units and calculate EV charging costs.
 */
class Vehicle extends Model
{
    /**
     * Mass-assignable attributes for vehicle registration.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'plate_number',
        'brand_model',
        'battery_capacity_kwh',
    ];

    /**
     * Define attribute casts for numeric battery capacity.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'battery_capacity_kwh' => 'float',
        ];
    }

    /**
     * Retrieve the renter who owns this vehicle.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retrieve all rental records associated with this vehicle.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Determine whether this vehicle is an electric car eligible for EV charging.
     *
     * @return bool True when vehicle type is 'mobil_listrik'.
     */
    public function isElectric(): bool
    {
        return $this->type === 'mobil_listrik';
    }

    /**
     * Return a human-readable Indonesian label for the vehicle type.
     *
     * @return string Formatted vehicle type label.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'sepeda' => 'Sepeda',
            'sepeda_motor' => 'Sepeda Motor',
            'mobil' => 'Mobil',
            'mobil_listrik' => 'Mobil Listrik (EV)',
            default => $this->type,
        };
    }
}
