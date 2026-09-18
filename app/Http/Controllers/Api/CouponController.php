<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    /**
     * Check a coupon code against the current cart subtotal, so the
     * cashier can see the discount before checkout. This is a PREVIEW
     * only - checkout (POST /transactions) re-validates and re-prices the
     * coupon itself from the code, it never trusts the amount shown here.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $coupon = Coupon::query()->where('code', strtoupper($data['code']))->first();

        if (! $coupon || ! $coupon->isCurrentlyActive()) {
            throw ValidationException::withMessages([
                'code' => ['Kupon tidak ditemukan atau sudah tidak berlaku.'],
            ]);
        }

        if ($coupon->is_age_based) {
            throw ValidationException::withMessages([
                'code' => ['Kupon ini butuh data umur pelanggan, belum didukung di aplikasi mobile.'],
            ]);
        }

        return response()->json([
            'data' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_amount' => $coupon->discountAmountFor($data['subtotal']),
                'has_gift' => $coupon->hasGift(),
                'gift_product_name' => $coupon->hasGift() ? $coupon->giftProduct?->name : null,
                'gift_qty' => $coupon->hasGift() ? $coupon->gift_qty : null,
            ],
        ]);
    }
}
