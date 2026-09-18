<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartPricer;
use App\Services\ThermalReceiptFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillPreviewController extends Controller
{
    /**
     * Price the current cart WITHOUT creating a Transaction - an unpaid
     * "Cetak Bill" preview for the cashier to show the customer before
     * actually confirming payment, mirroring Livewire\Pos\Terminal::
     * printBill(). Also returns `receipt_lines`: the same plain-text
     * ESC/POS-style lines the web version sends to a Bluetooth thermal
     * printer, for the mobile app to reuse if/when it gains its own
     * Bluetooth printing support.
     */
    public function store(Request $request, CartPricer $cartPricer): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'integer', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $store = Auth::user()->store;

        $pricing = $cartPricer->price(
            $store,
            $data['items'],
            $data['discount'] ?? 0,
            $data['coupon_code'] ?? null,
        );

        $bill = [
            'customer_name' => $data['customer_name'] ?? '',
            'note' => $data['note'] ?? '',
            'items' => array_values($pricing['cart']),
            'subtotal' => $pricing['subtotal'],
            'discount' => $pricing['discount'],
            'coupon_discount_amount' => $pricing['coupon_discount_amount'],
            'service_charge_amount' => $pricing['service_charge_amount'],
            'tax_amount' => $pricing['tax_amount'],
            'total' => $pricing['total'],
        ];

        return response()->json([
            'data' => [
                'subtotal' => $pricing['subtotal'],
                'discount' => $pricing['discount'],
                'coupon_discount_amount' => $pricing['coupon_discount_amount'],
                'service_charge_amount' => $pricing['service_charge_amount'],
                'tax_amount' => $pricing['tax_amount'],
                'total' => $pricing['total'],
                'receipt_lines' => ThermalReceiptFormatter::forBill($store, $bill),
            ],
        ]);
    }
}
