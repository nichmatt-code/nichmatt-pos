<?php

namespace App\Models;

use Database\Factories\SelfOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfOrderItem extends Model
{
    /** @use HasFactory<SelfOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'self_order_id',
        'product_id',
        'package_id',
        'product_name',
        'price',
        'qty',
        'note',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'qty' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function selfOrder(): BelongsTo
    {
        return $this->belongsTo(SelfOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
