<?php

namespace App\Livewire\Pos;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Terminal extends Component
{
    /** @var array<int, array{product_id: int, name: string, price: int, cost_price: int, qty: int, max_qty: int, note: string, unlimited: bool}> */
    public array $cart = [];

    public string $search = '';

    /** @var array<int, int> */
    public array $activeTags = [];

    public ?int $activeCategoryId = null;

    public string $barcodeInput = '';

    public string $customerName = '';

    public ?int $selectedCustomerId = null;

    public string $orderNote = '';

    public string $orderCodeInput = '';

    public ?int $claimedSelfOrderId = null;

    public string $discount = '0';

    public string $paymentMethod = 'cash';

    public string $paidAmount = '';

    public ?int $lastTransactionId = null;

    public ?int $viewingProductId = null;

    public int $modalQty = 1;

    public function addToCart(int $productId): void
    {
        $product = Product::query()->where('is_active', true)->findOrFail($productId);

        if (! $product->isAvailable()) {
            $this->addError('cart', "Stok {$product->name} habis.");

            return;
        }

        $this->addQtyToCart($product, 1);
    }

    /**
     * Open the mini detail modal for a product so the cashier can review its
     * description and confirm a quantity before it's added to the cart,
     * rather than a bare tap silently adding one unit.
     */
    public function openProductModal(int $productId): void
    {
        $product = Product::query()->where('is_active', true)->findOrFail($productId);

        if (! $product->isAvailable()) {
            $this->addError('cart', "Stok {$product->name} habis.");

            return;
        }

        $this->viewingProductId = $productId;
        $this->modalQty = 1;
    }

    public function closeProductModal(): void
    {
        $this->viewingProductId = null;
        $this->modalQty = 1;
    }

    public function incrementModalQty(): void
    {
        $product = $this->viewingProduct;
        $maxQty = $product && $product->is_unlimited_stock ? PHP_INT_MAX : $product?->stock_qty ?? 1;

        if ($this->modalQty < $maxQty) {
            $this->modalQty++;
        }
    }

    public function decrementModalQty(): void
    {
        if ($this->modalQty > 1) {
            $this->modalQty--;
        }
    }

    public function getViewingProductProperty(): ?Product
    {
        return $this->viewingProductId ? Product::find($this->viewingProductId) : null;
    }

    public function confirmAddToCart(): void
    {
        $product = $this->viewingProduct;

        if (! $product || ! $product->isAvailable()) {
            $this->closeProductModal();

            return;
        }

        $this->addQtyToCart($product, $this->modalQty);

        $this->dispatch('product-added', message: "{$product->name} ditambahkan ke keranjang.");
        $this->closeProductModal();
    }

    private function addQtyToCart(Product $product, int $qty): void
    {
        $maxQty = $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty;
        $qty = min($qty, $maxQty);

        if (isset($this->cart[$product->id])) {
            $this->cart[$product->id]['qty'] = min($this->cart[$product->id]['qty'] + $qty, $maxQty);
        } else {
            $this->cart[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'qty' => $qty,
                'max_qty' => $maxQty,
                'note' => '',
                'unlimited' => $product->is_unlimited_stock,
            ];
        }
    }

    /**
     * Look a product up by its scanned barcode (or typed SKU) and add it to
     * the cart directly - a barcode reader just types the code then Enter.
     */
    public function scanBarcode(): void
    {
        $code = trim($this->barcodeInput);
        $this->barcodeInput = '';

        if ($code === '') {
            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        if (! $product) {
            $this->addError('barcodeInput', "Produk dengan kode \"{$code}\" tidak ditemukan.");

            return;
        }

        $this->addToCart($product->id);
    }

    public function incrementQty(int $productId): void
    {
        if (isset($this->cart[$productId]) && $this->cart[$productId]['qty'] < $this->cart[$productId]['max_qty']) {
            $this->cart[$productId]['qty']++;
        }
    }

    public function decrementQty(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['qty']--;

        if ($this->cart[$productId]['qty'] <= 0) {
            unset($this->cart[$productId]);
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
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

    /**
     * Suggested existing members matching what's typed in the customer name
     * field, so the cashier can pick a registered customer instead of
     * retyping their details. Hidden again once one is actually selected.
     *
     * @return Collection<int, Customer>
     */
    public function getCustomerMatchesProperty(): Collection
    {
        if ($this->selectedCustomerId || trim($this->customerName) === '') {
            return collect();
        }

        return Customer::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$this->customerName}%")
                ->orWhere('phone', 'like', "%{$this->customerName}%")
            )
            ->orderBy('name')
            ->limit(5)
            ->get();
    }

    /**
     * If the name field is edited away from the selected member's name, treat
     * it as free text again so suggestions reappear.
     */
    public function updatedCustomerName(): void
    {
        if (! $this->selectedCustomerId) {
            return;
        }

        $customer = Customer::find($this->selectedCustomerId);

        if (! $customer || $customer->name !== $this->customerName) {
            $this->selectedCustomerId = null;
        }
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);

        $this->selectedCustomerId = $customer->id;
        $this->customerName = $customer->name;
    }

    public function clearSelectedCustomer(): void
    {
        $this->selectedCustomerId = null;
        $this->customerName = '';
    }

    /**
     * Pull a customer's self-order into the cart by its short code, so the
     * cashier only needs to confirm payment. The BelongsToStore scope on
     * SelfOrder/Product already keeps this to the cashier's own store.
     */
    public function claimCode(): void
    {
        $code = strtoupper(trim($this->orderCodeInput));

        if ($code === '') {
            return;
        }

        $selfOrder = SelfOrder::with('items')->where('code', $code)->first();

        if (! $selfOrder) {
            $this->addError('orderCodeInput', 'Kode tidak ditemukan.');

            return;
        }

        if (! $selfOrder->isPending()) {
            $this->addError('orderCodeInput', $selfOrder->isExpired() ? 'Kode sudah kedaluwarsa.' : 'Kode sudah pernah dipakai.');

            return;
        }

        $skipped = [];

        foreach ($selfOrder->items as $item) {
            $product = $item->product_id ? Product::query()->where('is_active', true)->find($item->product_id) : null;

            if (! $product || ! $product->isAvailable()) {
                $skipped[] = $item->product_name;

                continue;
            }

            $maxQty = $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty;
            $qty = min($item->qty, $maxQty);

            if (isset($this->cart[$product->id])) {
                $this->cart[$product->id]['qty'] = min($this->cart[$product->id]['qty'] + $qty, $maxQty);
            } else {
                $this->cart[$product->id] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'cost_price' => $product->cost_price,
                    'qty' => $qty,
                    'max_qty' => $maxQty,
                    'note' => (string) $item->note,
                    'unlimited' => $product->is_unlimited_stock,
                ];
            }
        }

        $this->customerName = (string) $selfOrder->customer_name;
        $this->selectedCustomerId = null;
        $this->orderNote = (string) $selfOrder->note;
        $this->claimedSelfOrderId = $selfOrder->id;
        $this->orderCodeInput = '';

        $selfOrder->update([
            'status' => 'claimed',
            'claimed_by' => Auth::id(),
            'claimed_at' => now(),
        ]);

        if ($skipped) {
            $this->addError('orderCodeInput', 'Menu berikut sudah tidak tersedia dan dilewati: '.implode(', ', $skipped));
        }
    }

    /**
     * Print a non-final bill from the current cart, so the cashier can show
     * the customer the total before payment is actually taken. Nothing is
     * saved to the database - the real Transaction is only created once
     * checkout() runs.
     */
    public function printBill(): void
    {
        if (empty($this->cart)) {
            $this->addError('cart', 'Keranjang masih kosong.');

            return;
        }

        session(['pos_bill' => [
            'store_id' => Auth::user()->store_id,
            'customer_name' => $this->customerName !== '' ? $this->customerName : null,
            'note' => $this->orderNote !== '' ? $this->orderNote : null,
            'items' => array_values($this->cart),
            'subtotal' => $this->subtotal,
            'discount' => (int) $this->discount,
            'tax_amount' => $this->taxAmount,
            'service_charge_amount' => $this->serviceChargeAmount,
            'total' => $this->total,
        ]]);

        $this->dispatch('bill-ready');
    }

    public function newTransaction(): void
    {
        $this->reset(['cart', 'discount', 'paidAmount', 'lastTransactionId', 'customerName', 'selectedCustomerId', 'orderNote', 'claimedSelfOrderId']);
        $this->discount = '0';
        $this->paymentMethod = 'cash';
    }

    public function getStoreProperty(): Store
    {
        return Auth::user()->store;
    }

    public function getSubtotalProperty(): int
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['qty']);
    }

    public function getDiscountedSubtotalProperty(): int
    {
        return max(0, $this->subtotal - (int) $this->discount);
    }

    public function getTaxAmountProperty(): int
    {
        return $this->store->taxAmountFor($this->discountedSubtotal);
    }

    public function getServiceChargeAmountProperty(): int
    {
        return $this->store->serviceChargeAmountFor($this->discountedSubtotal);
    }

    public function getTotalProperty(): int
    {
        return $this->discountedSubtotal + $this->taxAmount + $this->serviceChargeAmount;
    }

    public function getChangeProperty(): int
    {
        return max(0, (int) ($this->paidAmount !== '' ? $this->paidAmount : 0) - $this->total);
    }

    public function checkout(): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:cash,qris,kartu'],
            'discount' => ['required', 'integer', 'min:0'],
            'customerName' => ['nullable', 'string', 'max:255'],
            'orderNote' => ['nullable', 'string', 'max:255'],
            'cart.*.price' => ['required', 'integer', 'min:0'],
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Keranjang masih kosong.');

            return;
        }

        // The price input is only rendered when the store allows editing it,
        // but a forged request could still set cart.*.price directly - so
        // reassert the real product price server-side whenever it's off.
        if (! $this->store->allow_price_edit) {
            $realPrices = Product::query()->whereIn('id', array_column($this->cart, 'product_id'))->pluck('price', 'id');

            foreach ($this->cart as $productId => $item) {
                if (isset($realPrices[$productId])) {
                    $this->cart[$productId]['price'] = $realPrices[$productId];
                }
            }
        }

        if ($this->discount > $this->subtotal) {
            $this->addError('discount', 'Diskon tidak boleh melebihi subtotal.');

            return;
        }

        $paidAmount = $this->paymentMethod === 'cash' ? (int) $this->paidAmount : $this->total;

        if ($this->paymentMethod === 'cash' && $paidAmount < $this->total) {
            $this->addError('paidAmount', 'Jumlah bayar kurang dari total.');

            return;
        }

        $transaction = DB::transaction(function () use ($paidAmount) {
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'self_order_id' => $this->claimedSelfOrderId,
                'customer_id' => $this->selectedCustomerId,
                'transaction_no' => 'TRX-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'customer_name' => $this->customerName !== '' ? $this->customerName : null,
                'note' => $this->orderNote !== '' ? $this->orderNote : null,
                'subtotal' => $this->subtotal,
                'discount' => (int) $this->discount,
                'tax_amount' => $this->taxAmount,
                'service_charge_amount' => $this->serviceChargeAmount,
                'total' => $this->total,
                'payment_method' => $this->paymentMethod,
                'paid_amount' => $paidAmount,
                'change_amount' => max(0, $paidAmount - $this->total),
                'status' => 'completed',
            ]);

            foreach ($this->cart as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'qty' => $item['qty'],
                    'note' => $item['note'] !== '' ? $item['note'] : null,
                    'subtotal' => $item['price'] * $item['qty'],
                ]);

                $product = Product::with('ingredients')->findOrFail($item['product_id']);

                if (empty($item['unlimited'])) {
                    $product->decrement('stock_qty', $item['qty']);

                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'user_id' => Auth::id(),
                        'type' => 'out',
                        'qty' => -$item['qty'],
                        'note' => 'Penjualan '.$transaction->transaction_no,
                    ]);
                }

                foreach ($product->ingredients as $ingredient) {
                    $qtyUsed = $ingredient->pivot->qty_used * $item['qty'];

                    $ingredient->decrement('stock_qty', $qtyUsed);

                    InventoryMovement::create([
                        'inventory_item_id' => $ingredient->id,
                        'user_id' => Auth::id(),
                        'type' => 'out',
                        'qty' => -$qtyUsed,
                        'note' => 'Penjualan '.$transaction->transaction_no,
                    ]);
                }
            }

            if ($this->claimedSelfOrderId) {
                SelfOrder::where('id', $this->claimedSelfOrderId)->update(['status' => 'completed']);
            }

            return $transaction;
        });

        $this->reset(['cart', 'discount', 'paidAmount', 'customerName', 'selectedCustomerId', 'orderNote', 'claimedSelfOrderId']);
        $this->discount = '0';
        $this->lastTransactionId = $transaction->id;
    }

    public function render(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->when($this->activeTags, fn ($query) => $query->whereHas(
                'tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $this->activeTags)
            ))
            ->when($this->activeCategoryId, fn ($query) => $query->where('category_id', $this->activeCategoryId))
            ->with('category')
            ->orderBy('name')
            ->limit(40)
            ->get();

        return view('livewire.pos.terminal', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'productGroups' => $this->activeCategoryId ? null : $products->groupBy(fn ($product) => $product->category_id ?? 0),
            'tags' => Tag::query()->orderBy('name')->get(),
            'lastTransaction' => $this->lastTransactionId
                ? Transaction::with('items')->find($this->lastTransactionId)
                : null,
        ]);
    }
}
