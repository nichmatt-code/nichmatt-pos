<?php

namespace App\Livewire\StockOpname;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Show extends Component
{
    public StockOpname $stockOpname;

    /** @var array<int, string> keyed by stock_opname_item id */
    public array $counts = [];

    public function mount(StockOpname $stockOpname): void
    {
        $this->stockOpname = $stockOpname;

        foreach ($stockOpname->items as $item) {
            $this->counts[$item->id] = $item->counted_qty === null ? '' : (string) $item->counted_qty;
        }
    }

    /**
     * Persist one row's physical count as soon as it's entered (Livewire's
     * nested-property hook fires on every "counts.{id}" update), so progress
     * on a long count survives a closed tab or a dropped connection.
     */
    public function updatedCounts(string $value, string $key): void
    {
        if (! $this->stockOpname->isDraft()) {
            return;
        }

        $value = trim($value);

        StockOpnameItem::where('stock_opname_id', $this->stockOpname->id)
            ->where('id', (int) $key)
            ->update(['counted_qty' => $value === '' ? null : max(0, (int) $value)]);
    }

    /**
     * Apply every counted item's difference to the real stock, log an
     * adjustment movement for each one that changed, and lock the session.
     */
    public function finish(): void
    {
        if (! $this->stockOpname->isDraft()) {
            return;
        }

        DB::transaction(function () {
            $items = $this->stockOpname->items()->whereNotNull('counted_qty')->get();

            foreach ($items as $item) {
                $difference = $item->difference();

                if ($difference === 0) {
                    continue;
                }

                if ($this->stockOpname->isForProducts()) {
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
                        'note' => 'Stock opname '.$this->stockOpname->code,
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
                        'note' => 'Stock opname '.$this->stockOpname->code,
                    ]);
                }
            }

            $this->stockOpname->update([
                'status' => StockOpname::STATUS_COMPLETED,
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);
        });

        $this->stockOpname->refresh();
    }

    public function render(): View
    {
        return view('livewire.stock-opname.show', [
            'items' => $this->stockOpname->items()->orderBy('item_name')->get(),
        ]);
    }
}
