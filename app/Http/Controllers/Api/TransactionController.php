<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    /**
     * Checkout a cart of plain products (no packages/coupons/self-order yet)
     * into a real Transaction, for the mobile (React Native) cashier app.
     * Prices are always re-derived from the current Product rows server-side
     * - the client only sends product_id + qty, never a price.
     */
    public function store(Request $request, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,kartu'],
            'paid_amount' => ['required_if:payment_method,cash', 'nullable', 'integer', 'min:0'],
        ]);

        $store = Auth::user()->store;

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', collect($data['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $cart = [];

        foreach ($data['items'] as $line) {
            $product = $products->get($line['product_id']);

            if (! $product || ! $product->isAvailable()) {
                throw ValidationException::withMessages([
                    'items' => ["Produk dengan id {$line['product_id']} tidak tersedia."],
                ]);
            }

            $maxQty = $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty;

            if ($line['qty'] > $maxQty) {
                throw ValidationException::withMessages([
                    'items' => ["Stok {$product->name} tidak cukup."],
                ]);
            }

            $cart[$product->id] = [
                'product_id' => $product->id,
                'package_id' => null,
                'name' => $product->name,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'qty' => $line['qty'],
                'note' => $line['note'] ?? '',
                'unlimited' => $product->is_unlimited_stock,
                'type' => 'product',
            ];
        }

        $subtotal = collect($cart)->sum(fn (array $item) => $item['price'] * $item['qty']);
        $discount = $data['discount'] ?? 0;

        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => ['Diskon tidak boleh melebihi subtotal.']]);
        }

        $discountedSubtotal = $subtotal - $discount;
        $serviceChargeAmount = $store->serviceChargeAmountFor($discountedSubtotal);
        $taxAmount = $store->taxAmountFor($discountedSubtotal + $serviceChargeAmount);
        $total = $discountedSubtotal + $serviceChargeAmount + $taxAmount;

        $paidAmount = $data['payment_method'] === 'cash' ? (int) $data['paid_amount'] : $total;

        if ($data['payment_method'] === 'cash' && $paidAmount < $total) {
            throw ValidationException::withMessages(['paid_amount' => ['Jumlah bayar kurang dari total.']]);
        }

        $transaction = $checkoutService->materialize($cart, [
            'user_id' => Auth::id(),
            'customer_id' => null,
            'customer_name' => $data['customer_name'] ?? '',
            'order_note' => $data['note'] ?? '',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon_id' => null,
            'coupon_discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceChargeAmount,
            'total' => $total,
            'payment_method' => $data['payment_method'],
            'paid_amount' => $paidAmount,
            'change_amount' => max(0, $paidAmount - $total),
            'claimed_self_order_id' => null,
        ]);

        return (new TransactionResource($transaction->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load('items'));
    }
}
