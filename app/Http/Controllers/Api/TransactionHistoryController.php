<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionSummaryResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionHistoryController extends Controller
{
    /**
     * List/search completed transactions for the cashier's own store, for
     * the mobile app's transaction history screen. Mirrors the filtering
     * in Livewire\Reports\Sales: a `from`/`to` date range on `created_at`
     * (defaults to the current month), and a single `search` term matched
     * against both the transaction number and the customer name.
     *
     * Store isolation comes for free from Transaction's BelongsToStore
     * global scope - no manual store_id filtering needed here.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        $transactions = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when(
                $data['search'] ?? null,
                fn ($query, $search) => $query->where(
                    fn ($q) => $q->where('transaction_no', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                )
            )
            ->orderByDesc('created_at')
            ->paginate(20);

        return TransactionSummaryResource::collection($transactions);
    }
}
