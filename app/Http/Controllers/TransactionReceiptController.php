<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;

class TransactionReceiptController extends Controller
{
    public function __invoke(Transaction $transaction): View
    {
        $transaction->load(['items', 'user', 'store']);

        return view('transactions.receipt', ['transaction' => $transaction]);
    }
}
