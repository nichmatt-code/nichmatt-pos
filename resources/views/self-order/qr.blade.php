<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Self Order - {{ $store->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 40px 24px;
            text-align: center;
        }
        .logo { max-height: 56px; margin-bottom: 12px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .subtitle { color: #64748b; font-size: 14px; margin: 0 0 28px; }
        .qr-box {
            display: inline-block;
            padding: 24px;
            border: 2px solid #1e293b;
            border-radius: 16px;
        }
        .qr-box svg { display: block; width: 280px; height: 280px; }
        .instruction { margin-top: 24px; font-size: 16px; font-weight: 600; }
        .url { margin-top: 8px; color: #64748b; font-size: 12px; word-break: break-all; }
        .actions { margin-top: 32px; display: flex; gap: 12px; justify-content: center; }
        .actions a, .actions button {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
        }
        .actions .primary { background: #2a4ce0; border-color: #2a4ce0; color: #fff; }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    @if ($store->logoUrl())
        <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="logo">
    @endif
    <h1>{{ $store->name }}</h1>
    <p class="subtitle">{{ __('Scan untuk pesan sendiri') }}</p>

    <div class="qr-box">
        {!! $qrSvg !!}
    </div>

    <p class="instruction">{{ __('Scan QR di atas dengan kamera HP untuk memesan') }}</p>
    <p class="url">{{ $store->selfOrderUrl() }}</p>

    <div class="actions">
        <button type="button" onclick="window.print()">{{ __('Cetak / Simpan PDF') }}</button>
        <a class="primary" href="{{ route('self-order.qr.download') }}">{{ __('Download Gambar (PNG)') }}</a>
    </div>
</body>
</html>
