<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $transaction->transaction_no }}</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 8px;
            color: #000;
        }
        h1 { font-size: 14px; margin: 0 0 2px; text-align: center; }
        .center { text-align: center; }
        .muted { color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 2px 0; vertical-align: top; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .right { text-align: right; }
        .totals td { padding: 1px 0; }
        .print-btn { margin-top: 12px; text-align: center; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    @if ($transaction->store->logoUrl())
        <div class="center"><img src="{{ $transaction->store->logoUrl() }}" alt="{{ $transaction->store->name }}" style="max-height: 48px; max-width: 100%;"></div>
    @endif
    <h1>{{ $transaction->store->name }}</h1>
    @if ($transaction->store->address)
        <div class="center muted">{{ $transaction->store->address }}</div>
    @endif
    @if ($transaction->store->phone)
        <div class="center muted">{{ $transaction->store->phone }}</div>
    @endif

    <div class="line"></div>

    <div>{{ $transaction->transaction_no }}</div>
    <div class="muted">{{ $transaction->created_at->format('d/m/Y H:i') }} - Kasir: {{ $transaction->user->name }}</div>
    @if ($transaction->customer_name)
        <div>Customer: {{ $transaction->customer_name }}</div>
    @endif
    @if ($transaction->note)
        <div class="muted">Catatan: {{ $transaction->note }}</div>
    @endif

    <div class="line"></div>

    <table>
        @foreach ($transaction->items as $item)
            <tr>
                <td colspan="2">{{ $item->product_name }}</td>
            </tr>
            <tr>
                <td>{{ $item->qty }} x {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($item->note)
                <tr>
                    <td colspan="2" class="muted">- {{ $item->note }}</td>
                </tr>
            @endif
        @endforeach
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if ($transaction->discount > 0)
            <tr>
                <td>Diskon</td>
                <td class="right">-{{ number_format($transaction->discount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if ($transaction->tax_amount > 0)
            <tr>
                <td>Pajak</td>
                <td class="right">{{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if ($transaction->service_charge_amount > 0)
            <tr>
                <td>Service Charge</td>
                <td class="right">{{ number_format($transaction->service_charge_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Total</strong></td>
            <td class="right"><strong>{{ number_format($transaction->total, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td>Bayar ({{ strtoupper($transaction->payment_method) }})</td>
            <td class="right">{{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="right">{{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="center">Terima kasih telah berbelanja</div>

    <div class="print-btn">
        <button onclick="window.print()">Cetak</button>
    </div>
</body>
</html>
