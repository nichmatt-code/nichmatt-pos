<?php

namespace App\Livewire\Pos;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\LossRecord;
use App\Models\LossRecordItem;
use App\Models\Product;
use App\Models\QrisPayment;
use App\Models\SelfOrder;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Contracts\MidtransQrisGatewayContract;
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

    public bool $showLossModal = false;

    public string $lossSearch = '';

    /** @var array<int, array{product_id: int, name: string, cost_price: int, qty: int}> */
    public array $lossItems = [];

    public string $lossReason = '';

    public ?int $lastLossRecordId = null;

    public ?int $qrisPaymentId = null;

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
    }

    public function closeProductModal(): void
    {
        $this->viewingProductId = null;
    }

    public function getViewingProductProperty(): ?Product
    {
        return $this->viewingProductId ? Product::with('store')->find($this->viewingProductId) : null;
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

        $this->addQtyToCart($product, max(1, $qty), trim($note));

        $this->dispatch('product-added', message: "{$product->name} ditambahkan ke keranjang.");
        $this->closeProductModal();
    }

    private function addQtyToCart(Product $product, int $qty, string $note = ''): void
    {
        $maxQty = $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty;
        $qty = min($qty, $maxQty);

        if (isset($this->cart[$product->id])) {
            $this->cart[$product->id]['qty'] = min($this->cart[$product->id]['qty'] + $qty, $maxQty);

            if ($note !== '') {
                $this->cart[$product->id]['note'] = $note;
            }
        } else {
            $this->cart[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'qty' => $qty,
                'max_qty' => $maxQty,
                'note' => $note,
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

    /**
     * Service charge is applied first (on the discounted subtotal), then tax
     * is calculated on top of subtotal + service charge - the usual F&B
     * order, since tax authorities generally treat the service charge as
     * part of the taxable amount.
     */
    public function getServiceChargeAmountProperty(): int
    {
        return $this->store->serviceChargeAmountFor($this->discountedSubtotal);
    }

    public function getTaxAmountProperty(): int
    {
        return $this->store->taxAmountFor($this->discountedSubtotal + $this->serviceChargeAmount);
    }

    public function getTotalProperty(): int
    {
        return $this->discountedSubtotal + $this->serviceChargeAmount + $this->taxAmount;
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

        $transaction = $this->materializeTransaction($this->cart, [
            'user_id' => Auth::id(),
            'customer_id' => $this->selectedCustomerId,
            'customer_name' => $this->customerName,
            'order_note' => $this->orderNote,
            'subtotal' => $this->subtotal,
            'discount' => (int) $this->discount,
            'tax_amount' => $this->taxAmount,
            'service_charge_amount' => $this->serviceChargeAmount,
            'total' => $this->total,
            'payment_method' => $this->paymentMethod,
            'paid_amount' => $paidAmount,
            'change_amount' => max(0, $paidAmount - $this->total),
            'claimed_self_order_id' => $this->claimedSelfOrderId,
        ]);

        $this->reset(['cart', 'discount', 'paidAmount', 'customerName', 'selectedCustomerId', 'orderNote', 'claimedSelfOrderId']);
        $this->discount = '0';
        $this->lastTransactionId = $transaction->id;
    }

    /**
     * Create the Transaction/TransactionItem rows and deduct product +
     * ingredient stock for a cart - the one place this happens, shared by
     * the normal (manual) checkout and by a settled online QRIS payment.
     *
     * @param  array<int, array{product_id: int, name: string, price: int, cost_price: int, qty: int, note: string, unlimited: bool}>  $cart
     * @param  array{user_id: ?int, customer_id: ?int, customer_name: string, order_note: string, subtotal: int, discount: int, tax_amount: int, service_charge_amount: int, total: int, payment_method: string, paid_amount: int, change_amount: int, claimed_self_order_id: ?int}  $meta
     */
    private function materializeTransaction(array $cart, array $meta): Transaction
    {
        return DB::transaction(function () use ($cart, $meta) {
            $transaction = Transaction::create([
                'user_id' => $meta['user_id'],
                'self_order_id' => $meta['claimed_self_order_id'],
                'customer_id' => $meta['customer_id'],
                'transaction_no' => 'TRX-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'customer_name' => $meta['customer_name'] !== '' ? $meta['customer_name'] : null,
                'note' => $meta['order_note'] !== '' ? $meta['order_note'] : null,
                'subtotal' => $meta['subtotal'],
                'discount' => $meta['discount'],
                'tax_amount' => $meta['tax_amount'],
                'service_charge_amount' => $meta['service_charge_amount'],
                'total' => $meta['total'],
                'payment_method' => $meta['payment_method'],
                'paid_amount' => $meta['paid_amount'],
                'change_amount' => $meta['change_amount'],
                'status' => 'completed',
            ]);

            foreach ($cart as $item) {
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
                        'user_id' => $meta['user_id'],
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
                        'user_id' => $meta['user_id'],
                        'type' => 'out',
                        'qty' => -$qtyUsed,
                        'note' => 'Penjualan '.$transaction->transaction_no,
                    ]);
                }
            }

            if ($meta['claimed_self_order_id']) {
                SelfOrder::where('id', $meta['claimed_self_order_id'])->update(['status' => 'completed']);
            }

            return $transaction;
        });
    }

    /**
     * Start an online QRIS payment against the store's own Midtrans
     * merchant account: a dynamic QR sized to the exact total is generated,
     * and nothing is booked as a sale yet - the cart is only turned into a
     * real Transaction once the payment actually settles.
     */
    public function payWithQrisOnline(MidtransQrisGatewayContract $gateway): void
    {
        if (! $this->store->canAcceptOnlinePayments()) {
            return;
        }

        $this->validate([
            'discount' => ['required', 'integer', 'min:0'],
            'customerName' => ['nullable', 'string', 'max:255'],
            'orderNote' => ['nullable', 'string', 'max:255'],
            'cart.*.price' => ['required', 'integer', 'min:0'],
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Keranjang masih kosong.');

            return;
        }

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

        $orderId = 'QRIS-'.$this->store->id.'-'.now()->format('YmdHis').'-'.random_int(100, 999);

        $qrisPayment = QrisPayment::create([
            'user_id' => Auth::id(),
            'order_id' => $orderId,
            'amount' => $this->total,
            'status' => 'pending',
            'cart_snapshot' => [
                'cart' => $this->cart,
                'customer_id' => $this->selectedCustomerId,
                'customer_name' => $this->customerName,
                'order_note' => $this->orderNote,
                'subtotal' => $this->subtotal,
                'discount' => (int) $this->discount,
                'tax_amount' => $this->taxAmount,
                'service_charge_amount' => $this->serviceChargeAmount,
                'total' => $this->total,
                'claimed_self_order_id' => $this->claimedSelfOrderId,
            ],
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            $response = $gateway->charge($this->store, $orderId, $this->total);
        } catch (\Throwable $e) {
            $qrisPayment->update(['status' => 'failed']);
            $this->addError('qrisPayment', 'Gagal membuat pembayaran QRIS: '.$e->getMessage());

            return;
        }

        $qrUrl = collect($response->actions ?? [])
            ->first(fn ($action) => ($action->name ?? null) === 'generate-qr-code');

        $qrisPayment->update(['qr_url' => $qrUrl?->url]);
        $this->qrisPaymentId = $qrisPayment->id;
    }

    /**
     * Polled every few seconds while the QR modal is open - checks the
     * store's own Midtrans account directly for the charge's status rather
     * than waiting on a webhook, since a store owner forgetting to point
     * their Midtrans Notification URL at this app must not leave the
     * cashier stuck waiting forever.
     */
    public function checkQrisPaymentStatus(MidtransQrisGatewayContract $gateway): void
    {
        if (! $this->qrisPaymentId) {
            return;
        }

        $qrisPayment = QrisPayment::find($this->qrisPaymentId);

        if (! $qrisPayment) {
            $this->qrisPaymentId = null;

            return;
        }

        if (! $qrisPayment->isPending()) {
            return;
        }

        if ($qrisPayment->isExpired()) {
            $qrisPayment->update(['status' => 'expired']);

            return;
        }

        try {
            $status = $gateway->status($this->store, $qrisPayment->order_id);
        } catch (\Throwable $e) {
            return;
        }

        $transactionStatus = $status->transaction_status ?? null;
        $fraudStatus = $status->fraud_status ?? null;

        if (in_array($transactionStatus, ['capture', 'settlement'], true) && $fraudStatus !== 'deny') {
            $this->completeQrisPayment($qrisPayment);
        } elseif ($transactionStatus === 'expire') {
            $qrisPayment->update(['status' => 'expired']);
        } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
            $qrisPayment->update(['status' => 'cancelled']);
        }
    }

    /**
     * Materialize a settled QRIS payment into a real Transaction. Guarded by
     * transaction_id (not status) so a duplicate settlement check - e.g. two
     * overlapping polls - can never create the sale twice.
     */
    private function completeQrisPayment(QrisPayment $qrisPayment): void
    {
        if ($qrisPayment->transaction_id) {
            return;
        }

        $snapshot = $qrisPayment->cart_snapshot;

        $transaction = $this->materializeTransaction($snapshot['cart'], [
            'user_id' => $qrisPayment->user_id,
            'customer_id' => $snapshot['customer_id'],
            'customer_name' => $snapshot['customer_name'],
            'order_note' => $snapshot['order_note'],
            'subtotal' => $snapshot['subtotal'],
            'discount' => $snapshot['discount'],
            'tax_amount' => $snapshot['tax_amount'],
            'service_charge_amount' => $snapshot['service_charge_amount'],
            'total' => $snapshot['total'],
            'payment_method' => 'qris',
            'paid_amount' => $snapshot['total'],
            'change_amount' => 0,
            'claimed_self_order_id' => $snapshot['claimed_self_order_id'],
        ]);

        $qrisPayment->update(['status' => 'settled', 'paid_at' => now(), 'transaction_id' => $transaction->id]);

        $this->reset(['cart', 'discount', 'paidAmount', 'customerName', 'selectedCustomerId', 'orderNote', 'claimedSelfOrderId']);
        $this->discount = '0';
        $this->lastTransactionId = $transaction->id;
        $this->qrisPaymentId = null;
    }

    /**
     * Cancel a still-pending QR (best-effort on Midtrans's side too) so the
     * cashier can back out and pick a different payment method.
     */
    public function cancelQrisPayment(MidtransQrisGatewayContract $gateway): void
    {
        $qrisPayment = $this->qrisPaymentId ? QrisPayment::find($this->qrisPaymentId) : null;

        if ($qrisPayment && $qrisPayment->isPending()) {
            try {
                $gateway->cancel($this->store, $qrisPayment->order_id);
            } catch (\Throwable $e) {
                // Best-effort - the charge may already be expired/settled on
                // Midtrans's side. The local status below is authoritative.
            }

            $qrisPayment->update(['status' => 'cancelled']);
        }

        $this->qrisPaymentId = null;
    }

    public function getQrisPaymentProperty(): ?QrisPayment
    {
        return $this->qrisPaymentId ? QrisPayment::find($this->qrisPaymentId) : null;
    }

    /**
     * Open the "record a loss" panel, separate from the normal checkout flow
     * so damaged/dropped items can be written off without being booked as a
     * sale (no revenue, no customer payment).
     */
    public function openLossModal(): void
    {
        $this->lossItems = [];
        $this->lossSearch = '';
        $this->lossReason = '';
        $this->showLossModal = true;
    }

    public function closeLossModal(): void
    {
        $this->showLossModal = false;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getLossSearchResultsProperty(): Collection
    {
        if (trim($this->lossSearch) === '') {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$this->lossSearch}%")
            ->limit(5)
            ->get();
    }

    public function addLossItem(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if (isset($this->lossItems[$productId])) {
            $this->lossItems[$productId]['qty']++;
        } else {
            $this->lossItems[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'cost_price' => $product->cost_price,
                'qty' => 1,
            ];
        }

        $this->lossSearch = '';
    }

    public function incrementLossQty(int $productId): void
    {
        if (isset($this->lossItems[$productId])) {
            $this->lossItems[$productId]['qty']++;
        }
    }

    public function decrementLossQty(int $productId): void
    {
        if (! isset($this->lossItems[$productId])) {
            return;
        }

        $this->lossItems[$productId]['qty']--;

        if ($this->lossItems[$productId]['qty'] <= 0) {
            unset($this->lossItems[$productId]);
        }
    }

    public function removeLossItem(int $productId): void
    {
        unset($this->lossItems[$productId]);
    }

    public function getLossTotalCostProperty(): int
    {
        return collect($this->lossItems)->sum(fn (array $item) => $item['cost_price'] * $item['qty']);
    }

    /**
     * Record a loss (e.g. damaged goods, dropped food): deducts stock and
     * ingredient inventory exactly like a sale would, but creates a
     * LossRecord instead of a Transaction - no revenue, no customer payment,
     * and it's kept out of sales reports entirely.
     */
    public function submitLoss(): void
    {
        $this->validate([
            'lossReason' => ['required', 'string', 'max:255'],
        ], attributes: ['lossReason' => 'alasan kerugian']);

        if (empty($this->lossItems)) {
            $this->addError('lossItems', 'Pilih minimal 1 produk.');

            return;
        }

        $lossRecord = DB::transaction(function () {
            $lossRecord = LossRecord::create([
                'user_id' => Auth::id(),
                'loss_no' => 'LOSS-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'reason' => $this->lossReason,
                'total_cost_value' => $this->lossTotalCost,
            ]);

            foreach ($this->lossItems as $item) {
                LossRecordItem::create([
                    'loss_record_id' => $lossRecord->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'cost_price' => $item['cost_price'],
                    'subtotal_cost' => $item['cost_price'] * $item['qty'],
                ]);

                $product = Product::with('ingredients')->find($item['product_id']);

                if (! $product) {
                    continue;
                }

                if (! $product->is_unlimited_stock) {
                    $product->decrement('stock_qty', $item['qty']);

                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'user_id' => Auth::id(),
                        'type' => 'out',
                        'qty' => -$item['qty'],
                        'note' => 'Kerugian '.$lossRecord->loss_no,
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
                        'note' => 'Kerugian '.$lossRecord->loss_no,
                    ]);
                }
            }

            return $lossRecord;
        });

        $this->lastLossRecordId = $lossRecord->id;
        $this->showLossModal = false;
        $this->dispatch('loss-ready', lossId: $lossRecord->id);
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
            ->with(['category', 'store'])
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
