<?php

namespace App\Services;

use App\Models\Store;
use App\Services\Contracts\MidtransQrisGatewayContract;
use Midtrans\Config as MidtransConfig;
use Midtrans\CoreApi;
use Midtrans\Transaction as MidtransTransactionApi;

/**
 * Talks to Midtrans using each store's OWN merchant credentials (rather than
 * the platform-wide keys in config/services.php, which are only used for
 * stores paying their NichmattPOS subscription) so a customer's payment
 * settles directly into that store's own Midtrans account.
 */
class MidtransQrisGateway implements MidtransQrisGatewayContract
{
    public function charge(Store $store, string $orderId, int $amount): object
    {
        $this->configureFor($store);

        return CoreApi::charge([
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            // Without an explicit acquirer, Midtrans can route to a QRIS
            // channel that isn't active by default (surfaced as a 402
            // "Payment is not activated" error) - gopay is the acquirer
            // active by default on every sandbox AND production account.
            'qris' => [
                'acquirer' => 'gopay',
            ],
        ]);
    }

    public function status(Store $store, string $orderId): object
    {
        $this->configureFor($store);

        return MidtransTransactionApi::status($orderId);
    }

    public function cancel(Store $store, string $orderId): void
    {
        $this->configureFor($store);

        MidtransTransactionApi::cancel($orderId);
    }

    private function configureFor(Store $store): void
    {
        MidtransConfig::$serverKey = $store->midtrans_server_key;
        MidtransConfig::$isProduction = (bool) $store->midtrans_is_production;
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }
}
