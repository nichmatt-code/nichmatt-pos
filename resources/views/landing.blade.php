<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NichmattPOS') }} &mdash; Aplikasi Kasir, Self-Order &amp; Inventory Dalam Satu Sistem</title>
        <meta name="description" content="NichmattPOS adalah aplikasi kasir (POS) untuk toko dan resto: kasir cepat, self-order pelanggan lewat QR code, inventory dengan resep otomatis, data pelanggan, dashboard analitik, hingga manajemen karyawan. Coba gratis 30 hari.">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url('/') }}">

        <!-- Open Graph / social preview -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'NichmattPOS') }}">
        <meta property="og:locale" content="id_ID">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ config('app.name', 'NichmattPOS') }} — Aplikasi Kasir, Self-Order & Inventory Dalam Satu Sistem">
        <meta property="og:description" content="Kasir cepat, self-order lewat QR code, inventory dengan resep otomatis, data pelanggan, dashboard analitik, hingga manajemen karyawan — dalam satu aplikasi. Coba gratis 30 hari.">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'NichmattPOS') }} — Aplikasi Kasir, Self-Order & Inventory">
        <meta name="twitter:description" content="Kasir cepat, self-order lewat QR code, inventory dengan resep otomatis, dashboard analitik, dan manajemen karyawan dalam satu aplikasi.">
        <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

        <!-- Structured data for search engines -->
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "SoftwareApplication",
            "name": {!! json_encode(config('app.name', 'NichmattPOS')) !!},
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "url": {!! json_encode(url('/')) !!},
            "description": "Aplikasi kasir (POS) untuk toko dan resto: kasir cepat, self-order lewat QR code, inventory dengan resep otomatis, data pelanggan, dashboard analitik, dan manajemen karyawan.",
            "offers": {
                "@@type": "Offer",
                "priceCurrency": "IDR",
                "availability": "https://schema.org/InStock"
            }
        }
        </script>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50">

        <!-- Navbar -->
        <nav class="sticky top-0 z-40 bg-white/85 backdrop-blur border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <a href="/" class="shrink-0 flex items-center gap-2">
                        <x-application-logo class="h-8 w-auto" />
                    </a>

                    <div class="hidden sm:flex sm:items-center sm:gap-8">
                        <a href="#fitur" class="text-sm font-medium text-slate-500 hover:text-slate-900 transition">{{ __('Fitur') }}</a>
                        <a href="#harga" class="text-sm font-medium text-slate-500 hover:text-slate-900 transition">{{ __('Harga') }}</a>
                    </div>

                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
                                {{ __('Buka Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="hidden sm:inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                                {{ __('Masuk') }}
                            </a>
                            <a href="{{ route('register') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
                                {{ __('Daftar Gratis') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-32 left-1/2 h-96 w-[48rem] -translate-x-1/2 rounded-full bg-brand-200/40 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,theme(colors.slate.300)_1px,transparent_0)] [background-size:28px_28px] opacity-[0.15]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700">
                        {{ __('Coba gratis :days hari, tanpa kartu kredit', ['days' => \App\Models\Store::TRIAL_DAYS]) }}
                    </span>

                    <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
                        {{ __('Kasir, Self-Order, dan Inventory') }}
                        {{ __('Dalam Satu Sistem') }}
                    </h1>

                    <p class="mt-5 text-lg text-slate-500 max-w-xl mx-auto lg:mx-0">
                        {{ __('NichmattPOS membantu toko dan resto Anda mengelola transaksi kasir, self-order pelanggan lewat QR code, stok bahan baku yang otomatis berkurang sesuai resep, data pelanggan, hingga izin akses karyawan — semua dari satu dashboard yang rapi dan mudah dipakai.') }}
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 rounded-lg font-medium text-white shadow-sm hover:bg-brand-700 transition w-full sm:w-auto">
                                {{ __('Buka Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('register') }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 rounded-lg font-medium text-white shadow-sm hover:bg-brand-700 transition w-full sm:w-auto">
                                {{ __('Mulai Trial Gratis') }}
                            </a>
                        @endauth
                        <a href="#fitur" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-lg font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition w-full sm:w-auto">
                            {{ __('Lihat Fitur') }}
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-brand-100/60"></div>
                    <img
                        src="{{ asset('images/landing-cashier.jpg') }}"
                        alt="{{ __('Kasir menggunakan layar sentuh untuk memproses transaksi') }}"
                        class="w-full aspect-[4/3] object-cover rounded-2xl shadow-soft border border-white/50"
                    >
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="border-y border-slate-200/70 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 sm:gap-6">
                    @foreach ([
                        ['step' => '1', 'title' => 'Daftar & Setup Toko', 'desc' => 'Buat akun, isi data toko, lalu tambahkan produk, kategori, dan bahan Inventory.'],
                        ['step' => '2', 'title' => 'Bagikan QR Self-Order', 'desc' => 'Cetak QR code self-order untuk ditaruh di meja, pelanggan bisa langsung pesan sendiri.'],
                        ['step' => '3', 'title' => 'Mulai Jualan', 'desc' => 'Kasir terima pembayaran, stok dan laporan terupdate otomatis di dashboard.'],
                    ] as $step)
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-white font-bold">
                                {{ $step['step'] }}
                            </span>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ __($step['title']) }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __($step['desc']) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="fitur" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold text-slate-900 tracking-tight">{{ __('Semua yang toko Anda butuhkan') }}</h2>
                <p class="mt-3 text-slate-500">{{ __('Dari kasir harian sampai laporan bulanan, satu aplikasi untuk semuanya.') }}</p>
            </div>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75v10.5A2.25 2.25 0 005.25 19.5z', 'title' => 'Kasir Cepat & Fleksibel', 'desc' => 'Scan barcode, cari produk berdasarkan kategori atau tag, cetak bill sebelum bayar, lalu proses pembayaran tunai, QRIS, maupun kartu dalam hitungan detik.'],
                        ['icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25', 'title' => 'Self-Order dengan QR Code', 'desc' => 'Pelanggan pesan sendiri lewat QR code di meja (bisa dicetak/download), dapat kode unik yang tinggal di-scan barcode scanner oleh kasir untuk konfirmasi bayar.'],
                        ['icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z', 'title' => 'Produk, Kategori & Tag', 'desc' => 'Kelola produk dengan foto, deskripsi, SKU/barcode otomatis, kategori, dan multi-tag supaya pelanggan dan kasir gampang cari menu.'],
                        ['icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25-3v3m-3-3h6a2.25 2.25 0 012.25 2.25v.75H6.75v-.75A2.25 2.25 0 019 4.5z', 'title' => 'Inventory dengan Resep Otomatis', 'desc' => 'Hubungkan bahan baku ke resep tiap produk — stok Inventory otomatis berkurang setiap produk terjual, lalu rekonsiliasi cepat lewat sesi stock opname.'],
                        ['icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'title' => 'Data Pelanggan (Member)', 'desc' => 'Simpan nama, nomor HP, dan alamat pelanggan — tinggal cari sekali ketik saat transaksi berikutnya, tanpa isi ulang data dari awal.'],
                        ['icon' => 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z', 'title' => 'Struk & Invoice Fleksibel', 'desc' => 'Pilih format struk thermal atau invoice PDF sesuai printer Anda, dengan pajak dan service charge yang dihitung otomatis di setiap transaksi.'],
                        ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'title' => 'Dashboard & Laporan Real-Time', 'desc' => 'Grafik tren penjualan 14 hari terakhir, pemakaian bahan Inventory hari ini, dan peringatan stok menipis — semua di satu layar.'],
                        ['icon' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z', 'title' => 'Karyawan & Izin Akses', 'desc' => 'Undang karyawan dan atur izin akses per fitur secara fleksibel — bukan sekadar peran tetap seperti kasir atau admin.'],
                        ['icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'title' => 'Ganti Akun Cepat', 'desc' => 'Kelola lebih dari satu toko dengan akun terpisah, dan pindah antar akun dalam satu klik tanpa perlu login ulang.'],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" /></svg>
                        </span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ __($feature['title']) }}</h3>
                        <p class="mt-1.5 text-sm text-slate-500">{{ __($feature['desc']) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Stock Opname showcase -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="relative order-2 lg:order-1">
                    <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-brand-100/60"></div>
                    <img
                        src="{{ asset('images/landing-stock-opname.jpg') }}"
                        alt="{{ __('Staf melakukan stock opname di gudang dengan clipboard') }}"
                        class="w-full aspect-[4/3] object-cover rounded-2xl shadow-soft border border-white/50"
                    >
                </div>

                <div class="order-1 lg:order-2 text-center lg:text-left">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700">
                        {{ __('Inventory & Stock Opname') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-bold text-slate-900 tracking-tight">
                        {{ __('Hitung stok fisik tanpa Excel dan tanpa pusing') }}
                    </h2>
                    <p class="mt-4 text-slate-500">
                        {{ __('Catat stok bahan baku gudang terpisah dari produk jual, lalu mulai sesi stock opname kapan saja. Sistem otomatis mencatat stok saat ini sebagai acuan, staf tinggal isi hasil hitung fisik, dan selisihnya langsung terlihat serta diterapkan begitu sesi diselesaikan.') }}
                    </p>
                    <ul class="mt-6 space-y-2.5 text-sm text-slate-600 text-left inline-block">
                        @foreach ([
                            'Snapshot stok sistem otomatis saat sesi dimulai',
                            'Hasil hitung tersimpan langsung, tidak hilang meski koneksi putus',
                            'Selisih stok dan riwayat penyesuaian tercatat rapi',
                        ] as $point)
                            <li class="flex items-start gap-2">
                                <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                {{ __($point) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section id="harga" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center max-w-xl mx-auto">
                <h2 class="text-3xl font-bold text-slate-900 tracking-tight">{{ __('Pilih paket sesuai kebutuhan') }}</h2>
                <p class="mt-3 text-slate-500">{{ __('Tidak ada paket rumit. Coba dulu gratis, berlangganan kalau sudah cocok.') }}</p>
            </div>

            @php $plans = \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(); @endphp

            <div class="mt-10 grid grid-cols-1 {{ $plans->count() > 1 ? 'sm:grid-cols-2' : 'max-w-md mx-auto' }} gap-6">
                @foreach ($plans as $plan)
                    <div class="bg-white border border-slate-200/70 shadow-soft rounded-2xl p-8">
                        <p class="font-semibold text-slate-900">{{ $plan->name }}</p>
                        <p class="text-sm text-slate-500">{{ $plan->description ?: __('Akses penuh semua fitur, per toko') }}</p>

                        <div class="mt-4">
                            @if ($plan->hasActivePromo())
                                <span class="text-sm text-slate-400 line-through">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                                @if ($plan->promo_label)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">{{ $plan->promo_label }}</span>
                                @endif
                                <p class="text-3xl font-extrabold text-brand-600">
                                    Rp {{ number_format($plan->promo_price, 0, ',', '.') }}
                                    <span class="text-base font-medium text-slate-400">/{{ __(':days hari', ['days' => $plan->duration_days]) }}</span>
                                </p>
                            @else
                                <p class="text-3xl font-extrabold text-brand-600">
                                    Rp {{ number_format($plan->price, 0, ',', '.') }}
                                    <span class="text-base font-medium text-slate-400">/{{ __(':days hari', ['days' => $plan->duration_days]) }}</span>
                                </p>
                            @endif
                        </div>

                        <ul class="mt-6 space-y-2.5 text-sm text-slate-600">
                            @forelse ($plan->featureList() as $feature)
                                <li class="flex items-start gap-2">
                                    <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    {{ $feature }}
                                </li>
                            @empty
                                @foreach ([
                                    'Kasir & Self-Order QR tanpa batas transaksi',
                                    'Produk, kategori, dan tag tanpa batas',
                                    'Inventory dengan resep otomatis & Stock Opname',
                                    'Data pelanggan (member) tanpa batas',
                                    'Manajemen karyawan & izin akses',
                                    'Dashboard & laporan penjualan real-time',
                                ] as $item)
                                    <li class="flex items-start gap-2">
                                        <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        {{ __($item) }}
                                    </li>
                                @endforeach
                            @endforelse
                        </ul>

                        <div class="mt-8">
                            @auth
                                <a href="{{ route('billing.subscribe') }}" wire:navigate class="flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 rounded-lg font-medium text-white shadow-sm hover:bg-brand-700 transition">
                                    {{ __('Kelola Langganan') }}
                                </a>
                            @else
                                <a href="{{ route('register') }}" wire:navigate class="flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 rounded-lg font-medium text-white shadow-sm hover:bg-brand-700 transition">
                                    {{ __('Mulai Trial Gratis') }}
                                </a>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">
                {{ __('Trial gratis :days hari untuk akun baru, tanpa kartu kredit. Batal kapan saja.', ['days' => \App\Models\Store::TRIAL_DAYS]) }}
            </p>
        </section>

        <!-- Final CTA -->
        @guest
            <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <div class="relative overflow-hidden rounded-2xl bg-brand-600 px-8 py-14 text-center shadow-soft">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.25)_1px,transparent_0)] [background-size:24px_24px] opacity-20"></div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">{{ __('Siap kelola toko Anda lebih mudah?') }}</h2>
                    <p class="mt-3 text-brand-100">{{ __('Daftar sekarang dan nikmati trial gratis :days hari, tanpa kartu kredit.', ['days' => \App\Models\Store::TRIAL_DAYS]) }}</p>
                    <a href="{{ route('register') }}" wire:navigate class="mt-6 inline-flex items-center justify-center gap-2 px-6 py-3 bg-white rounded-lg font-medium text-brand-700 shadow-sm hover:bg-brand-50 transition">
                        {{ __('Daftar Gratis Sekarang') }}
                    </a>
                </div>
            </section>
        @endguest

        <!-- Footer -->
        <footer class="border-t border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="/" class="flex items-center gap-2">
                    <x-application-logo class="h-7 w-auto" />
                </a>
                <p class="text-xs text-slate-400">&copy; {{ now()->year }} {{ config('app.name') }}. {{ __('Semua hak dilindungi.') }}</p>
            </div>
        </footer>
    </body>
</html>
