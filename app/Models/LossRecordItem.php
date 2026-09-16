<?php

namespace App\Models;

use Database\Factories\LossRecordItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LossRecordItem extends Model
{
    /** @use HasFactory<LossRecordItemFactory> */
    use HasFactory;

    protected $fillable = [
        'loss_record_id',
        'product_id',
        'product_name',
        'qty',
        'cost_price',
        'subtotal_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'cost_price' => 'integer',
            'subtotal_cost' => 'integer',
        ];
    }

    public function lossRecord(): BelongsTo
    {
        return $this->belongsTo(LossRecord::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
