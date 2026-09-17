<?php

namespace App\Livewire\Coupons;

use App\Livewire\Concerns\Sortable;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use Sortable, WithPagination;

    public bool $showFormModal = false;

    public string $search = '';

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public bool $includeDiscount = false;

    public string $discountType = Coupon::DISCOUNT_PERCENT;

    public string $discountValue = '';

    public bool $isAgeBased = false;

    public string $ageMultiplier = '';

    public bool $includeGift = false;

    public ?int $giftProductId = null;

    public string $giftQty = '1';

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createCoupon(): void
    {
        $this->reset([
            'editingId', 'code', 'name', 'includeDiscount', 'discountType', 'discountValue',
            'isAgeBased', 'ageMultiplier', 'includeGift', 'giftProductId', 'giftQty', 'startsAt', 'endsAt', 'isActive',
        ]);
        $this->discountType = Coupon::DISCOUNT_PERCENT;
        $this->giftQty = '1';
        $this->isActive = true;
        $this->showFormModal = true;
    }

    public function editCoupon(int $couponId): void
    {
        $coupon = Coupon::findOrFail($couponId);

        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->name = $coupon->name;
        $this->includeDiscount = $coupon->hasDiscount();
        $this->discountType = $coupon->discount_type ?? Coupon::DISCOUNT_PERCENT;
        $this->discountValue = (string) $coupon->discount_value;
        $this->isAgeBased = $coupon->is_age_based;
        $this->ageMultiplier = (string) $coupon->age_multiplier;
        $this->includeGift = $coupon->hasGift();
        $this->giftProductId = $coupon->gift_product_id;
        $this->giftQty = (string) ($coupon->gift_qty ?? 1);
        $this->startsAt = $coupon->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $coupon->ends_at?->format('Y-m-d') ?? '';
        $this->isActive = $coupon->is_active;
        $this->showFormModal = true;
    }

    public function generateCode(): void
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 8))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
        } while (Coupon::where('code', $code)->exists());

        $this->code = $code;
    }

    public function save(): void
    {
        $storeId = Auth::user()->store_id;

        $validated = $this->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('coupons', 'code')->where('store_id', $storeId)->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'isActive' => ['boolean'],
        ]);

        if (! $this->includeDiscount && ! $this->includeGift) {
            $this->addError('includeDiscount', 'Kupon harus punya diskon dan/atau hadiah.');

            return;
        }

        $data = [
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'starts_at' => $validated['startsAt'] !== '' ? $validated['startsAt'] : null,
            'ends_at' => $validated['endsAt'] !== '' ? $validated['endsAt'] : null,
            'is_active' => $validated['isActive'],
            'discount_type' => null,
            'discount_value' => null,
            'is_age_based' => false,
            'age_multiplier' => null,
            'gift_product_id' => null,
            'gift_qty' => null,
        ];

        if ($this->includeDiscount) {
            $this->validate([
                'discountType' => ['required', 'in:'.Coupon::DISCOUNT_PERCENT.','.Coupon::DISCOUNT_FIXED],
                'discountValue' => ['nullable', 'integer', 'min:0'],
                'isAgeBased' => ['boolean'],
                'ageMultiplier' => [$this->isAgeBased ? 'required' : 'nullable', 'integer', 'min:0'],
            ]);

            $data['discount_type'] = $this->discountType;
            $data['discount_value'] = $this->discountValue !== '' ? (int) $this->discountValue : 0;
            $data['is_age_based'] = $this->isAgeBased;
            $data['age_multiplier'] = $this->isAgeBased ? (int) $this->ageMultiplier : null;
        }

        if ($this->includeGift) {
            $this->validate([
                'giftProductId' => ['required', 'exists:products,id'],
                'giftQty' => ['required', 'integer', 'min:1'],
            ]);

            $data['gift_product_id'] = $this->giftProductId;
            $data['gift_qty'] = (int) $this->giftQty;
        }

        if ($this->editingId) {
            Coupon::findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            Coupon::create($data);
        }

        $this->showFormModal = false;
    }

    public function delete(int $couponId): void
    {
        Coupon::findOrFail($couponId)->delete();
    }

    public function render(): View
    {
        return view('livewire.coupons.index', [
            'coupons' => Coupon::query()
                ->with(['giftProduct', 'creator'])
                ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                ))
                ->orderBy($this->sortField ?: 'created_at', $this->sortField ? $this->sortDirection : 'desc')
                ->paginate(15),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
