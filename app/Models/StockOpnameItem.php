<?php

namespace App\Models;

use Database\Factories\StockOpnameItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    /** @use HasFactory<StockOpnameItemFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'inventory_item_id',
        'item_name',
        'unit',
        'system_qty',
        'counted_qty',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'integer',
            'counted_qty' => 'integer',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * The gap between what's physically counted and what the system had on
     * record, or null while this item hasn't been counted yet.
     */
    public function difference(): ?int
    {
        return $this->counted_qty === null ? null : $this->counted_qty - $this->system_qty;
    }
}
