<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SelfOrderQrDownloadController extends Controller
{
    public function __invoke(): Response
    {
        $store = Auth::user()->store;

        $result = (new Builder(
            writer: new PngWriter,
            data: $store->selfOrderUrl(),
            size: 800,
            margin: 20,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'attachment; filename="qr-self-order-'.Str::slug($store->name).'.png"',
        ]);
    }
}
