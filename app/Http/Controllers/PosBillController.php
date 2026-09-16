<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class PosBillController extends Controller
{
    /**
     * Render the current cashier's pending cart as a printable bill, stashed
     * in the session by Pos\Terminal::printBill(). Not tied to any
     * Transaction - it's shown to the customer before payment is taken.
     */
    public function __invoke(): View
    {
        $bill = session('pos_bill');

        abort_unless($bill && $bill['store_id'] === Auth::user()->store_id, 404);

        return view('pos.bill', [
            'bill' => $bill,
            'store' => Auth::user()->store,
        ]);
    }
}
