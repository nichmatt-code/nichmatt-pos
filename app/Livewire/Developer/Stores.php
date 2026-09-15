<?php

namespace App\Livewire\Developer;

use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Stores extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $stores = Store::query()
            ->withCount('users')
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        // Manual cross-store aggregates: Product/Transaction are scoped by
        // BelongsToStore to the *current* user's store, so those global
        // scopes must be bypassed here to see every tenant's numbers.
        $storeIds = $stores->pluck('id');

        $productCounts = Product::withoutGlobalScopes()
            ->whereIn('store_id', $storeIds)
            ->selectRaw('store_id, count(*) as aggregate')
            ->groupBy('store_id')
            ->pluck('aggregate', 'store_id');

        $revenue = Transaction::withoutGlobalScopes()
            ->whereIn('store_id', $storeIds)
            ->where('status', 'completed')
            ->selectRaw('store_id, count(*) as tx_count, coalesce(sum(total), 0) as tx_revenue')
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        return view('livewire.developer.stores', [
            'stores' => $stores,
            'productCounts' => $productCounts,
            'revenue' => $revenue,
        ]);
    }
}
