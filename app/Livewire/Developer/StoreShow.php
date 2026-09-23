<?php

namespace App\Livewire\Developer;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSubscriptionPayment;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class StoreShow extends Component
{
    use WithPagination;

    public Store $store;

    public string $extendDays = '';

    public string $customExpiryDate = '';

    /** overview | products | categories | transactions */
    public string $tab = 'overview';

    public string $productSearch = '';

    public string $categorySearch = '';

    public string $transactionSearch = '';

    public function mount(Store $store): void
    {
        abort_unless(Auth::user()?->isDeveloper(), 403);

        $this->store = $store;
    }

    public function updatingProductSearch(): void
    {
        $this->resetPage('productsPage');
    }

    public function updatingCategorySearch(): void
    {
        $this->resetPage('categoriesPage');
    }

    public function updatingTransactionSearch(): void
    {
        $this->resetPage('transactionsPage');
    }

    /**
     * Grant this store paid access manually, with no payment involved -
     * stacking onto its current period the same way a real renewal would
     * if it's already active.
     */
    public function extend(): void
    {
        $validated = $this->validate([
            'extendDays' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $periodStart = $this->store->nextSubscriptionPeriodStart();

        $this->store->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $periodStart->copy()->addDays((int) $validated['extendDays']),
        ]);

        $this->reset('extendDays');
    }

    /**
     * Set the store's access to expire on an exact date, overriding
     * whatever period it currently has - for correcting mistakes rather
     * than everyday extensions.
     */
    public function setExpiry(): void
    {
        $validated = $this->validate([
            'customExpiryDate' => ['required', 'date', 'after:today'],
        ]);

        $this->store->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => Carbon::parse($validated['customExpiryDate'])->endOfDay(),
        ]);

        $this->reset('customExpiryDate');
    }

    public function render(): View
    {
        $transactions = Transaction::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('status', 'completed');

        return view('livewire.developer.store-show', [
            'users' => $this->store->users()->orderByDesc('role')->orderBy('name')->get(),
            'payments' => StoreSubscriptionPayment::withoutGlobalScopes()
                ->where('store_id', $this->store->id)
                ->latest()
                ->limit(10)
                ->get(),
            'stats' => [
                'products' => Product::withoutGlobalScopes()->where('store_id', $this->store->id)->count(),
                'categories' => Category::withoutGlobalScopes()->where('store_id', $this->store->id)->count(),
                'transactions' => (clone $transactions)->count(),
                'revenue' => (clone $transactions)->sum('total'),
            ],
            'recentTransactions' => Transaction::withoutGlobalScopes()
                ->where('store_id', $this->store->id)
                ->latest()
                ->limit(10)
                ->get(),
            'products' => $this->tab === 'products' ? $this->productsQuery()->paginate(15, ['*'], 'productsPage') : null,
            'categories' => $this->tab === 'categories' ? $this->categoriesQuery()->paginate(15, ['*'], 'categoriesPage') : null,
            'allTransactions' => $this->tab === 'transactions' ? $this->transactionsQuery()->paginate(15, ['*'], 'transactionsPage') : null,
        ]);
    }

    private function productsQuery()
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            // Eager-loaded relations run their own fresh query and are NOT
            // covered by withoutGlobalScopes() above - Category's own
            // BelongsToStore scope would otherwise filter it to the
            // DEVELOPER's store and hide every other tenant's category.
            ->with(['category' => fn ($query) => $query->withoutGlobalScopes()])
            ->when($this->productSearch !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%")
                    ->orWhere('barcode', 'like', "%{$this->productSearch}%")
            ))
            ->orderBy('name');
    }

    private function categoriesQuery()
    {
        return Category::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->withCount('products')
            ->when($this->categorySearch !== '', fn ($query) => $query->where('name', 'like', "%{$this->categorySearch}%"))
            ->orderBy('name');
    }

    private function transactionsQuery()
    {
        return Transaction::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->when($this->transactionSearch !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('transaction_no', 'like', "%{$this->transactionSearch}%")
                    ->orWhere('customer_name', 'like', "%{$this->transactionSearch}%")
            ))
            ->latest();
    }
}
