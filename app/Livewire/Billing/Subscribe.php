<?php

namespace App\Livewire\Billing;

use App\Livewire\Actions\Logout;
use App\Models\PromoCode;
use App\Models\StoreSubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Midtrans\Snap;

class Subscribe extends Component
{
    public ?string $snapToken = null;

    public ?int $selectedPlanId = null;

    public string $promoCodeInput = '';

    public ?int $appliedPromoCodeId = null;

    public function mount(): void
    {
        $this->selectedPlanId = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function selectPlan(int $planId): void
    {
        $this->selectedPlanId = $planId;
    }

    public function applyPromoCode(): void
    {
        $this->resetErrorBag('promoCodeInput');

        $code = trim($this->promoCodeInput);

        if ($code === '') {
            return;
        }

        $promoCode = PromoCode::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        if (! $promoCode || ! $promoCode->isValid()) {
            $this->addError('promoCodeInput', 'Kode promo tidak valid atau sudah tidak berlaku.');

            return;
        }

        $this->appliedPromoCodeId = $promoCode->id;
    }

    public function removePromoCode(): void
    {
        $this->appliedPromoCodeId = null;
        $this->promoCodeInput = '';
        $this->resetErrorBag('promoCodeInput');
    }

    public function getSelectedPlanProperty(): ?SubscriptionPlan
    {
        return SubscriptionPlan::find($this->selectedPlanId);
    }

    public function getAppliedPromoCodeProperty(): ?PromoCode
    {
        return $this->appliedPromoCodeId ? PromoCode::find($this->appliedPromoCodeId) : null;
    }

    public function getFinalPriceProperty(): int
    {
        $plan = $this->selectedPlan;

        if (! $plan) {
            return 0;
        }

        $price = $plan->effectivePrice();

        return $this->appliedPromoCode ? $this->appliedPromoCode->applyTo($price) : $price;
    }

    public function subscribe(): void
    {
        $plan = $this->selectedPlan;

        if (! $plan) {
            $this->addError('selectedPlanId', 'Pilih paket langganan terlebih dahulu.');

            return;
        }

        $store = Auth::user()->store;
        $promoCode = $this->appliedPromoCode;
        $finalPrice = $this->finalPrice;

        $orderId = 'SUB-'.$store->id.'-'.now()->format('YmdHis').'-'.random_int(100, 999);

        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'subscription_plan_id' => $plan->id,
            'promo_code_id' => $promoCode?->id,
            'order_id' => $orderId,
            'amount' => $finalPrice,
            'duration_days' => $plan->duration_days,
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
                'id' => 'subscription-'.$plan->code,
                'price' => $finalPrice,
                'quantity' => 1,
                'name' => $plan->name.' NichmattPOS - '.$store->name,
            ]],
        ]);
    }

    public function render(): View
    {
        $store = Auth::user()->store;

        return view('livewire.billing.subscribe', [
            'store' => $store,
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
            'clientKey' => config('services.midtrans.client_key'),
            'isProduction' => (bool) config('services.midtrans.is_production'),
            'latestPayment' => $store->subscriptionPayments()->latest()->first(),
        ]);
    }
}
