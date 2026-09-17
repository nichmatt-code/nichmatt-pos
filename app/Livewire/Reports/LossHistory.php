<?php

namespace App\Livewire\Reports;

use App\Models\LossRecord;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class LossHistory extends Component
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
        $baseQuery = fn () => LossRecord::query()
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);

        $records = $baseQuery()
            ->with(['items', 'user'])
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('loss_no', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.reports.loss-history', [
            'records' => $records,
            'totalValue' => $baseQuery()->sum('total_cost_value'),
        ]);
    }
}
