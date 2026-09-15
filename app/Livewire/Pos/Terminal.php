<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Terminal extends Component
{
    /** @var array<int, array{product_id: int, name: string, price: int, cost_price: int, qty: int, max_qty: int}> */
    public array $cart = [];

    public string $search = '';

    public string $discount = '0';

    public string $paymentMethod = 'cash';

    public string $paidAmount = '';

    public ?int $lastTransactionId = null;

    public function addToCart(int $productId): void
    {
        $product = Product::query()->where('is_active', true)->findOrFail($productId);

        if ($product->stock_qty < 1) {
            $this->addError('cart', "Stok {$product->name} habis.");

            return;
        }

        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['qty'] < $product->stock_qty) {
                $this->cart[$productId]['qty']++;
            }
        } else {
            $this->cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'qty' => 1,
                'max_qty' => $product->stock_qty,
            ];
        }
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

    public function newTransaction(): void
    {
        $this->reset(['cart', 'discount', 'paidAmount', 'lastTransactionId']);
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
                'transaction_no' => 'TRX-'.now()->format('Ymd-His').'-'.random_int(100, 999),
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
                    'subtotal' => $item['price'] * $item['qty'],
                ]);

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

            return $transaction;
        });

        $this->reset(['cart', 'discount', 'paidAmount']);
        $this->discount = '0';
        $this->lastTransactionId = $transaction->id;
    }

    public function render(): View
    {
        return view('livewire.pos.terminal', [
            'products' => Product::query()
                ->where('is_active', true)
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->limit(40)
                ->get(),
            'lastTransaction' => $this->lastTransactionId
                ? Transaction::with('items')->find($this->lastTransactionId)
                : null,
        ]);
    }
}
