<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use BelongsToStore, HasFactory;

    public const TYPE_PRODUCT = 'product';

    public const TYPE_INVENTORY = 'inventory';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'store_id',
        'code',
        'type',
        'status',
        'note',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isForProducts(): bool
    {
        return $this->type === self::TYPE_PRODUCT;
    }
}
