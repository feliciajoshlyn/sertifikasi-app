<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a dependent duration extension attached to an active rental,
 * recording the updated end date, added days, extension cost, and payment proof.
 */
class RentalExtension extends Model
{
    /**
     * Mass-assignable attributes for rental extension records.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rental_id',
        'previous_end_date',
        'new_end_date',
        'added_days',
        'extension_cost',
        'payment_proof_path',
    ];

    /**
     * Define attribute casts for extension dates, days, and cost.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_end_date' => 'date',
            'new_end_date' => 'date',
            'added_days' => 'integer',
            'extension_cost' => 'float',
        ];
    }

    /**
     * Retrieve the parent rental that this extension belongs to.
     *
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
