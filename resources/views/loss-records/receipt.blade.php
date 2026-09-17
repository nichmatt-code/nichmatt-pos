<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota Kerugian {{ $lossRecord->loss_no }}</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: {{ $lossRecord->store->receipt_width }};
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
        .badge { text-align: center; font-weight: bold; letter-spacing: 1px; margin: 4px 0; }
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
    @if ($lossRecord->store->logoUrl())
        <div class="center"><img src="{{ $lossRecord->store->logoUrl() }}" alt="{{ $lossRecord->store->name }}" style="max-height: 48px; max-width: 100%;"></div>
    @endif
    <h1>{{ $lossRecord->store->name }}</h1>

    <div class="badge">*** NOTA KERUGIAN ***</div>

    <div class="line"></div>

    <div>{{ $lossRecord->loss_no }}</div>
    <div class="muted">{{ $lossRecord->created_at->format('d/m/Y H:i') }} - Dicatat oleh: {{ $lossRecord->user->name ?? '-' }}</div>
    <div>Alasan: {{ $lossRecord->reason }}</div>

    <div class="line"></div>

    <table>
        @foreach ($lossRecord->items as $item)
            <tr>
                <td colspan="2">{{ $item->product_name }}</td>
            </tr>
            <tr>
                <td>{{ $item->qty }} x {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($item->subtotal_cost, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td><strong>Total Nilai Kerugian</strong></td>
            <td class="right"><strong>{{ number_format($lossRecord->total_cost_value, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="center muted">Dihitung dari harga modal, bukan penjualan</div>

    <div class="print-btn">
        <button onclick="window.print()">Cetak</button>
    </div>
    <div class="bt-btn">
        <button id="bt-print-btn" onclick="printViaBluetooth(this)">Cetak via Bluetooth</button>
    </div>
    <div class="bt-status" id="bt-status"></div>

    <script>
        window.__receiptLines = @json(\App\Services\ThermalReceiptFormatter::forLossRecord($lossRecord));

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
                status.textContent = 'Nota terkirim ke printer.';
            } catch (e) {
                status.textContent = 'Gagal cetak: ' + e.message;
            } finally {
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
