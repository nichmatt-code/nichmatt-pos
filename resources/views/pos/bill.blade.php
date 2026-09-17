<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bill Sementara</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: {{ $store->receipt_width }};
            margin: 0 auto;
            padding: 8px;
            color: #000;
        }
        h1 { font-size: 14px; margin: 0 0 2px; text-align: center; }
        .center { text-align: center; }
        .muted { color: #444; }
        .badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            border: 1px dashed #000;
            font-weight: bold;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 2px 0; vertical-align: top; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .right { text-align: right; }
        .totals td { padding: 1px 0; }
        .print-btn { margin-top: 12px; text-align: center; }
        .bt-btn { margin-top: 6px; text-align: center; }
        .bt-btn button { display: none; }
        .bt-status { margin-top: 6px; text-align: center; font-size: 11px; }
        @media print {
            .print-btn, .bt-btn, .bt-status { display: none; }
        }
    </style>
    @vite('resources/js/thermal-bluetooth.js')
</head>
<body>
    @if ($store->logoUrl())
        <div class="center"><img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" style="max-height: 48px; max-width: 100%;"></div>
    @endif
    <h1>{{ $store->name }}</h1>
    @if ($store->address)
        <div class="center muted">{{ $store->address }}</div>
    @endif

    <div class="center"><span class="badge">{{ __('BILL SEMENTARA - BELUM DIBAYAR') }}</span></div>

    <div class="line"></div>

    <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
    @if ($bill['customer_name'])
        <div>Customer: {{ $bill['customer_name'] }}</div>
    @endif
    @if ($bill['note'])
        <div class="muted">Catatan: {{ $bill['note'] }}</div>
    @endif

    <div class="line"></div>

    <table>
        @foreach ($bill['items'] as $item)
            <tr>
                <td colspan="2">{{ $item['name'] }}</td>
            </tr>
            <tr>
                <td>{{ $item['qty'] }} x {{ number_format($item['price'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</td>
            </tr>
            @if (!empty($item['note']))
                <tr>
                    <td colspan="2" class="muted">- {{ $item['note'] }}</td>
                </tr>
            @endif
        @endforeach
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ number_format($bill['subtotal'], 0, ',', '.') }}</td>
        </tr>
        @if ($bill['discount'] > 0)
            <tr>
                <td>Diskon</td>
                <td class="right">-{{ number_format($bill['discount'], 0, ',', '.') }}</td>
            </tr>
        @endif
        @if (($bill['coupon_discount_amount'] ?? 0) > 0)
            <tr>
                <td>Diskon Kupon</td>
                <td class="right">-{{ number_format($bill['coupon_discount_amount'], 0, ',', '.') }}</td>
            </tr>
        @endif
        @if (($bill['service_charge_amount'] ?? 0) > 0)
            <tr>
                <td>Service Charge</td>
                <td class="right">{{ number_format($bill['service_charge_amount'], 0, ',', '.') }}</td>
            </tr>
        @endif
        @if (($bill['tax_amount'] ?? 0) > 0)
            <tr>
                <td>Pajak</td>
                <td class="right">{{ number_format($bill['tax_amount'], 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Total Tagihan</strong></td>
            <td class="right"><strong>{{ number_format($bill['total'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="center">{{ __('Mohon lakukan pembayaran ke kasir') }}</div>

    <div class="print-btn">
        <button onclick="window.print()">Cetak</button>
    </div>
    <div class="bt-btn">
        <button id="bt-print-btn" onclick="printViaBluetooth(this)">Cetak via Bluetooth</button>
    </div>
    <div class="bt-status" id="bt-status"></div>

    <script>
        window.__receiptLines = @json(\App\Services\ThermalReceiptFormatter::forBill($store, $bill));

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
                status.textContent = 'Bill terkirim ke printer.';
            } catch (e) {
                status.textContent = 'Gagal cetak: ' + e.message;
            } finally {
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
