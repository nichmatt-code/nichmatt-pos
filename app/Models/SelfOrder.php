<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\SelfOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfOrder extends Model
{
    /** @use HasFactory<SelfOrderFactory> */
    use BelongsToStore, HasFactory;

    public const VALID_MINUTES = 60;

    protected $fillable = [
        'store_id',
        'code',
        'customer_name',
        'note',
        'subtotal',
        'total',
        'status',
        'expires_at',
        'claimed_by',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'total' => 'integer',
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SelfOrderItem::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isPast();
    }

    /**
     * Generate a short, unambiguous code (no 0/O/1/I) that isn't currently
     * in use by another pending self-order.
     */
    public static function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }
}
