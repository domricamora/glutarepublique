<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A public holiday. Regular holidays pay double when worked; special
 * non-working days pay 130%. Neither counts as an absence.
 *
 * @property CarbonImmutable $date
 * @property string $name
 * @property string $type regular or special
 */
class Holiday extends Model
{
    use BelongsToOrganization;

    public const TYPES = ['regular' => 'Regular holiday', 'special' => 'Special non-working day'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }
}
