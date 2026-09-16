<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\Sortable;
use App\Models\StockOpname as StockOpnameModel;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class StockOpname extends Component
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
        $opnames = StockOpnameModel::query()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $this->startDate)
            ->whereDate('completed_at', '<=', $this->endDate)
            ->with(['items', 'creator', 'completer'])
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('code', 'like', "%{$this->search}%")
                ->orWhere('note', 'like', "%{$this->search}%")
            ))
            ->orderBy($this->sortField ?: 'completed_at', $this->sortField ? $this->sortDirection : 'desc')
            ->paginate(15);

        $opnames->getCollection()->transform(function (StockOpnameModel $opname) {
            $counted = $opname->items->whereNotNull('counted_qty');

            $opname->setAttribute('shrinkage_count', $counted->filter(fn ($item) => $item->difference() < 0)->count());
            $opname->setAttribute('surplus_count', $counted->filter(fn ($item) => $item->difference() > 0)->count());
            $opname->setAttribute('net_difference', $counted->sum(fn ($item) => $item->difference()));

            return $opname;
        });

        return view('livewire.reports.stock-opname', [
            'opnames' => $opnames,
        ]);
    }
}
