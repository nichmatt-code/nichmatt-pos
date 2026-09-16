<?php

namespace App\Livewire;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Transaction;
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

        $todayTransactions = Transaction::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->get();

        return view('livewire.dashboard', [
            'todayOmzet' => $todayTransactions->sum('total'),
            'todayCount' => $todayTransactions->count(),
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
