<?php

namespace App\Livewire\Developer;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StoreShow extends Component
{
    public Store $store;

    public function mount(Store $store): void
    {
        $this->store = $store;
    }

    public function render(): View
    {
        $transactions = Transaction::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('status', 'completed');

        return view('livewire.developer.store-show', [
            'users' => $this->store->users()->orderByDesc('role')->orderBy('name')->get(),
            'payments' => $this->store->subscriptionPayments()->latest()->limit(10)->get(),
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
