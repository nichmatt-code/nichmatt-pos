<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'sku',
        'unit',
        'stock_qty',
        'min_stock',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock_qty' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_stock;
    }

    /**
     * A short readable inventory code, e.g. INV-7K2Q4M. Editable afterwards.
     */
    public static function generateUniqueSku(int $storeId): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $sku = 'INV-'.collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::withoutGlobalScopes()->where('store_id', $storeId)->where('sku', $sku)->exists());

        return $sku;
    }
}
