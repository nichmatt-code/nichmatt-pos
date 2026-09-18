<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockOpnameResource;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    /**
     * List this store's stock opname sessions (both product and
     * inventory type, draft and completed), newest first.
     */
    public function index(): AnonymousResourceCollection
    {
        $stockOpnames = StockOpname::query()
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        return StockOpnameResource::collection($stockOpnames);
    }

    /**
     * Start a new opname session: snapshot every eligible product (or
     * inventory item) as a row with `system_qty` = its current stock and
     * `counted_qty` = null - mirrors Livewire\StockOpname\Index::create()
     * exactly, including which rows get skipped.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:product,inventory'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $stockOpname = DB::transaction(function () use ($data) {
            $stockOpname = StockOpname::create([
                'code' => 'SO-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'type' => $data['type'],
                'status' => StockOpname::STATUS_DRAFT,
                'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
                'created_by' => Auth::id(),
            ]);

            $isForProducts = $data['type'] === StockOpname::TYPE_PRODUCT;

            $sources = $isForProducts
                ? Product::query()->where('is_active', true)->where('is_unlimited_stock', false)->get()
                : InventoryItem::query()->where('is_active', true)->get();

            foreach ($sources as $source) {
                StockOpnameItem::create([
                    'stock_opname_id' => $stockOpname->id,
                    'product_id' => $isForProducts ? $source->id : null,
                    'inventory_item_id' => $isForProducts ? null : $source->id,
                    'item_name' => $source->name,
                    'unit' => $source->unit,
                    'system_qty' => $source->stock_qty,
                ]);
            }

            return $stockOpname;
        });

        return (new StockOpnameResource($stockOpname->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StockOpname $stockOpname): StockOpnameResource
    {
        return new StockOpnameResource($stockOpname->load('items'));
    }

    /**
     * Save counted quantities in one batch (the web version saves per
     * keystroke via Livewire; the mobile app instead lets the cashier
     * count several items offline-ish and save them together to avoid a
     * network round trip per digit typed). No-ops silently once the
     * session is completed, same as the web version.
     */
    public function updateCounts(Request $request, StockOpname $stockOpname): StockOpnameResource
    {
        $data = $request->validate([
            'counts' => ['required', 'array', 'min:1'],
            'counts.*.item_id' => ['required', 'integer'],
            'counts.*.counted_qty' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($stockOpname->isDraft()) {
            foreach ($data['counts'] as $count) {
                $stockOpname->items()
                    ->where('id', $count['item_id'])
                    ->update(['counted_qty' => $count['counted_qty']]);
            }
        }

        return new StockOpnameResource($stockOpname->load('items'));
    }

    /**
     * Apply every counted item's difference to real stock and log a
     * StockMovement/InventoryMovement per changed item - the exact loop
     * from Livewire\StockOpname\Show::finish(), including skipping
     * uncounted rows and zero-difference rows (no movement logged).
     */
    public function finish(StockOpname $stockOpname): StockOpnameResource
    {
        if ($stockOpname->isDraft()) {
            DB::transaction(function () use ($stockOpname) {
                $items = $stockOpname->items()->whereNotNull('counted_qty')->get();

                foreach ($items as $item) {
                    $difference = $item->difference();

                    if ($difference === 0) {
                        continue;
                    }

                    if ($stockOpname->isForProducts()) {
                        $product = Product::find($item->product_id);

                        if (! $product) {
                            continue;
                        }

                        $product->update(['stock_qty' => $item->counted_qty]);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'user_id' => Auth::id(),
                            'type' => 'adjustment',
                            'qty' => $difference,
                            'note' => 'Stock opname '.$stockOpname->code,
                        ]);
                    } else {
                        $inventoryItem = InventoryItem::find($item->inventory_item_id);

                        if (! $inventoryItem) {
                            continue;
                        }

                        $inventoryItem->update(['stock_qty' => $item->counted_qty]);

                        InventoryMovement::create([
                            'inventory_item_id' => $inventoryItem->id,
                            'user_id' => Auth::id(),
                            'type' => 'adjustment',
                            'qty' => $difference,
                            'note' => 'Stock opname '.$stockOpname->code,
                        ]);
                    }
                }

                $stockOpname->update([
                    'status' => StockOpname::STATUS_COMPLETED,
                    'completed_by' => Auth::id(),
                    'completed_at' => now(),
                ]);
            });
        }

        return new StockOpnameResource($stockOpname->load('items'));
    }

    /**
     * Only a draft session can be deleted - a completed one is silently
     * left alone (same forgiving behavior as the web version, which
     * never throws for this either).
     */
    public function destroy(StockOpname $stockOpname): JsonResponse
    {
        if ($stockOpname->isDraft()) {
            $stockOpname->delete();
        }

        return response()->json(['message' => 'OK']);
    }
}
