<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LossRecordResource;
use App\Services\LossRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LossRecordController extends Controller
{
    /**
     * Record damaged/lost/wasted stock from the mobile cashier app -
     * mirrors "Catat Kerugian" on the web Kasir terminal. Deducts stock
     * (and its ingredients) without creating a sale, same as the web
     * version. `cost_price` is always resolved server-side from the
     * Product row, never accepted from the client.
     */
    public function store(Request $request, LossRecordService $lossRecordService): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $lossRecord = $lossRecordService->record($data['items'], $data['reason'], Auth::id());

        return (new LossRecordResource($lossRecord->load('items')))
            ->response()
            ->setStatusCode(201);
    }
}
