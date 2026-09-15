<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\StoreSubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSubscriptionPayment extends Model
{
    /** @use HasFactory<StoreSubscriptionPaymentFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'order_id',
        'amount',
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
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
