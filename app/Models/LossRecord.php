<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\LossRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LossRecord extends Model
{
    /** @use HasFactory<LossRecordFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'loss_no',
        'reason',
        'total_cost_value',
    ];

    protected function casts(): array
    {
        return [
            'total_cost_value' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(LossRecordItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
