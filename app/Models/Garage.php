<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a physical garage unit with a unique prefix code (S/C/E),
 * vehicle compatibility type, daily rate, optional EV kWh rate, and availability status.
 */
class Garage extends Model
{
    /**
     * Mass-assignable attributes for garage unit creation and status updates.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'type',
        'daily_rate',
        'kwh_price',
        'status',
    ];

    /**
     * Define attribute casts for monetary rate fields.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate' => 'float',
            'kwh_price' => 'float',
        ];
    }

    /**
     * Retrieve all rental bookings assigned to this garage unit.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Return a human-readable Indonesian label showing which vehicles fit this garage type.
     *
     * @return string Formatted garage type label.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'sepeda' => 'Sepeda / Sepeda Motor',
            'mobil' => 'Mobil',
            'mobil_listrik' => 'Mobil Listrik (EV)',
            default => $this->type,
        };
    }
}
