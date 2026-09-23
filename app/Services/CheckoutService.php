<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Package;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

/**
 * Creates the Transaction/TransactionItem rows and deducts product +
 * ingredient stock for a cart - the one place this happens, shared by the
 * Kasir (Livewire) checkout, a settled online QRIS payment, and the mobile
 * API checkout.
 */
class CheckoutService
{
    /**
     * @param  array<int, array{product_id: ?int, package_id: ?int, name: string, price: int, cost_price: int, qty: int, note: string, unlimited: bool, type: string}>  $cart
     * @param  array{user_id: ?int, customer_id: ?int, customer_name: string, customer_phone?: ?string, order_note: string, subtotal: int, discount: int, coupon_id: ?int, coupon_discount_amount: int, tax_amount: int, service_charge_amount: int, total: int, payment_method: string, paid_amount: int, change_amount: int, claimed_self_order_id: ?int}  $meta
     */
    public function materialize(array $cart, array $meta): Transaction
    {
        return DB::transaction(function () use ($cart, $meta) {
            $transaction = Transaction::create([
                'user_id' => $meta['user_id'],
                'self_order_id' => $meta['claimed_self_order_id'],
                'customer_id' => $meta['customer_id'],
                'transaction_no' => 'TRX-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'customer_name' => $meta['customer_name'] !== '' ? $meta['customer_name'] : null,
                'customer_phone' => ($meta['customer_phone'] ?? '') !== '' ? $meta['customer_phone'] : null,
                'note' => $meta['order_note'] !== '' ? $meta['order_note'] : null,
                'subtotal' => $meta['subtotal'],
                'discount' => $meta['discount'],
                'coupon_id' => $meta['coupon_id'] ?? null,
                'coupon_discount_amount' => $meta['coupon_discount_amount'] ?? 0,
                'tax_amount' => $meta['tax_amount'],
                'service_charge_amount' => $meta['service_charge_amount'],
                'total' => $meta['total'],
                'payment_method' => $meta['payment_method'],
                'paid_amount' => $meta['paid_amount'],
                'change_amount' => $meta['change_amount'],
                'status' => 'completed',
            ]);

            foreach ($cart as $item) {
                $isPackage = ($item['type'] ?? 'product') === 'package';

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $isPackage ? null : $item['product_id'],
                    'package_id' => $isPackage ? $item['package_id'] : null,
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'qty' => $item['qty'],
                    'note' => $item['note'] !== '' ? $item['note'] : null,
                    'subtotal' => $item['price'] * $item['qty'],
                ]);

                if ($isPackage) {
                    $this->deductPackageStock($item['package_id'], $item['qty'], $meta['user_id'], $transaction->transaction_no);

                    continue;
                }

                $this->deductProductStock($item['product_id'], $item['qty'], empty($item['unlimited']), $meta['user_id'], $transaction->transaction_no);
            }

            if ($meta['claimed_self_order_id']) {
                SelfOrder::where('id', $meta['claimed_self_order_id'])->update(['status' => 'completed']);
            }

            return $transaction;
        });
    }

    /**
     * Deduct one product's own stock (unless unlimited) and its ingredient
     * inventory - the effect of selling `$qty` of it, whether that came
     * from a direct cart line or as a component inside a sold package.
     */
    private function deductProductStock(int $productId, int $qty, bool $deductOwnStock, ?int $userId, string $note): void
    {
        $product = Product::with('ingredients')->findOrFail($productId);

        if ($deductOwnStock && ! $product->is_unlimited_stock) {
            $product->decrement('stock_qty', $qty);

            StockMovement::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'type' => 'out',
                'qty' => -$qty,
                'note' => 'Penjualan '.$note,
            ]);
        }

        foreach ($product->ingredients as $ingredient) {
            $qtyUsed = $ingredient->pivot->qty_used * $qty;

            $ingredient->decrement('stock_qty', $qtyUsed);

            InventoryMovement::create([
                'inventory_item_id' => $ingredient->id,
                'user_id' => $userId,
                'type' => 'out',
                'qty' => -$qtyUsed,
                'note' => 'Penjualan '.$note,
            ]);
        }
    }

    /**
     * Selling `$packageQty` of a package deducts every one of its component
     * products (and their own ingredients) by qty-per-package * packages
     * sold - the package itself has no stock of its own.
     */
    private function deductPackageStock(int $packageId, int $packageQty, ?int $userId, string $note): void
    {
        $package = Package::with('items')->findOrFail($packageId);

        foreach ($package->items as $packageItem) {
            $this->deductProductStock(
                $packageItem->product_id,
                $packageItem->qty * $packageQty,
                true,
                $userId,
                'Paket '.$package->name.' - '.$note,
            );
        }
    }
}
