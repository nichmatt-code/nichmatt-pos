<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\SelfOrder;
use App\Models\Transaction;
use App\Services\CartPricer;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    /**
     * Checkout a cart of plain products (no packages yet) into a real
     * Transaction, for the mobile (React Native) cashier app. Prices are
     * always re-derived from the current Product rows server-side - the
     * client only sends product_id + qty, never a price. A coupon code is
     * likewise only ever taken as a code and re-validated/re-priced here,
     * never as a client-supplied discount amount.
     */
    public function store(Request $request, CheckoutService $checkoutService, CartPricer $cartPricer): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'self_order_id' => ['nullable', 'integer'],
            'payment_method' => ['required', 'in:cash,qris,kartu'],
            'paid_amount' => ['required_if:payment_method,cash', 'nullable', 'integer', 'min:0'],
        ]);

        $store = Auth::user()->store;

        $pricing = $cartPricer->price(
            $store,
            $data['items'],
            $data['discount'] ?? 0,
            $data['coupon_code'] ?? null,
        );

        $claimedSelfOrderId = $this->resolveClaimedSelfOrderId($data['self_order_id'] ?? null);

        $paidAmount = $data['payment_method'] === 'cash' ? (int) $data['paid_amount'] : $pricing['total'];

        if ($data['payment_method'] === 'cash' && $paidAmount < $pricing['total']) {
            throw ValidationException::withMessages(['paid_amount' => ['Jumlah bayar kurang dari total.']]);
        }

        $transaction = $checkoutService->materialize($pricing['cart'], [
            'user_id' => Auth::id(),
            'customer_id' => null,
            'customer_name' => $data['customer_name'] ?? '',
            'order_note' => $data['note'] ?? '',
            'subtotal' => $pricing['subtotal'],
            'discount' => $pricing['discount'],
            'coupon_id' => $pricing['coupon']?->id,
            'coupon_discount_amount' => $pricing['coupon_discount_amount'],
            'tax_amount' => $pricing['tax_amount'],
            'service_charge_amount' => $pricing['service_charge_amount'],
            'total' => $pricing['total'],
            'payment_method' => $data['payment_method'],
            'paid_amount' => $paidAmount,
            'change_amount' => max(0, $paidAmount - $pricing['total']),
            'claimed_self_order_id' => $claimedSelfOrderId,
        ]);

        return (new TransactionResource($transaction->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load('items'));
    }

    /**
     * @throws ValidationException
     */
    private function resolveClaimedSelfOrderId(?int $selfOrderId): ?int
    {
        if (! $selfOrderId) {
            return null;
        }

        $selfOrder = SelfOrder::query()->find($selfOrderId);

        if (! $selfOrder || $selfOrder->status !== 'claimed' || $selfOrder->claimed_by !== Auth::id()) {
            throw ValidationException::withMessages([
                'self_order_id' => ['Self order ini tidak bisa dipakai untuk pembayaran.'],
            ]);
        }

        return $selfOrder->id;
    }
}
