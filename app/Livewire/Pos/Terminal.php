<?php

namespace App\Livewire\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\StockMovement;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Contracts\View\View;
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

    public string $orderNote = '';

    public string $orderCodeInput = '';

    public ?int $claimedSelfOrderId = null;

    public string $discount = '0';

    public string $paymentMethod = 'cash';

    public string $paidAmount = '';

    public ?int $lastTransactionId = null;

    public function addToCart(int $productId): void
    {
        $product = Product::query()->where('is_active', true)->findOrFail($productId);

        if (! $product->isAvailable()) {
            $this->addError('cart', "Stok {$product->name} habis.");

            return;
        }

        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['qty'] < $this->cart[$productId]['max_qty']) {
                $this->cart[$productId]['qty']++;
            }
        } else {
            $this->cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'qty' => 1,
                'max_qty' => $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty,
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

    public function newTransaction(): void
    {
        $this->reset(['cart', 'discount', 'paidAmount', 'lastTransactionId', 'customerName', 'orderNote', 'claimedSelfOrderId']);
        $this->discount = '0';
        $this->paymentMethod = 'cash';
    }

    public function getSubtotalProperty(): int
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['qty']);
    }

    public function getTotalProperty(): int
    {
        return max(0, $this->subtotal - (int) $this->discount);
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
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Keranjang masih kosong.');

            return;
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
                'transaction_no' => 'TRX-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'customer_name' => $this->customerName !== '' ? $this->customerName : null,
                'note' => $this->orderNote !== '' ? $this->orderNote : null,
                'subtotal' => $this->subtotal,
                'discount' => (int) $this->discount,
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

                if (empty($item['unlimited'])) {
                    $product = Product::findOrFail($item['product_id']);
                    $product->decrement('stock_qty', $item['qty']);

                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'user_id' => Auth::id(),
                        'type' => 'out',
                        'qty' => -$item['qty'],
                        'note' => 'Penjualan '.$transaction->transaction_no,
                    ]);
                }
            }

            if ($this->claimedSelfOrderId) {
                SelfOrder::where('id', $this->claimedSelfOrderId)->update(['status' => 'completed']);
            }

            return $transaction;
        });

        $this->reset(['cart', 'discount', 'paidAmount', 'customerName', 'orderNote', 'claimedSelfOrderId']);
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
