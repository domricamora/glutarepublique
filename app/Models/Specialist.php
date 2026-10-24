<?php

namespace App\Models;

use App\Casts\AssetUrl;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialist extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['photo' => AssetUrl::class, 'focus' => 'array', 'is_active' => 'boolean'];
    }

    /**
     * Services this person may perform; see Treatment::specialists().
     *
     * @return BelongsToMany<Treatment, $this>
     */
    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class);
    }

    /** @return BelongsToMany<Branch, $this> */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }
}
