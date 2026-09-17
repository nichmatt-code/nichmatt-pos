<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use BelongsToStore, HasFactory;

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'store_id',
        'created_by',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'is_age_based',
        'age_multiplier',
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
            'is_age_based' => 'boolean',
            'age_multiplier' => 'integer',
            'gift_qty' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function giftProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    public function hasDiscount(): bool
    {
        return $this->discount_type !== null;
    }

    public function hasGift(): bool
    {
        return $this->gift_product_id !== null;
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

    /**
     * The discount amount this coupon actually gives, given a cart subtotal
     * and (if this coupon is age-based) the redeeming customer's age.
     */
    public function discountAmountFor(int $subtotal, ?int $customerAge = null): int
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        $value = $this->discount_value ?? 0;

        if ($this->is_age_based) {
            $value += ($customerAge ?? 0) * ($this->age_multiplier ?? 0);
        }

        $value = max(0, $value);

        $amount = $this->discount_type === self::DISCOUNT_PERCENT
            ? (int) round($subtotal * $value / 100)
            : $value;

        return min($amount, $subtotal);
    }
}
