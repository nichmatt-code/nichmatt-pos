<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'self_order_id',
        'customer_id',
        'transaction_no',
        'customer_name',
        'customer_phone',
        'note',
        'subtotal',
        'discount',
        'coupon_id',
        'coupon_discount_amount',
        'tax_amount',
        'service_charge_amount',
        'total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'status',
        'prepared_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount' => 'integer',
            'coupon_discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'service_charge_amount' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
            'change_amount' => 'integer',
            'prepared_at' => 'datetime',
        ];
    }

    public function isPrepared(): bool
    {
        return $this->prepared_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function selfOrder(): BelongsTo
    {
        return $this->belongsTo(SelfOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
