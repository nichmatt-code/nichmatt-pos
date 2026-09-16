<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $transaction->transaction_no }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            margin: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 16px;
        }
        .store-name { font-size: 20px; font-weight: 700; margin: 0; }
        .muted { color: #64748b; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase; }
        .meta { margin-top: 20px; display: flex; justify-content: space-between; gap: 24px; }
        .meta div { flex: 1; }
        .meta dt { color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta dd { margin: 2px 0 10px; font-weight: 600; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.items th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 4px;
        }
        table.items td { padding: 10px 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .right { text-align: right; }
        .item-note { color: #64748b; font-size: 11px; }
        table.totals { width: 260px; margin-left: auto; margin-top: 16px; border-collapse: collapse; }
        table.totals td { padding: 4px 0; }
        table.totals tr.grand td { border-top: 2px solid #1e293b; padding-top: 8px; font-size: 16px; font-weight: 700; }
        .footer { margin-top: 40px; text-align: center; color: #64748b; font-size: 12px; }
        .print-btn { margin-top: 24px; text-align: center; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="store-name">{{ $transaction->store->name }}</p>
            @if ($transaction->store->address)
                <div class="muted">{{ $transaction->store->address }}</div>
            @endif
            @if ($transaction->store->phone)
                <div class="muted">{{ $transaction->store->phone }}</div>
            @endif
        </div>
        <div class="invoice-title">
            <h2>Invoice</h2>
            <div class="muted">{{ $transaction->transaction_no }}</div>
        </div>
    </div>

    <div class="meta">
        <div>
            <dt>Tanggal</dt>
            <dd>{{ $transaction->created_at->translatedFormat('d F Y, H:i') }}</dd>
            <dt>Kasir</dt>
            <dd>{{ $transaction->user->name }}</dd>
        </div>
        <div>
            <dt>Customer</dt>
            <dd>{{ $transaction->customer_name ?: '-' }}</dd>
            <dt>Metode Bayar</dt>
            <dd>{{ strtoupper($transaction->payment_method) }}</dd>
        </div>
    </div>

    @if ($transaction->note)
        <p class="muted">{{ __('Catatan') }}: {{ $transaction->note }}</p>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('Produk') }}</th>
                <th class="right">{{ __('Qty') }}</th>
                <th class="right">{{ __('Harga') }}</th>
                <th class="right">{{ __('Subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaction->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if ($item->note)
                            <div class="item-note">{{ $item->note }}</div>
                        @endif
                    </td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>{{ __('Subtotal') }}</td>
            <td class="right">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if ($transaction->discount > 0)
            <tr>
                <td>{{ __('Diskon') }}</td>
                <td class="right">-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('Total') }}</td>
            <td class="right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>{{ __('Bayar') }}</td>
            <td class="right">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>{{ __('Kembali') }}</td>
            <td class="right">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">{{ __('Terima kasih telah berbelanja') }}</div>

    <div class="print-btn">
        <button onclick="window.print()">{{ __('Cetak / Simpan sebagai PDF') }}</button>
    </div>
</body>
</html>
