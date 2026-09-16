<?php

namespace App\Livewire\StockOpname;

use App\Livewire\Concerns\Sortable;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use Sortable, WithPagination;

    public bool $showCreateModal = false;

    public string $type = StockOpname::TYPE_PRODUCT;

    public string $note = '';

    public string $search = '';

    public string $filterType = 'all';

    public string $filterStatus = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->type = StockOpname::TYPE_PRODUCT;
        $this->note = '';
        $this->showCreateModal = true;
    }

    /**
     * Start a new counting session by snapshotting every currently active
     * item's system stock, so counters can fill in the physical count
     * without the numbers moving underneath them mid-session. Items with
     * unlimited stock are skipped - there's nothing finite to reconcile.
     */
    public function create(): void
    {
        $validated = $this->validate([
            'type' => ['required', 'in:'.StockOpname::TYPE_PRODUCT.','.StockOpname::TYPE_INVENTORY],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $opname = DB::transaction(function () use ($validated) {
            $opname = StockOpname::create([
                'code' => 'SO-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'type' => $validated['type'],
                'status' => StockOpname::STATUS_DRAFT,
                'note' => $validated['note'] !== '' ? $validated['note'] : null,
                'created_by' => Auth::id(),
            ]);

            if ($validated['type'] === StockOpname::TYPE_PRODUCT) {
                $sources = Product::query()->where('is_active', true)->where('is_unlimited_stock', false)->get();
            } else {
                $sources = InventoryItem::query()->where('is_active', true)->get();
            }

            foreach ($sources as $source) {
                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'product_id' => $validated['type'] === StockOpname::TYPE_PRODUCT ? $source->id : null,
                    'inventory_item_id' => $validated['type'] === StockOpname::TYPE_INVENTORY ? $source->id : null,
                    'item_name' => $source->name,
                    'unit' => $source->unit,
                    'system_qty' => $source->stock_qty,
                ]);
            }

            return $opname;
        });

        $this->redirect(route('stock-opname.show', $opname), navigate: true);
    }

    public function delete(int $opnameId): void
    {
        $opname = StockOpname::findOrFail($opnameId);

        if ($opname->isDraft()) {
            $opname->delete();
        }
    }

    public function render(): View
    {
        return view('livewire.stock-opname.index', [
            'opnames' => StockOpname::query()
                ->withCount('items')
                ->with('creator')
                ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('code', 'like', "%{$this->search}%")
                    ->orWhere('note', 'like', "%{$this->search}%")
                ))
                ->when($this->filterType !== 'all', fn ($query) => $query->where('type', $this->filterType))
                ->when($this->filterStatus !== 'all', fn ($query) => $query->where('status', $this->filterStatus))
                ->orderBy($this->sortField ?: 'created_at', $this->sortField ? $this->sortDirection : 'desc')
                ->paginate(15),
        ]);
    }
}
