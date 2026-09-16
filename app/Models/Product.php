<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'stock_qty',
        'unit',
        'image_path',
        'is_active',
        'is_out_of_stock',
        'is_unlimited_stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost_price' => 'integer',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
            'is_out_of_stock' => 'boolean',
            'is_unlimited_stock' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * The inventory items consumed to make one unit of this product, with
     * the quantity of each used per sale carried in the pivot.
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'product_ingredients')
            ->withPivot('qty_used')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return ! $this->is_unlimited_stock && ! $this->is_out_of_stock && $this->stock_qty <= $threshold;
    }

    /**
     * Whether this product can currently be added to a cart: not manually
     * marked as sold out, and either unlimited or actually in stock.
     */
    public function isAvailable(): bool
    {
        if ($this->is_out_of_stock) {
            return false;
        }

        return $this->is_unlimited_stock || $this->stock_qty > 0;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * A short readable product code, e.g. SKU-7K2Q4M. Editable afterwards.
     */
    public static function generateUniqueSku(int $storeId): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $sku = 'SKU-'.collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::withoutGlobalScopes()->where('store_id', $storeId)->where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * A 12-digit numeric code, scannable and printable as a Code128 barcode.
     */
    public static function generateUniqueBarcode(int $storeId): string
    {
        do {
            $barcode = (string) random_int(100000000000, 999999999999);
        } while (static::withoutGlobalScopes()->where('store_id', $storeId)->where('barcode', $barcode)->exists());

        return $barcode;
    }
}
