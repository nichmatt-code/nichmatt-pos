<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class SelfOrderQrController extends Controller
{
    public function __invoke(): View
    {
        $store = Auth::user()->store;

        $qrSvg = (new Builder(
            writer: new SvgWriter,
            data: $store->selfOrderUrl(),
            size: 320,
            margin: 10,
        ))->build()->getString();

        return view('self-order.qr', ['store' => $store, 'qrSvg' => $qrSvg]);
    }
}
