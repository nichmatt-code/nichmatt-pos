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
            width: {{ $transaction->store->receipt_width }};
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
        .print-btn a { display: inline-block; background: #25d366; color: #fff; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; margin-top: 6px; }
        .bt-btn { margin-top: 6px; text-align: center; }
        .bt-btn button { display: none; }
        .bt-status { margin-top: 6px; text-align: center; font-size: 11px; white-space: pre-wrap; }
        @media print {
            .print-btn, .bt-btn, .bt-status { display: none; }
        }
    </style>
    @vite('resources/js/thermal-bluetooth.js')
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
        @if ($transaction->coupon_discount_amount > 0)
            <tr>
                <td>Diskon Kupon</td>
                <td class="right">-{{ number_format($transaction->coupon_discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if ($transaction->service_charge_amount > 0)
            <tr>
                <td>Service Charge</td>
                <td class="right">{{ number_format($transaction->service_charge_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if ($transaction->tax_amount > 0)
            <tr>
                <td>Pajak</td>
                <td class="right">{{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
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

    <div class="center">{{ $transaction->store->receiptFooterText() }}</div>

    <div class="print-btn">
        <button onclick="window.print()">Cetak</button>
    </div>
    @if ($waLink = \App\Services\WhatsAppReceiptFormatter::waLink($transaction))
        <div class="print-btn">
            <a href="{{ $waLink }}" target="_blank" rel="noopener">Kirim via WhatsApp</a>
        </div>
    @endif
    <div class="bt-btn">
        <button id="bt-print-btn" onclick="printViaBluetooth(this)">Cetak via Bluetooth</button>
    </div>
    <div class="bt-status" id="bt-status"></div>

    <script>
        window.__receiptLines = @json(\App\Services\ThermalReceiptFormatter::forTransaction($transaction));

        document.addEventListener('DOMContentLoaded', function () {
            if (window.NichmattThermalPrinter && window.NichmattThermalPrinter.isSupported()) {
                document.getElementById('bt-print-btn').style.display = 'inline-block';
            }
        });

        async function printViaBluetooth(button) {
            const status = document.getElementById('bt-status');
            button.disabled = true;
            status.textContent = 'Menghubungkan ke printer...';
            try {
                await window.NichmattThermalPrinter.printLines(window.__receiptLines);
                status.textContent = 'Struk terkirim ke printer.';
            } catch (e) {
                status.textContent = 'Gagal cetak: ' + e.message;
            } finally {
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
