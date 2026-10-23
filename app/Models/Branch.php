<?php

namespace App\Models;

use App\Casts\AssetUrl;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_active
 * @property list<array{0: string, 1: string}|null> $hours
 */
#[Fillable(['organization_id', 'name', 'slug', 'address', 'city', 'phone', 'email', 'image', 'hours', 'map_url', 'is_active'])]
class Branch extends Model
{
    use BelongsToOrganization;

    /**
     * Opening hours by day of week, Sunday first (Carbon's dayOfWeek and JS
     * getDay both count that way). null is closed. The website, the open or
     * closed badge and the booking slots all read this one schedule.
     */
    public const DEFAULT_HOURS = [null, ['10:00', '20:00'], ['10:00', '20:00'], ['10:00', '20:00'], ['10:00', '20:00'], ['10:00', '20:00'], ['10:00', '18:00']];

    /** The mall branch keeps mall hours, six days a week. */
    public const CEBU_HOURS = [null, ['10:00', '19:00'], ['10:00', '19:00'], ['10:00', '19:00'], ['10:00', '19:00'], ['10:00', '19:00'], ['10:00', '19:00']];

    /** @return BelongsToMany<Specialist, $this> */
    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(Specialist::class);
    }

    /** @return array{0: string, 1: string}|null */
    public function hoursOn(CarbonInterface $date): ?array
    {
        return $this->hours[$date->dayOfWeek];
    }

    /**
     * Anything that is not a seven-day schedule (old free-text rows, null) reads as the default.
     *
     * @return Attribute<list<array{0: string, 1: string}|null>, list<array{0: string, 1: string}|null>|null>
     */
    protected function hours(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                $hours = json_decode($value ?? 'null', true);

                return is_array($hours) && array_is_list($hours) && count($hours) === 7 ? $hours : self::DEFAULT_HOURS;
            },
            set: fn (?array $value) => json_encode($value ?? self::DEFAULT_HOURS),
        );
    }

    protected function casts(): array
    {
        return ['image' => AssetUrl::class, 'is_active' => 'boolean'];
    }
}
