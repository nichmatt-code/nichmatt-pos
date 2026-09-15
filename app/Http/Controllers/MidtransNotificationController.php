<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreSubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends Controller
{
    /**
     * Handle a payment status notification pushed by Midtrans. Activates
     * or extends the store's subscription once payment is confirmed.
     */
    public function __invoke(Request $request): Response
    {
        $payload = $request->all();

        if (! $this->hasValidSignature($payload)) {
            Log::warning('Midtrans notification with invalid signature.', ['order_id' => $payload['order_id'] ?? null]);

            return response('Invalid signature', 403);
        }

        $payment = StoreSubscriptionPayment::withoutGlobalScopes()
            ->where('order_id', $payload['order_id'] ?? null)
            ->first();

        if (! $payment) {
            return response('Order not found', 404);
        }

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        $payment->payment_type = $payload['payment_type'] ?? $payment->payment_type;
        $payment->midtrans_transaction_id = $payload['transaction_id'] ?? $payment->midtrans_transaction_id;

        if (in_array($transactionStatus, ['capture', 'settlement'], true) && $fraudStatus !== 'deny') {
            $this->activateSubscription($payment);
        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)) {
            $payment->status = $transactionStatus;
            $payment->save();
        } else {
            $payment->status = $transactionStatus ?? $payment->status;
            $payment->save();
        }

        return response('OK', 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasValidSignature(array $payload): bool
    {
        if (! isset($payload['order_id'], $payload['status_code'], $payload['gross_amount'], $payload['signature_key'])) {
            return false;
        }

        $expected = hash('sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key')
        );

        return hash_equals($expected, $payload['signature_key']);
    }

    private function activateSubscription(StoreSubscriptionPayment $payment): void
    {
        $store = Store::findOrFail($payment->store_id);

        $periodStart = $store->nextSubscriptionPeriodStart();
        $periodEnd = $periodStart->copy()->addMonth();

        $payment->status = 'settlement';
        $payment->period_start = $periodStart;
        $payment->period_end = $periodEnd;
        $payment->paid_at = now();
        $payment->save();

        $store->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $periodEnd,
        ]);
    }
}
