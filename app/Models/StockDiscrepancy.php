<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A product at a branch whose shelf count, lots and stock ledger disagree (stock:reconcile). */
class StockDiscrepancy extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'lots' => 'integer',
            'ledger' => 'integer',
            'found_at' => 'datetime',
            'checked_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
