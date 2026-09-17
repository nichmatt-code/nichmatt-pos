<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'image_path',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Whether at least one more of this package could be sold right now,
     * given its component products' current stock.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->maxSellable() > 0;
    }

    /**
     * How many of this package could be sold right now, limited by the
     * scarcest component product's stock (unlimited if every component is).
     */
    public function maxSellable(): int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        $max = PHP_INT_MAX;

        foreach ($this->items as $item) {
            $product = $item->product;

            if (! $product || ! $product->isAvailable()) {
                return 0;
            }

            if (! $product->is_unlimited_stock) {
                $max = min($max, intdiv($product->stock_qty, max(1, $item->qty)));
            }
        }

        return $max;
    }

    /**
     * The combined cost price of one package, for profit reporting.
     */
    public function totalCostPrice(): int
    {
        return $this->items->sum(fn (PackageItem $item) => ($item->product?->cost_price ?? 0) * $item->qty);
    }
}
