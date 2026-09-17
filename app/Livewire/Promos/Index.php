<?php

namespace App\Livewire\Promos;

use App\Livewire\Concerns\Sortable;
use App\Models\Product;
use App\Models\Promo;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use Sortable, WithPagination;

    public bool $showFormModal = false;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $type = Promo::TYPE_DISCOUNT;

    public ?int $productId = null;

    public string $discountType = Promo::DISCOUNT_PERCENT;

    public string $discountValue = '';

    public string $minQty = '1';

    public ?int $giftProductId = null;

    public string $giftQty = '1';

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createPromo(): void
    {
        $this->reset(['editingId', 'name', 'type', 'productId', 'discountType', 'discountValue', 'minQty', 'giftProductId', 'giftQty', 'startsAt', 'endsAt', 'isActive']);
        $this->type = Promo::TYPE_DISCOUNT;
        $this->discountType = Promo::DISCOUNT_PERCENT;
        $this->minQty = '1';
        $this->giftQty = '1';
        $this->isActive = true;
        $this->showFormModal = true;
    }

    public function editPromo(int $promoId): void
    {
        $promo = Promo::findOrFail($promoId);

        $this->editingId = $promo->id;
        $this->name = $promo->name;
        $this->type = $promo->type;
        $this->productId = $promo->product_id;
        $this->discountType = $promo->discount_type ?? Promo::DISCOUNT_PERCENT;
        $this->discountValue = (string) $promo->discount_value;
        $this->minQty = (string) ($promo->min_qty ?? 1);
        $this->giftProductId = $promo->gift_product_id;
        $this->giftQty = (string) ($promo->gift_qty ?? 1);
        $this->startsAt = $promo->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $promo->ends_at?->format('Y-m-d') ?? '';
        $this->isActive = $promo->is_active;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.Promo::TYPE_DISCOUNT.','.Promo::TYPE_GIFT],
            'productId' => ['required', 'exists:products,id'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'isActive' => ['boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'product_id' => $validated['productId'],
            'starts_at' => $validated['startsAt'] !== '' ? $validated['startsAt'] : null,
            'ends_at' => $validated['endsAt'] !== '' ? $validated['endsAt'] : null,
            'is_active' => $validated['isActive'],
            'discount_type' => null,
            'discount_value' => null,
            'min_qty' => null,
            'gift_product_id' => null,
            'gift_qty' => null,
        ];

        if ($this->type === Promo::TYPE_DISCOUNT) {
            $this->validate([
                'discountType' => ['required', 'in:'.Promo::DISCOUNT_PERCENT.','.Promo::DISCOUNT_FIXED],
                'discountValue' => ['required', 'integer', 'min:1'],
            ]);

            $data['discount_type'] = $this->discountType;
            $data['discount_value'] = (int) $this->discountValue;
        } else {
            $this->validate([
                'minQty' => ['required', 'integer', 'min:1'],
                'giftProductId' => ['required', 'exists:products,id'],
                'giftQty' => ['required', 'integer', 'min:1'],
            ]);

            $data['min_qty'] = (int) $this->minQty;
            $data['gift_product_id'] = $this->giftProductId;
            $data['gift_qty'] = (int) $this->giftQty;
        }

        if ($this->editingId) {
            Promo::findOrFail($this->editingId)->update($data);
        } else {
            Promo::create($data);
        }

        $this->showFormModal = false;
    }

    public function delete(int $promoId): void
    {
        Promo::findOrFail($promoId)->delete();
    }

    public function render(): View
    {
        return view('livewire.promos.index', [
            'promos' => Promo::query()
                ->with(['product', 'giftProduct'])
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortField ?: 'created_at', $this->sortField ? $this->sortDirection : 'desc')
                ->paginate(15),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
