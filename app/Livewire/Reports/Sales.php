<?php

namespace App\Livewire\Reports;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Sales extends Component
{
    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function render(): View
    {
        $transactions = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->orderByDesc('created_at')
            ->get();

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

        return view('livewire.reports.sales', [
            'transactions' => $transactions,
            'totalOmzet' => $transactions->sum('total'),
            'totalTransactions' => $transactions->count(),
            'totalProfit' => $items->sum(fn ($item) => ($item->price - $item->cost_price) * $item->qty),
            'bestSellers' => $bestSellers,
        ]);
    }
}
