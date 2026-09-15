<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label {{ $product->name }}</title>
    <style>
        @page { margin: 8mm; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            display: flex;
            justify-content: center;
        }
        .label {
            width: 50mm;
            padding: 4mm;
            text-align: center;
            border: 1px dashed #ccc;
        }
        .label .name {
            font-size: 11px;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 2px;
            word-wrap: break-word;
        }
        .label .price {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .label svg {
            width: 100%;
            height: auto;
        }
        .label .code {
            font-size: 10px;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .print-btn { margin-top: 16px; text-align: center; }
        @media print {
            .label { border: none; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div>
        <div class="label">
            <div class="name">{{ $product->name }}</div>
            <div class="price">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
            {!! $barcodeSvg !!}
            <div class="code">{{ $product->barcode }}</div>
        </div>

        <div class="print-btn">
            <button onclick="window.print()">Cetak</button>
        </div>
    </div>
</body>
</html>
