<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\LossRecord;
use App\Models\LossRecordItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records damaged/lost/wasted stock (broken glass, dropped food, expired
 * items, etc.) - deducts stock WITHOUT counting it as a sale. Mirrors
 * Livewire\Pos\Terminal::submitLoss() exactly, so mobile and web losses
 * post the same StockMovement/InventoryMovement trail.
 */
class LossRecordService
{
    /**
     * @param  array<int, array{product_id: int, qty: int}>  $items
     *
     * @throws ValidationException
     */
    public function record(array $items, string $reason, ?int $userId): LossRecord
    {
        return DB::transaction(function () use ($items, $reason, $userId) {
            $lossNo = 'LOSS-'.now()->format('Ymd-His').'-'.random_int(100, 999);

            $lossRecord = LossRecord::create([
                'user_id' => $userId,
                'loss_no' => $lossNo,
                'reason' => $reason,
                'total_cost_value' => 0,
            ]);

            $totalCostValue = 0;

            foreach ($items as $line) {
                $product = Product::with('ingredients')->find($line['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Produk dengan id {$line['product_id']} tidak ditemukan."],
                    ]);
                }

                $qty = $line['qty'];
                $subtotalCost = $product->cost_price * $qty;
                $totalCostValue += $subtotalCost;

                LossRecordItem::create([
                    'loss_record_id' => $lossRecord->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'qty' => $qty,
                    'cost_price' => $product->cost_price,
                    'subtotal_cost' => $subtotalCost,
                ]);

                if (! $product->is_unlimited_stock) {
                    $product->decrement('stock_qty', $qty);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'type' => 'out',
                        'qty' => -$qty,
                        'note' => 'Kerugian '.$lossNo,
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
                        'note' => 'Kerugian '.$lossNo,
                    ]);
                }
            }

            $lossRecord->update(['total_cost_value' => $totalCostValue]);

            return $lossRecord;
        });
    }
}
