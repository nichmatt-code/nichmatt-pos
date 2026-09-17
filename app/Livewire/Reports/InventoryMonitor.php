<?php

namespace App\Livewire\Reports;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class InventoryMonitor extends Component
{
    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function render(): View
    {
        $usedByItem = $this->sumByItem('out', '%Penjualan%');
        $recordedLossByItem = $this->sumByItem('out', '%Kerugian%');
        $opnameDifferenceByItem = $this->sumByItem('adjustment', 'Stock opname%', negate: false);

        $rows = InventoryItem::query()
            ->orderBy('name')
            ->get()
            ->map(function (InventoryItem $item) use ($usedByItem, $recordedLossByItem, $opnameDifferenceByItem) {
                $used = (int) ($usedByItem[$item->id] ?? 0);
                $recordedLoss = (int) ($recordedLossByItem[$item->id] ?? 0);
                $opnameDifference = (int) ($opnameDifferenceByItem[$item->id] ?? 0);
                $shortage = max(0, -$opnameDifference);

                return [
                    'item' => $item,
                    'used' => $used,
                    'recorded_loss' => $recordedLoss,
                    'opname_difference' => $opnameDifference,
                    'loss_value' => ($recordedLoss + $shortage) * $item->cost_price,
                ];
            })
            ->sortByDesc('loss_value')
            ->values();

        return view('livewire.reports.inventory-monitor', [
            'rows' => $rows,
            'totalLossValue' => $rows->sum('loss_value'),
            'totalUsed' => $rows->sum('used'),
            'itemsWithShortage' => $rows->filter(fn ($row) => $row['recorded_loss'] > 0 || $row['opname_difference'] < 0)->count(),
        ]);
    }

    /**
     * Sum InventoryMovement qty within the selected date range, grouped by
     * inventory_item_id, for movements of the given type whose note matches
     * the given LIKE pattern. Deducting movements are stored as negative
     * qty, so the result is negated back to a positive amount unless told
     * not to (a stock opname "adjustment" is already signed the way we want
     * to display it: negative means shortage, positive means surplus).
     *
     * @return Collection<int, int>
     */
    private function sumByItem(string $type, string $notePattern, bool $negate = true): Collection
    {
        $sumExpression = $negate ? 'SUM(-qty) as total' : 'SUM(qty) as total';

        return InventoryMovement::query()
            ->where('type', $type)
            ->where('note', 'like', $notePattern)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->selectRaw("inventory_item_id, {$sumExpression}")
            ->groupBy('inventory_item_id')
            ->pluck('total', 'inventory_item_id');
    }
}
