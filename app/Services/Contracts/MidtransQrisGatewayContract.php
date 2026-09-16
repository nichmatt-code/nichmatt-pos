<?php

namespace App\Services\Contracts;

use App\Models\Store;

interface MidtransQrisGatewayContract
{
    /**
     * Create a dynamic QRIS charge against the given store's own Midtrans
     * merchant account and return the raw Midtrans response object.
     */
    public function charge(Store $store, string $orderId, int $amount): object;

    /**
     * Look up a charge's current status against the store's own account.
     */
    public function status(Store $store, string $orderId): object;

    /**
     * Cancel a still-pending charge against the store's own account.
     */
    public function cancel(Store $store, string $orderId): void;
}
