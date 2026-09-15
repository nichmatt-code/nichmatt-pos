<?php

namespace App\Livewire\Billing;

use App\Livewire\Actions\Logout;
use App\Models\Store;
use App\Models\StoreSubscriptionPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Midtrans\Snap;

class Subscribe extends Component
{
    public ?string $snapToken = null;

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Create a pending payment record and request a Snap token from
     * Midtrans so the browser can open the payment popup.
     */
    public function subscribe(): void
    {
        $store = Auth::user()->store;

        $orderId = 'SUB-'.$store->id.'-'.now()->format('YmdHis').'-'.random_int(100, 999);

        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'order_id' => $orderId,
            'amount' => Store::SUBSCRIPTION_MONTHLY_PRICE,
            'status' => 'pending',
        ]);

        $this->snapToken = Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => $payment->amount,
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ],
            'item_details' => [[
                'id' => 'subscription-monthly',
                'price' => Store::SUBSCRIPTION_MONTHLY_PRICE,
                'quantity' => 1,
                'name' => 'Langganan NichmattPOS 1 Bulan - '.$store->name,
            ]],
        ]);
    }

    public function render(): View
    {
        $store = Auth::user()->store;

        return view('livewire.billing.subscribe', [
            'store' => $store,
            'clientKey' => config('services.midtrans.client_key'),
            'isProduction' => (bool) config('services.midtrans.is_production'),
            'latestPayment' => $store->subscriptionPayments()->latest()->first(),
        ]);
    }
}
