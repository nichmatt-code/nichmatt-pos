<?php

namespace App\Livewire\Preparation;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public ?int $viewingId = null;

    public function openDetail(int $transactionId): void
    {
        $this->viewingId = $transactionId;
    }

    public function closeDetail(): void
    {
        $this->viewingId = null;
    }

    /**
     * Mark an order as done preparing. Only completed (paid) transactions
     * can be toggled; the store scope on Transaction already keeps this to
     * the current store.
     */
    public function markPrepared(int $transactionId): void
    {
        Transaction::where('id', $transactionId)
            ->where('status', 'completed')
            ->update(['prepared_at' => now()]);

        if ($this->viewingId === $transactionId) {
            $this->viewingId = null;
        }
    }

    /**
     * Undo an accidental "done" tap.
     */
    public function markUnprepared(int $transactionId): void
    {
        Transaction::where('id', $transactionId)
            ->where('status', 'completed')
            ->update(['prepared_at' => null]);

        if ($this->viewingId === $transactionId) {
            $this->viewingId = null;
        }
    }

    public function render(): View
    {
        return view('livewire.preparation.index', [
            'pending' => Transaction::query()
                ->with('items')
                ->where('status', 'completed')
                ->whereNull('prepared_at')
                ->orderBy('created_at')
                ->get(),
            'recentlyPrepared' => Transaction::query()
                ->with('items')
                ->where('status', 'completed')
                ->whereNotNull('prepared_at')
                ->whereDate('prepared_at', today())
                ->orderByDesc('prepared_at')
                ->limit(20)
                ->get(),
            'viewing' => $this->viewingId
                ? Transaction::with(['items', 'user'])->find($this->viewingId)
                : null,
        ]);
    }
}
