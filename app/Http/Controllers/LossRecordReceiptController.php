<?php

namespace App\Http\Controllers;

use App\Models\LossRecord;
use Illuminate\Contracts\View\View;

class LossRecordReceiptController extends Controller
{
    public function __invoke(LossRecord $lossRecord): View
    {
        $lossRecord->load(['items', 'user', 'store']);

        return view('loss-records.receipt', ['lossRecord' => $lossRecord]);
    }
}
