<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public bool $showFormModal = false;

    public bool $showStockModal = false;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $unit = 'pcs';

    public string $stock_qty = '0';

    public string $min_stock = '0';

    public string $note = '';

    public ?int $stockItemId = null;

    public string $stockType = 'in';

    public string $stockQty = '';

    public string $stockNote = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createItem(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'unit', 'stock_qty', 'min_stock', 'note']);
        $this->unit = 'pcs';
        $this->stock_qty = '0';
        $this->min_stock = '0';
        $this->showFormModal = true;
    }

    public function editItem(int $itemId): void
    {
        $item = InventoryItem::findOrFail($itemId);

        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->sku = (string) $item->sku;
        $this->unit = $item->unit;
        $this->stock_qty = (string) $item->stock_qty;
        $this->min_stock = (string) $item->min_stock;
        $this->note = (string) $item->note;
        $this->showFormModal = true;
    }

    public function generateSku(): void
    {
        $this->sku = InventoryItem::generateUniqueSku(Auth::user()->store_id);
    }

    public function save(): void
    {
        $storeId = Auth::user()->store_id;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable', 'string', 'max:255',
                Rule::unique('inventory_items', 'sku')->where('store_id', $storeId)->ignore($this->editingId),
            ],
            'unit' => ['required', 'string', 'max:50'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['sku'] = $validated['sku'] !== '' ? $validated['sku'] : null;
        $validated['note'] = $validated['note'] !== '' ? $validated['note'] : null;

        if ($this->editingId) {
            InventoryItem::findOrFail($this->editingId)->update($validated);
        } else {
            InventoryItem::create($validated);
        }

        $this->showFormModal = false;
    }

    public function delete(int $itemId): void
    {
        InventoryItem::findOrFail($itemId)->delete();
    }

    public function openStockModal(int $itemId): void
    {
        $this->stockItemId = $itemId;
        $this->stockType = 'in';
        $this->stockQty = '';
        $this->stockNote = '';
        $this->showStockModal = true;
    }

    public function saveStock(): void
    {
        $validated = $this->validate([
            'stockType' => ['required', 'in:in,out,adjustment'],
            'stockQty' => ['required', 'integer', 'min:0'],
            'stockNote' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $item = InventoryItem::findOrFail($this->stockItemId);

            $delta = match ($validated['stockType']) {
                'in' => (int) $validated['stockQty'],
                'out' => -(int) $validated['stockQty'],
                'adjustment' => (int) $validated['stockQty'] - $item->stock_qty,
            };

            $item->increment('stock_qty', $delta);

            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'user_id' => Auth::id(),
                'type' => $validated['stockType'],
                'qty' => $delta,
                'note' => $validated['stockNote'] ?: null,
            ]);
        });

        $this->showStockModal = false;
    }

    public function render(): View
    {
        return view('livewire.inventory.index', [
            'items' => InventoryItem::query()
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
