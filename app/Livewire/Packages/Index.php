<?php

namespace App\Livewire\Packages;

use App\Livewire\Concerns\Sortable;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use Sortable, WithFileUploads, WithPagination;

    public bool $showFormModal = false;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $price = '';

    public bool $isActive = true;

    /** @var array<int, string> keyed by product_id */
    public array $itemQty = [];

    public mixed $image = null;

    public ?string $existingImageUrl = null;

    public bool $removeExistingImage = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createPackage(): void
    {
        $this->reset(['editingId', 'name', 'description', 'price', 'isActive', 'itemQty', 'image', 'existingImageUrl', 'removeExistingImage']);
        $this->isActive = true;
        $this->showFormModal = true;
    }

    public function editPackage(int $packageId): void
    {
        $package = Package::with('items')->findOrFail($packageId);

        $this->editingId = $package->id;
        $this->name = $package->name;
        $this->description = (string) $package->description;
        $this->price = (string) $package->price;
        $this->isActive = $package->is_active;
        $this->itemQty = $package->items
            ->mapWithKeys(fn (PackageItem $item) => [$item->product_id => (string) $item->qty])
            ->all();
        $this->image = null;
        $this->existingImageUrl = $package->imageUrl();
        $this->removeExistingImage = false;
        $this->showFormModal = true;
    }

    /**
     * Toggle whether a product is included as a component of the package
     * being edited, defaulting its quantity to 1 per package sold.
     */
    public function toggleItem(int $productId): void
    {
        if (array_key_exists($productId, $this->itemQty)) {
            unset($this->itemQty[$productId]);
        } else {
            $this->itemQty[$productId] = '1';
        }
    }

    public function removeImage(): void
    {
        $this->image = null;
        $this->existingImageUrl = null;
        $this->removeExistingImage = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $items = collect($this->itemQty)
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0);

        if ($items->isEmpty()) {
            $this->addError('itemQty', 'Pilih minimal 1 produk untuk paket ini.');

            return;
        }

        $validated['description'] = $validated['description'] !== '' ? $validated['description'] : null;
        $validated['is_active'] = $validated['isActive'];
        unset($validated['isActive']);

        $package = $this->editingId ? Package::findOrFail($this->editingId) : null;

        if ($this->image) {
            if ($package?->image_path) {
                Storage::disk('public')->delete($package->image_path);
            }
            $validated['image_path'] = $this->image->store('packages', 'public');
        } elseif ($this->removeExistingImage && $package?->image_path) {
            Storage::disk('public')->delete($package->image_path);
            $validated['image_path'] = null;
        }
        unset($validated['image']);

        if ($package) {
            $package->update($validated);
        } else {
            $package = Package::create($validated);
        }

        $package->items()->delete();

        foreach ($items as $productId => $qty) {
            PackageItem::create([
                'package_id' => $package->id,
                'product_id' => $productId,
                'qty' => $qty,
            ]);
        }

        $this->showFormModal = false;
    }

    public function delete(int $packageId): void
    {
        $package = Package::findOrFail($packageId);

        if ($package->image_path) {
            Storage::disk('public')->delete($package->image_path);
        }

        $package->delete();
    }

    public function render(): View
    {
        return view('livewire.packages.index', [
            'packages' => Package::query()
                ->withCount('items')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortField ?: 'name', $this->sortDirection)
                ->paginate(15),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
