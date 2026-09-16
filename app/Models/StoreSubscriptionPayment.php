<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\StoreSubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSubscriptionPayment extends Model
{
    /** @use HasFactory<StoreSubscriptionPaymentFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'subscription_plan_id',
        'promo_code_id',
        'order_id',
        'amount',
        'duration_days',
        'status',
        'payment_type',
        'midtrans_transaction_id',
        'period_start',
        'period_end',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'duration_days' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }
}
