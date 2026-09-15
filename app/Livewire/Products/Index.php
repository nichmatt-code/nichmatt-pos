<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public ?int $category_id = null;

    public string $sku = '';

    public string $barcode = '';

    public string $price = '';

    public string $cost_price = '';

    public string $unit = 'pcs';

    public string $stock_qty = '0';

    public ?int $stockProductId = null;

    public string $stockType = 'in';

    public string $stockQty = '';

    public string $stockNote = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createProduct(): void
    {
        $this->reset(['editingId', 'name', 'category_id', 'sku', 'barcode', 'price', 'cost_price', 'unit', 'stock_qty']);
        $this->unit = 'pcs';
        $this->stock_qty = '0';
        $this->showFormModal = true;
    }

    public function editProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->category_id = $product->category_id;
        $this->sku = (string) $product->sku;
        $this->barcode = (string) $product->barcode;
        $this->price = (string) $product->price;
        $this->cost_price = (string) $product->cost_price;
        $this->unit = $product->unit;
        $this->stock_qty = (string) $product->stock_qty;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'stock_qty' => ['required', 'integer', 'min:0'],
        ]);

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($validated);
        } else {
            Product::create($validated);
        }

        $this->showFormModal = false;
    }

    public function delete(int $productId): void
    {
        Product::findOrFail($productId)->delete();
    }

    public function openStockModal(int $productId): void
    {
        $this->stockProductId = $productId;
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
            $product = Product::findOrFail($this->stockProductId);

            $delta = match ($validated['stockType']) {
                'in' => (int) $validated['stockQty'],
                'out' => -(int) $validated['stockQty'],
                'adjustment' => (int) $validated['stockQty'] - $product->stock_qty,
            };

            $product->increment('stock_qty', $delta);

            StockMovement::create([
                'product_id' => $product->id,
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
        return view('livewire.products.index', [
            'products' => Product::query()
                ->with('category')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
