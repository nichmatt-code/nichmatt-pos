<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ProductLabelController extends Controller
{
    public function __invoke(Product $product): View
    {
        abort_unless($product->barcode, 404);

        $generator = new BarcodeGeneratorSVG;
        $barcodeSvg = $generator->getBarcode($product->barcode, BarcodeGeneratorSVG::TYPE_CODE_128, 2, 50);

        return view('products.label', [
            'product' => $product,
            'barcodeSvg' => $barcodeSvg,
        ]);
    }
}
