<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SelfOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SelfOrderController extends Controller
{
    /**
     * Claim a customer's self-order by its 6-character code, so its items
     * can be folded into the mobile cart for payment - mirrors
     * Livewire\Pos\Terminal::claimCode(), minus package items (the mobile
     * app doesn't support packages yet).
     *
     * The self-order is marked "claimed" immediately (same as the web
     * flow), so a code can only be claimed once - the actual Transaction
     * is created afterwards via a normal POST /transactions call that
     * includes this response's `self_order_id`.
     */
    public function claim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $code = strtoupper(trim($data['code']));

        $selfOrder = SelfOrder::query()->with('items')->where('code', $code)->first();

        if (! $selfOrder) {
            throw ValidationException::withMessages(['code' => ['Kode tidak ditemukan.']]);
        }

        if (! $selfOrder->isPending()) {
            throw ValidationException::withMessages([
                'code' => [$selfOrder->isExpired() ? 'Kode sudah kedaluwarsa.' : 'Kode sudah pernah dipakai.'],
            ]);
        }

        $items = [];
        $skipped = [];

        foreach ($selfOrder->items as $item) {
            if ($item->package_id) {
                // Paket belum didukung di aplikasi mobile.
                $skipped[] = $item->product_name;

                continue;
            }

            $product = $item->product_id
                ? Product::query()->where('is_active', true)->find($item->product_id)
                : null;

            if (! $product || ! $product->isAvailable()) {
                $skipped[] = $item->product_name;

                continue;
            }

            $maxQty = $product->is_unlimited_stock ? PHP_INT_MAX : $product->stock_qty;

            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => min($item->qty, $maxQty),
                'note' => (string) $item->note,
            ];
        }

        $selfOrder->update([
            'status' => 'claimed',
            'claimed_by' => Auth::id(),
            'claimed_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'self_order_id' => $selfOrder->id,
                'code' => $selfOrder->code,
                'customer_name' => $selfOrder->customer_name,
                'note' => $selfOrder->note,
                'items' => $items,
                'skipped' => $skipped,
            ],
        ]);
    }
}
