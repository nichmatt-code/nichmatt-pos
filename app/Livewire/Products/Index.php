<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\Sortable;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use Sortable, WithFileUploads, WithPagination;

    public bool $showFormModal = false;

    public bool $showStockModal = false;

    public string $search = '';

    public ?int $filterCategoryId = null;

    public string $filterStatus = 'all';

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public ?int $category_id = null;

    /** @var array<int, int> */
    public array $tag_ids = [];

    public string $newTagName = '';

    public string $sku = '';

    public string $barcode = '';

    public string $price = '';

    public string $cost_price = '';

    public string $unit = 'pcs';

    public string $stock_qty = '0';

    public bool $is_out_of_stock = false;

    public bool $is_unlimited_stock = false;

    /** @var array<int, string> keyed by inventory_item_id */
    public array $ingredientQty = [];

    public string $newIngredientName = '';

    public string $newIngredientUnit = 'pcs';

    public mixed $image = null;

    public ?string $existingImageUrl = null;

    public bool $removeExistingImage = false;

    public ?int $stockProductId = null;

    public string $stockType = 'in';

    public string $stockQty = '';

    public string $stockNote = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function createProduct(): void
    {
        $this->reset(['editingId', 'name', 'description', 'category_id', 'tag_ids', 'newTagName', 'sku', 'barcode', 'price', 'cost_price', 'unit', 'stock_qty', 'is_out_of_stock', 'is_unlimited_stock', 'ingredientQty', 'newIngredientName', 'newIngredientUnit', 'image', 'existingImageUrl', 'removeExistingImage']);
        $this->unit = 'pcs';
        $this->stock_qty = '0';
        $this->newIngredientUnit = 'pcs';
        $this->showFormModal = true;
    }

    public function editProduct(int $productId): void
    {
        $product = Product::with(['tags', 'ingredients'])->findOrFail($productId);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->description = (string) $product->description;
        $this->category_id = $product->category_id;
        $this->tag_ids = $product->tags->pluck('id')->all();
        $this->newTagName = '';
        $this->sku = (string) $product->sku;
        $this->barcode = (string) $product->barcode;
        $this->price = (string) $product->price;
        $this->cost_price = (string) $product->cost_price;
        $this->unit = $product->unit;
        $this->stock_qty = (string) $product->stock_qty;
        $this->is_out_of_stock = $product->is_out_of_stock;
        $this->is_unlimited_stock = $product->is_unlimited_stock;
        $this->ingredientQty = $product->ingredients
            ->mapWithKeys(fn (InventoryItem $item) => [$item->id => (string) $item->pivot->qty_used])
            ->all();
        $this->newIngredientName = '';
        $this->newIngredientUnit = 'pcs';
        $this->image = null;
        $this->existingImageUrl = $product->imageUrl();
        $this->removeExistingImage = false;
        $this->showFormModal = true;
    }

    /**
     * Toggle whether an inventory item is used as an ingredient for the
     * product being edited, defaulting its usage to 1 per sale.
     */
    public function toggleIngredient(int $inventoryItemId): void
    {
        if (array_key_exists($inventoryItemId, $this->ingredientQty)) {
            unset($this->ingredientQty[$inventoryItemId]);
        } else {
            $this->ingredientQty[$inventoryItemId] = '1';
        }
    }

    /**
     * Quick-create a brand new Inventory item (rather than picking from the
     * existing list) and immediately mark it as an ingredient of this
     * product. It shows up in the standalone Inventory page right away.
     */
    public function addIngredient(): void
    {
        $validated = $this->validate([
            'newIngredientName' => ['required', 'string', 'max:255'],
            'newIngredientUnit' => ['required', 'string', 'max:50'],
        ], attributes: ['newIngredientName' => 'nama bahan', 'newIngredientUnit' => 'satuan']);

        $item = InventoryItem::create([
            'name' => $validated['newIngredientName'],
            'unit' => $validated['newIngredientUnit'],
            'stock_qty' => 0,
            'min_stock' => 0,
        ]);

        $this->ingredientQty[$item->id] = '1';
        $this->newIngredientName = '';
        $this->newIngredientUnit = 'pcs';
    }

    /**
     * Quick toggle from the product list, so marking something sold out
     * (or back in stock) doesn't require opening the edit form.
     */
    public function toggleOutOfStock(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_out_of_stock' => ! $product->is_out_of_stock]);
    }

    /**
     * Quick-create a tag by name (or reuse it if it already exists for this
     * store) and attach it to the form's current selection.
     */
    public function addTag(): void
    {
        $name = trim($this->newTagName);

        if ($name === '') {
            return;
        }

        $tag = Tag::firstOrCreate(['name' => $name]);

        if (! in_array($tag->id, $this->tag_ids, true)) {
            $this->tag_ids[] = $tag->id;
        }

        $this->newTagName = '';
    }

    public function removeTag(int $tagId): void
    {
        $this->tag_ids = array_values(array_diff($this->tag_ids, [$tagId]));
    }

    public function removeImage(): void
    {
        $this->image = null;
        $this->existingImageUrl = null;
        $this->removeExistingImage = true;
    }

    public function generateSku(): void
    {
        $this->sku = Product::generateUniqueSku(Auth::user()->store_id);
    }

    public function generateBarcode(): void
    {
        $this->barcode = Product::generateUniqueBarcode(Auth::user()->store_id);
    }

    public function save(): void
    {
        $storeId = Auth::user()->store_id;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'sku')->where('store_id', $storeId)->ignore($this->editingId),
            ],
            'barcode' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'barcode')->where('store_id', $storeId)->ignore($this->editingId),
            ],
            'price' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'is_out_of_stock' => ['boolean'],
            'is_unlimited_stock' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['sku'] = $validated['sku'] !== '' ? $validated['sku'] : null;
        $validated['barcode'] = $validated['barcode'] !== '' ? $validated['barcode'] : null;
        $validated['description'] = $validated['description'] !== '' ? $validated['description'] : null;

        $product = $this->editingId ? Product::findOrFail($this->editingId) : null;

        if ($this->image) {
            if ($product?->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $this->image->store('products', 'public');
        } elseif ($this->removeExistingImage && $product?->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $validated['image_path'] = null;
        }
        unset($validated['image']);

        if ($product) {
            $product->update($validated);
        } else {
            $product = Product::create($validated);
        }

        $product->tags()->sync($this->tag_ids);

        $ingredients = collect($this->ingredientQty)
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0)
            ->mapWithKeys(fn (int $qty, int $inventoryItemId) => [$inventoryItemId => ['qty_used' => $qty]]);
        $product->ingredients()->sync($ingredients);

        $this->showFormModal = false;
    }

    public function delete(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
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
                ->with(['category', 'tags', 'store'])
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->when($this->filterCategoryId, fn ($query) => $query->where('category_id', $this->filterCategoryId))
                ->when($this->filterStatus === 'out_of_stock', fn ($query) => $query->where('is_out_of_stock', true))
                ->when($this->filterStatus === 'low_stock', fn ($query) => $query
                    ->where('is_unlimited_stock', false)
                    ->where('is_out_of_stock', false)
                    ->where('stock_qty', '<=', 5))
                ->orderBy($this->sortField ?: 'name', $this->sortDirection)
                ->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'inventoryItems' => InventoryItem::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
