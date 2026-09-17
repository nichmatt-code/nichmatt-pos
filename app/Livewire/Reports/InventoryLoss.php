<?php

namespace App\Livewire\Reports;

use App\Models\InventoryMovement;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryLoss extends Component
{
    use WithPagination;

    public string $startDate;

    public string $endDate;

    public string $search = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $baseQuery = fn () => InventoryMovement::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_movements.inventory_item_id')
            ->where('inventory_movements.type', 'adjustment')
            ->where('inventory_movements.note', 'like', 'Stock opname%')
            ->where('inventory_movements.qty', '<', 0)
            ->whereDate('inventory_movements.created_at', '>=', $this->startDate)
            ->whereDate('inventory_movements.created_at', '<=', $this->endDate);

        $totalLossValue = (int) $baseQuery()
            ->selectRaw('SUM(-inventory_movements.qty * inventory_items.cost_price) as total')
            ->value('total');

        $rows = $baseQuery()
            ->when($this->search, fn ($query) => $query->where('inventory_items.name', 'like', "%{$this->search}%"))
            ->select(
                'inventory_movements.id',
                'inventory_movements.created_at',
                'inventory_movements.note',
                'inventory_movements.qty',
                'inventory_items.name as item_name',
                'inventory_items.unit as item_unit',
                'inventory_items.cost_price as item_cost_price',
            )
            ->orderByDesc('inventory_movements.created_at')
            ->paginate(15);

        return view('livewire.reports.inventory-loss', [
            'rows' => $rows,
            'totalLossValue' => $totalLossValue,
        ]);
    }
}
