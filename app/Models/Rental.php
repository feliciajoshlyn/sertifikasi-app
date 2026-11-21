<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a garage rental booking linking a renter, vehicle, and garage unit,
 * preserving historical pricing snapshots and payment proof references.
 */
class Rental extends Model
{
    /**
     * Mass-assignable attributes for creating and updating rental bookings.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'garage_id',
        'rental_type',
        'start_date',
        'end_date',
        'duration_days',
        'remaining_battery_kwh',
        'base_rental_cost',
        'charging_cost',
        'total_cost',
        'payment_proof_path',
    ];

    /**
     * Define attribute casts for dates, integers, and monetary snapshot fields.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
            'remaining_battery_kwh' => 'float',
            'base_rental_cost' => 'float',
            'charging_cost' => 'float',
            'total_cost' => 'float',
        ];
    }

    /**
     * Retrieve the renter who booked this rental.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retrieve the vehicle parked in this rental.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Retrieve the garage unit assigned to this rental.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Retrieve all Admin-approved duration extensions for this rental (Composition).
     *
     * @return HasMany<RentalExtension, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }
}
