<?php

namespace App\Http\Controllers;

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

        $payment->applyMidtransStatus(
            $payload['transaction_status'] ?? null,
            $payload['fraud_status'] ?? null,
            $payload['payment_type'] ?? null,
            $payload['transaction_id'] ?? null,
        );

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
}
