<?php

namespace App\Livewire;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): mixed
    {
        if (! Auth::user()->hasPermission(Permission::Dashboard)) {
            return redirect()->route('pos');
        }

        return null;
    }

    public function render(): View
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        // Fetched once from the widest window (this month) and sliced in
        // memory for the narrower ones, since today and this week are
        // always subsets of this month - avoids three overlapping queries.
        $monthTransactions = Transaction::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->get();
        $weekTransactions = $monthTransactions->where('created_at', '>=', $weekStart);
        $todayTransactions = $monthTransactions->where('created_at', '>=', $today);

        $bestSellers = TransactionItem::query()
            ->whereIn('transaction_id', $monthTransactions->pluck('id'))
            ->get()
            ->groupBy('product_name')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'qty' => $rows->sum('qty'),
                'revenue' => $rows->sum('subtotal'),
            ])
            ->sortByDesc('qty')
            ->take(5)
            ->values();

        return view('livewire.dashboard', [
            'summary' => [
                'today' => [
                    'omzet' => $todayTransactions->sum('total'),
                    'count' => $todayTransactions->count(),
                    'loss' => LossRecord::where('created_at', '>=', $today)->sum('total_cost_value'),
                    'inventory_lost' => $this->inventoryShrinkageValue($today),
                ],
                'week' => [
                    'omzet' => $weekTransactions->sum('total'),
                    'count' => $weekTransactions->count(),
                    'loss' => LossRecord::where('created_at', '>=', $weekStart)->sum('total_cost_value'),
                    'inventory_lost' => $this->inventoryShrinkageValue($weekStart),
                ],
                'month' => [
                    'omzet' => $monthTransactions->sum('total'),
                    'count' => $monthTransactions->count(),
                    'loss' => LossRecord::where('created_at', '>=', $monthStart)->sum('total_cost_value'),
                    'inventory_lost' => $this->inventoryShrinkageValue($monthStart),
                ],
            ],
            'bestSellers' => $bestSellers,
            'lowStockProducts' => Product::query()
                ->where('is_active', true)
                ->where('stock_qty', '<=', 5)
                ->orderBy('stock_qty')
                ->limit(10)
                ->get(),
            'lowStockInventory' => InventoryItem::query()
                ->where('is_active', true)
                ->whereColumn('stock_qty', '<=', 'min_stock')
                ->orderBy('stock_qty')
                ->limit(10)
                ->get(),
            'salesTrend' => $this->salesTrend(),
            'inventoryUsageToday' => $this->inventoryUsageToday($today),
        ]);
    }

    /**
     * The Rupiah value of inventory shrinkage found by completed Stock
     * Opname sessions since the given date - the "hilang" (missing) side of
     * inventory monitoring, distinct from an explicitly recorded loss.
     */
    private function inventoryShrinkageValue(Carbon $since): int
    {
        return (int) InventoryMovement::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_movements.inventory_item_id')
            ->where('inventory_movements.type', 'adjustment')
            ->where('inventory_movements.note', 'like', 'Stock opname%')
            ->where('inventory_movements.qty', '<', 0)
            ->where('inventory_movements.created_at', '>=', $since)
            ->selectRaw('SUM(-inventory_movements.qty * inventory_items.cost_price) as total')
            ->value('total');
    }

    /**
     * Daily completed-sales total for each of the last 14 days (including
     * today), oldest first, filling in zero for days with no sales.
     *
     * @return array<int, array{date: Carbon, total: int}>
     */
    private function salesTrend(): array
    {
        $since = now()->subDays(13)->startOfDay();

        $totalsByDate = Transaction::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 13))
            ->map(function (int $offset) use ($since, $totalsByDate) {
                $date = $since->copy()->addDays($offset);

                return [
                    'date' => $date,
                    'total' => (int) ($totalsByDate[$date->format('Y-m-d')] ?? 0),
                ];
            })
            ->all();
    }

    /**
     * How much of each Inventory item has been used (deducted via product
     * recipes at checkout) so far today, so low stock isn't a surprise.
     *
     * @return Collection<int, object{name: string, unit: string, used: int}>
     */
    private function inventoryUsageToday(Carbon $today): Collection
    {
        return InventoryMovement::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_movements.inventory_item_id')
            ->where('inventory_movements.type', 'out')
            ->where('inventory_movements.created_at', '>=', $today)
            ->selectRaw('inventory_items.name as name, inventory_items.unit as unit, SUM(-inventory_movements.qty) as used')
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.unit')
            ->orderByDesc('used')
            ->limit(10)
            ->get();
    }
}
