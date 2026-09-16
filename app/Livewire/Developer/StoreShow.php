<?php

namespace App\Livewire\Developer;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSubscriptionPayment;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class StoreShow extends Component
{
    public Store $store;

    public string $extendDays = '';

    public string $customExpiryDate = '';

    public function mount(Store $store): void
    {
        $this->store = $store;
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
        ]);
    }
}
