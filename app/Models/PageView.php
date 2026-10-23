<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/** One view of a public page. Kept 13 months, then pruned by `model:prune`. */
class PageView extends Model
{
    use BelongsToOrganization, Prunable;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /** @return Builder<PageView> */
    public function prunable(): Builder
    {
        return static::withoutGlobalScopes()->where('created_at', '<', now()->subMonths(13));
    }
}
