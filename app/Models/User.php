<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Represents an authenticated Admin or Penyewa account in the Vehicle Garage Rental System,
 * storing KTP identity and verification status for role-based access control.
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass-assignable attributes for user registration and verification.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nik',
        'name',
        'phone',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * Define the attribute casts for timestamps and hashed passwords.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Retrieve all vehicles registered by this user.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Retrieve all garage rentals booked by this user.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Determine whether the user holds the administrator role.
     *
     * @return bool True when role is 'admin'.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine whether the user's registration has been approved by an administrator.
     *
     * @return bool True when status is 'approved'.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
