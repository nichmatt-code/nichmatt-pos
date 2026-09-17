<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\PromoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Promo extends Model
{
    /** @use HasFactory<PromoFactory> */
    use BelongsToStore, HasFactory;

    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_GIFT = 'gift';

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'store_id',
        'name',
        'type',
        'product_id',
        'discount_type',
        'discount_value',
        'min_qty',
        'gift_product_id',
        'gift_qty',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'min_qty' => 'integer',
            'gift_qty' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function giftProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    public function isDiscount(): bool
    {
        return $this->type === self::TYPE_DISCOUNT;
    }

    public function isGift(): bool
    {
        return $this->type === self::TYPE_GIFT;
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->starts_at && $today->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $today->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function discountedPriceFor(int $basePrice): int
    {
        if (! $this->isDiscount()) {
            return $basePrice;
        }

        if ($this->discount_type === self::DISCOUNT_PERCENT) {
            return max(0, $basePrice - (int) round($basePrice * $this->discount_value / 100));
        }

        return max(0, $basePrice - $this->discount_value);
    }

    public function badgeLabel(): string
    {
        if ($this->isDiscount()) {
            return $this->discount_type === self::DISCOUNT_PERCENT
                ? "Promo -{$this->discount_value}%"
                : 'Promo -Rp'.number_format($this->discount_value, 0, ',', '.');
        }

        return 'Beli '.$this->min_qty.' Gratis '.($this->giftProduct?->name ?? 'Hadiah');
    }

    /**
     * Every currently-active promo for the store, with its product(s)
     * eager-loaded - meant to be fetched once per request and matched
     * against products in memory, rather than queried per product.
     *
     * A store id must be passed explicitly for guest-facing contexts (e.g.
     * self-order), since the BelongsToStore global scope only auto-applies
     * for an authenticated user (a cashier).
     *
     * @return Collection<int, self>
     */
    public static function activeForStore(?int $storeId = null): Collection
    {
        return static::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->where('is_active', true)
            ->with(['product', 'giftProduct'])
            ->get()
            ->filter(fn (self $promo) => $promo->isCurrentlyActive())
            ->values();
    }
}
