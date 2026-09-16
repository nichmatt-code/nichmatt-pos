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

    /**
     * Apply a Midtrans transaction status to this payment, activating the
     * store's subscription on capture/settlement. Shared by the webhook
     * notification handler and the reconciliation command, since Midtrans
     * notifications are not always delivered (e.g. a misconfigured
     * Notification URL) and pending payments need to be settled by polling
     * the status API instead.
     */
    public function applyMidtransStatus(
        ?string $transactionStatus,
        ?string $fraudStatus,
        ?string $paymentType = null,
        ?string $transactionId = null,
    ): void {
        $this->payment_type = $paymentType ?? $this->payment_type;
        $this->midtrans_transaction_id = $transactionId ?? $this->midtrans_transaction_id;

        if (in_array($transactionStatus, ['capture', 'settlement'], true) && $fraudStatus !== 'deny') {
            $this->activateSubscription();
        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)) {
            $this->status = $transactionStatus;
            $this->save();
        } else {
            $this->status = $transactionStatus ?? $this->status;
            $this->save();
        }
    }

    /**
     * Midtrans can (and does) send duplicate notifications for the same
     * transaction, so a payment already marked settled is never
     * reprocessed - otherwise the store's subscription and any promo
     * code's redemption count would be extended/incremented twice.
     */
    private function activateSubscription(): void
    {
        if ($this->status === 'settlement') {
            return;
        }

        $store = Store::findOrFail($this->store_id);

        $durationDays = $this->duration_days ?? 30;
        $periodStart = $store->nextSubscriptionPeriodStart();
        $periodEnd = $periodStart->copy()->addDays($durationDays);

        $this->status = 'settlement';
        $this->period_start = $periodStart;
        $this->period_end = $periodEnd;
        $this->paid_at = now();
        $this->save();

        $store->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $periodEnd,
        ]);

        $this->promoCode?->increment('times_redeemed');
    }
}
