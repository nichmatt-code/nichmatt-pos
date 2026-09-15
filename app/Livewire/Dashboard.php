<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): mixed
    {
        if (Auth::user()->isKasir()) {
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
        ]);
    }
}
