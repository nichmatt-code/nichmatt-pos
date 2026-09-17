<?php

namespace App\Livewire\SelfOrder;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\SelfOrderItem;
use App\Models\Store;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Picqer\Barcode\BarcodeGeneratorSVG;

class Menu extends Component
{
    public Store $store;

    /**
     * Keyed by product_id for a normal item, or "pkg_{package_id}" for a
     * package.
     *
     * @var array<int|string, array{product_id: ?int, package_id: ?int, name: string, price: int, qty: int, max_qty: int, note: string, type: string}>
     */
    public array $cart = [];

    public string $search = '';

    /** @var array<int, int> */
    public array $activeTags = [];

    public ?int $activeCategoryId = null;

    public string $customerName = '';

    public string $orderNote = '';

    public ?string $confirmedCode = null;

    public ?int $confirmedTotal = null;

    public ?int $viewingProductId = null;

    public function mount(Store $store): void
    {
        $this->store = $store;
    }

    /**
     * A Code-128 barcode of the confirmed order code, scannable by the
     * cashier's barcode gun at checkout instead of typing it in.
     */
    public function getConfirmedCodeBarcodeProperty(): ?string
    {
        if (! $this->confirmedCode) {
            return null;
        }

        return (new BarcodeGeneratorSVG)->getBarcode($this->confirmedCode, BarcodeGeneratorSVG::TYPE_CODE_128, 2, 60);
    }

    /**
     * Open the mini detail modal for a menu item so the customer can review
     * its description and confirm a quantity before it's added to the cart.
     */
    public function openProductModal(int $productId): void
    {
        $product = $this->products()->where('products.id', $productId)->first();

        if (! $product || ! $product->isAvailable()) {
            $this->addError('cart', 'Menu ini sedang tidak tersedia.');

            return;
        }

        $this->viewingProductId = $productId;
    }

    public function closeProductModal(): void
    {
        $this->viewingProductId = null;
    }

    public function getViewingProductProperty(): ?Product
    {
        return $this->viewingProductId
            ? $this->products()->where('products.id', $this->viewingProductId)->first()
            : null;
    }

    /**
     * The quantity stepper in the product modal is handled entirely
     * client-side (Alpine) so tapping +/- doesn't round-trip to the server;
     * only the final confirmed quantity (and an optional note) is sent
     * here, once.
     */
    public function confirmAddToCart(int $qty = 1, string $note = ''): void
    {
        $product = $this->viewingProduct;

        if (! $product || ! $product->isAvailable()) {
            $this->closeProductModal();

            return;
        }

        $maxQty = $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty;
        $qty = min(max(1, $qty), $maxQty);
        $note = trim($note);

        if (isset($this->cart[$product->id])) {
            $this->cart[$product->id]['qty'] = min($this->cart[$product->id]['qty'] + $qty, $maxQty);

            if ($note !== '') {
                $this->cart[$product->id]['note'] = $note;
            }
        } else {
            $this->cart[$product->id] = [
                'product_id' => $product->id,
                'package_id' => null,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $qty,
                'max_qty' => $maxQty,
                'note' => $note,
                'type' => 'product',
            ];
        }

        $this->dispatch('product-added', message: "{$product->name} ditambahkan ke pesanan.");
        $this->closeProductModal();
    }

    /**
     * Add one of a package to the order as a single line - the products it
     * contains aren't shown separately, but their stock is still deducted
     * individually once the cashier completes the sale.
     */
    public function addPackageToCart(int $packageId): void
    {
        $package = Package::query()
            ->where('store_id', $this->store->id)
            ->where('is_active', true)
            ->with('items.product')
            ->findOrFail($packageId);

        $maxQty = $package->maxSellable();

        if ($maxQty < 1) {
            $this->addError('cart', "Paket {$package->name} sedang tidak tersedia.");

            return;
        }

        $key = 'pkg_'.$package->id;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] = min($this->cart[$key]['qty'] + 1, $this->cart[$key]['max_qty']);
        } else {
            $this->cart[$key] = [
                'product_id' => null,
                'package_id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'qty' => 1,
                'max_qty' => $maxQty,
                'note' => '',
                'type' => 'package',
            ];
        }

        $this->dispatch('product-added', message: "{$package->name} ditambahkan ke pesanan.");
    }

    public function incrementQty(int|string $cartKey): void
    {
        if (isset($this->cart[$cartKey]) && $this->cart[$cartKey]['qty'] < $this->cart[$cartKey]['max_qty']) {
            $this->cart[$cartKey]['qty']++;
        }
    }

    public function decrementQty(int|string $cartKey): void
    {
        if (! isset($this->cart[$cartKey])) {
            return;
        }

        $this->cart[$cartKey]['qty']--;

        if ($this->cart[$cartKey]['qty'] <= 0) {
            unset($this->cart[$cartKey]);
        }
    }

    public function removeFromCart(int|string $cartKey): void
    {
        unset($this->cart[$cartKey]);
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->activeTags, true)) {
            $this->activeTags = array_values(array_diff($this->activeTags, [$tagId]));
        } else {
            $this->activeTags[] = $tagId;
        }
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
    }

    public function getSubtotalProperty(): int
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['qty']);
    }

    public function confirmOrder(): void
    {
        $this->validate([
            'customerName' => ['nullable', 'string', 'max:255'],
            'orderNote' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Keranjang masih kosong.');

            return;
        }

        $selfOrder = DB::transaction(function () {
            $selfOrder = SelfOrder::create([
                'store_id' => $this->store->id,
                'code' => SelfOrder::generateUniqueCode(),
                'customer_name' => $this->customerName !== '' ? $this->customerName : null,
                'note' => $this->orderNote !== '' ? $this->orderNote : null,
                'subtotal' => $this->subtotal,
                'total' => $this->subtotal,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(SelfOrder::VALID_MINUTES),
            ]);

            foreach ($this->cart as $item) {
                $isPackage = ($item['type'] ?? 'product') === 'package';

                SelfOrderItem::create([
                    'self_order_id' => $selfOrder->id,
                    'product_id' => $isPackage ? null : $item['product_id'],
                    'package_id' => $isPackage ? $item['package_id'] : null,
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'note' => $item['note'] !== '' ? $item['note'] : null,
                    'subtotal' => $item['price'] * $item['qty'],
                ]);
            }

            return $selfOrder;
        });

        $this->confirmedCode = $selfOrder->code;
        $this->confirmedTotal = $selfOrder->total;
        $this->reset(['cart', 'search', 'activeTags', 'activeCategoryId', 'customerName', 'orderNote']);
    }

    public function newOrder(): void
    {
        $this->reset(['cart', 'search', 'activeTags', 'activeCategoryId', 'customerName', 'orderNote', 'confirmedCode', 'confirmedTotal']);
    }

    private function products()
    {
        return Product::query()
            ->where('store_id', $this->store->id)
            ->where('is_active', true)
            ->with('store');
    }

    public function render(): View
    {
        $products = $this->products()
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->when($this->activeTags, fn ($query) => $query->whereHas(
                'tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $this->activeTags)
            ))
            ->when($this->activeCategoryId, fn ($query) => $query->where('category_id', $this->activeCategoryId))
            ->with('category')
            ->orderBy('name')
            ->limit(40)
            ->get();

        return view('livewire.self-order.menu', [
            'products' => $products,
            'categories' => Category::query()->where('store_id', $this->store->id)->orderBy('name')->get(),
            'productGroups' => $this->activeCategoryId ? null : $products->groupBy(fn ($product) => $product->category_id ?? 0),
            'tags' => Tag::query()->where('store_id', $this->store->id)->orderBy('name')->get(),
            'packages' => Package::query()->where('store_id', $this->store->id)->where('is_active', true)->with('items.product')->orderBy('name')->get(),
        ]);
    }
}
