<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

/**
 * Turns a client-submitted list of {product_id, qty, note} lines into a
 * priced cart: re-derives every price from the current Product rows
 * (never trusts a client-supplied price), then applies discount/coupon/
 * service-charge/tax in the same order as the Kasir (Livewire) terminal -
 * shared by the mobile checkout endpoint and the bill-preview endpoint so
 * a previewed total always matches what checkout would actually charge.
 */
class CartPricer
{
    /**
     * @param  array<int, array{product_id: int, qty: int, note?: ?string}>  $items
     * @return array{cart: array<int, array{product_id: int, package_id: ?int, name: string, price: int, cost_price: int, qty: int, note: string, unlimited: bool, type: string}>, subtotal: int, discount: int, coupon: ?Coupon, coupon_discount_amount: int, discounted_subtotal: int, service_charge_amount: int, tax_amount: int, total: int}
     *
     * @throws ValidationException
     */
    public function price(Store $store, array $items, int $discount, ?string $couponCode): array
    {
        $cart = $this->buildCart($items);

        $subtotal = collect($cart)->sum(fn (array $item) => $item['price'] * $item['qty']);

        [$coupon, $couponDiscountAmount] = $this->resolveCoupon($couponCode, $subtotal);

        if ($discount + $couponDiscountAmount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' => ['Total diskon tidak boleh melebihi subtotal.'],
            ]);
        }

        $discountedSubtotal = max(0, $subtotal - $discount - $couponDiscountAmount);
        $serviceChargeAmount = $store->serviceChargeAmountFor($discountedSubtotal);
        $taxAmount = $store->taxAmountFor($discountedSubtotal + $serviceChargeAmount);
        $total = $discountedSubtotal + $serviceChargeAmount + $taxAmount;

        return [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon' => $coupon,
            'coupon_discount_amount' => $couponDiscountAmount,
            'discounted_subtotal' => $discountedSubtotal,
            'service_charge_amount' => $serviceChargeAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * @param  array<int, array{product_id: int, qty: int, note?: ?string}>  $items
     * @return array<int, array{product_id: int, package_id: ?int, name: string, price: int, cost_price: int, qty: int, note: string, unlimited: bool, type: string}>
     *
     * @throws ValidationException
     */
    private function buildCart(array $items): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', collect($items)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $cart = [];

        foreach ($items as $line) {
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

        return $cart;
    }

    /**
     * @return array{0: ?Coupon, 1: int}
     *
     * @throws ValidationException
     */
    private function resolveCoupon(?string $couponCode, int $subtotal): array
    {
        if (! $couponCode) {
            return [null, 0];
        }

        $coupon = Coupon::query()->where('code', strtoupper($couponCode))->first();

        if (! $coupon || ! $coupon->isCurrentlyActive()) {
            throw ValidationException::withMessages([
                'coupon_code' => ['Kupon tidak ditemukan atau sudah tidak berlaku.'],
            ]);
        }

        if ($coupon->is_age_based) {
            throw ValidationException::withMessages([
                'coupon_code' => ['Kupon ini butuh data umur pelanggan, belum didukung di aplikasi mobile.'],
            ]);
        }

        return [$coupon, $coupon->discountAmountFor($subtotal)];
    }
}
