<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'duration_days',
        'price',
        'promo_price',
        'promo_label',
        'promo_ends_at',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'integer',
            'promo_price' => 'integer',
            'promo_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StoreSubscriptionPayment::class);
    }

    public function hasActivePromo(): bool
    {
        if ($this->promo_price === null) {
            return false;
        }

        return $this->promo_ends_at === null || $this->promo_ends_at->isFuture();
    }

    /**
     * The price actually charged right now: the promo price while one is
     * running, otherwise the regular price.
     */
    public function effectivePrice(): int
    {
        return $this->hasActivePromo() ? $this->promo_price : $this->price;
    }
}
