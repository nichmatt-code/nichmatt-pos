<?php

namespace App\Livewire\Developer;

use App\Models\PromoCode;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Pricing extends Component
{
    public bool $showPlanModal = false;

    public ?int $editingPlanId = null;

    public string $planCode = '';

    public string $planName = '';

    public string $planDurationDays = '';

    public string $planPrice = '';

    public string $planPromoPrice = '';

    public string $planPromoLabel = '';

    public string $planPromoEndsAt = '';

    public bool $planIsActive = true;

    public string $planSortOrder = '0';

    public bool $showPromoModal = false;

    public ?int $editingPromoId = null;

    public string $promoCode = '';

    public string $promoType = 'fixed';

    public string $promoValue = '';

    public string $promoMaxRedemptions = '';

    public string $promoExpiresAt = '';

    public bool $promoIsActive = true;

    public function createPlan(): void
    {
        $this->reset(['editingPlanId', 'planCode', 'planName', 'planDurationDays', 'planPrice', 'planPromoPrice', 'planPromoLabel', 'planPromoEndsAt', 'planSortOrder']);
        $this->planIsActive = true;
        $this->showPlanModal = true;
    }

    public function editPlan(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $this->editingPlanId = $plan->id;
        $this->planCode = $plan->code;
        $this->planName = $plan->name;
        $this->planDurationDays = (string) $plan->duration_days;
        $this->planPrice = (string) $plan->price;
        $this->planPromoPrice = (string) $plan->promo_price;
        $this->planPromoLabel = (string) $plan->promo_label;
        $this->planPromoEndsAt = $plan->promo_ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->planIsActive = $plan->is_active;
        $this->planSortOrder = (string) $plan->sort_order;
        $this->showPlanModal = true;
    }

    public function savePlan(): void
    {
        $validated = $this->validate([
            'planCode' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('subscription_plans', 'code')->ignore($this->editingPlanId)],
            'planName' => ['required', 'string', 'max:255'],
            'planDurationDays' => ['required', 'integer', 'min:1'],
            'planPrice' => ['required', 'integer', 'min:0'],
            'planPromoPrice' => ['nullable', 'integer', 'min:0'],
            'planPromoLabel' => ['nullable', 'string', 'max:100'],
            'planPromoEndsAt' => ['nullable', 'date'],
            'planSortOrder' => ['required', 'integer'],
        ]);

        $data = [
            'code' => $validated['planCode'],
            'name' => $validated['planName'],
            'duration_days' => $validated['planDurationDays'],
            'price' => $validated['planPrice'],
            'promo_price' => $validated['planPromoPrice'] !== '' ? $validated['planPromoPrice'] : null,
            'promo_label' => $validated['planPromoLabel'] !== '' ? $validated['planPromoLabel'] : null,
            'promo_ends_at' => $validated['planPromoEndsAt'] !== '' ? $validated['planPromoEndsAt'] : null,
            'is_active' => $this->planIsActive,
            'sort_order' => $validated['planSortOrder'],
        ];

        if ($this->editingPlanId) {
            SubscriptionPlan::findOrFail($this->editingPlanId)->update($data);
        } else {
            SubscriptionPlan::create($data);
        }

        $this->showPlanModal = false;
    }

    public function deletePlan(int $planId): void
    {
        SubscriptionPlan::findOrFail($planId)->delete();
    }

    public function createPromo(): void
    {
        $this->reset(['editingPromoId', 'promoCode', 'promoValue', 'promoMaxRedemptions', 'promoExpiresAt']);
        $this->promoType = 'fixed';
        $this->promoIsActive = true;
        $this->showPromoModal = true;
    }

    public function editPromo(int $promoId): void
    {
        $promo = PromoCode::findOrFail($promoId);

        $this->editingPromoId = $promo->id;
        $this->promoCode = $promo->code;
        $this->promoType = $promo->type;
        $this->promoValue = (string) $promo->value;
        $this->promoMaxRedemptions = (string) $promo->max_redemptions;
        $this->promoExpiresAt = $promo->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->promoIsActive = $promo->is_active;
        $this->showPromoModal = true;
    }

    public function savePromo(): void
    {
        $validated = $this->validate([
            'promoCode' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('promo_codes', 'code')->ignore($this->editingPromoId)],
            'promoType' => ['required', 'in:fixed,percent'],
            'promoValue' => ['required', 'integer', 'min:1'],
            'promoMaxRedemptions' => ['nullable', 'integer', 'min:1'],
            'promoExpiresAt' => ['nullable', 'date'],
        ]);

        $data = [
            'code' => strtoupper($validated['promoCode']),
            'type' => $validated['promoType'],
            'value' => $validated['promoValue'],
            'max_redemptions' => $validated['promoMaxRedemptions'] !== '' ? $validated['promoMaxRedemptions'] : null,
            'expires_at' => $validated['promoExpiresAt'] !== '' ? $validated['promoExpiresAt'] : null,
            'is_active' => $this->promoIsActive,
        ];

        if ($this->editingPromoId) {
            PromoCode::findOrFail($this->editingPromoId)->update($data);
        } else {
            $data['times_redeemed'] = 0;
            PromoCode::create($data);
        }

        $this->showPromoModal = false;
    }

    public function deletePromo(int $promoId): void
    {
        PromoCode::findOrFail($promoId)->delete();
    }

    public function render(): View
    {
        return view('livewire.developer.pricing', [
            'plans' => SubscriptionPlan::orderBy('sort_order')->get(),
            'promoCodes' => PromoCode::latest()->get(),
        ]);
    }
}
