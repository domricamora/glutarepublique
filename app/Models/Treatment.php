<?php

namespace App\Models;

use App\Casts\AssetUrl;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['image' => AssetUrl::class,
            'price' => 'float',
            'promo_price' => 'float',
            'price_list' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<TreatmentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TreatmentCategory::class, 'treatment_category_id');
    }

    /**
     * The recipe: the products one session uses, in whole units. They come off
     * the shelf of the branch that rings the service up (App\Actions\Pos\RingUpSale).
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'treatment_products')->withPivot('quantity')->withTimestamps();
    }

    /**
     * The staff who may perform the service. Nobody named means anyone at the
     * branch can (App\Actions\Booking\AvailableSlots::specialists).
     *
     * @return BelongsToMany<Specialist, $this>
     */
    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(Specialist::class);
    }

    /** What one session's products cost, at today's product costs. */
    public function recipeCost(): float
    {
        return round((float) $this->products->sum(fn (Product $p) => (float) $p->cost * (int) $p->pivot->quantity), 2);
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * What the register charges today: the promo price when there is one.
     */
    public function currentPrice(): float
    {
        return (float) ($this->promo_price ?: $this->price);
    }

    public function isOnPromo(): bool
    {
        return (float) $this->promo_price > 0 && (float) $this->promo_price < (float) $this->price;
    }

    /**
     * The stored path, not the public url.
     *
     * The image is cast to a url for the site, but the office needs the path on
     * disk to know which file a new upload replaces and which one a deleted
     * one removes.
     */
    public function imagePath(): ?string
    {
        return $this->getRawOriginal('image');
    }
}
