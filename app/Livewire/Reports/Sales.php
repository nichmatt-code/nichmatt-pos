<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\Sortable;
use App\Models\InventoryMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Sales extends Component
{
    use Sortable, WithPagination;

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
        $baseQuery = fn () => Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);

        // Aggregates (best sellers, totals, inventory usage) always reflect the
        // full date range, independent of the table's own search/pagination.
        $transactions = $baseQuery()->get();

        $paginatedTransactions = $baseQuery()
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('transaction_no', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%")
            ))
            ->orderBy($this->sortField ?: 'created_at', $this->sortField ? $this->sortDirection : 'desc')
            ->paginate(15);

        $transactionIds = $transactions->pluck('id');

        $items = TransactionItem::query()
            ->whereIn('transaction_id', $transactionIds)
            ->get();

        $bestSellers = $items
            ->groupBy('product_name')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'qty' => $rows->sum('qty'),
                'revenue' => $rows->sum('subtotal'),
            ])
            ->sortByDesc('qty')
            ->take(10)
            ->values();

        $inventoryUsage = InventoryMovement::query()
            ->with('inventoryItem')
            ->where('type', 'out')
            ->where('note', 'like', 'Penjualan %')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->get()
            ->filter(fn (InventoryMovement $movement) => $movement->inventoryItem !== null)
            ->groupBy('inventory_item_id')
            ->map(fn ($rows) => [
                'name' => $rows->first()->inventoryItem->name,
                'unit' => $rows->first()->inventoryItem->unit,
                'qty' => $rows->sum(fn ($row) => abs($row->qty)),
            ])
            ->sortByDesc('qty')
            ->values();

        return view('livewire.reports.sales', [
            'transactions' => $paginatedTransactions,
            'totalOmzet' => $transactions->sum('total'),
            'totalTransactions' => $transactions->count(),
            'totalProfit' => $items->sum(fn ($item) => ($item->price - $item->cost_price) * $item->qty),
            'bestSellers' => $bestSellers,
            'inventoryUsage' => $inventoryUsage,
        ]);
    }
}
