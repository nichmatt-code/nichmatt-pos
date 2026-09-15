<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NichmattPOS') }} &mdash; Kasir, Stok &amp; Karyawan Dalam Satu Aplikasi</title>
        <meta name="description" content="NichmattPOS adalah aplikasi kasir (POS) untuk toko dan resto: kasir cepat, self-order pelanggan, inventory &amp; stock opname, dan manajemen karyawan dengan izin akses. Coba gratis 30 hari.">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Theme (applied before paint to avoid a flash of the wrong theme) -->
        <script>
            (function () {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 dark:text-slate-100 antialiased bg-slate-50 dark:bg-slate-950">

        <!-- Navbar -->
        <nav class="sticky top-0 z-40 bg-white/85 dark:bg-slate-900/85 backdrop-blur border-b border-slate-200/70 dark:border-slate-800/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <a href="/" class="shrink-0 flex items-center gap-2">
                        <x-application-logo class="h-8 w-auto" />
                    </a>

                    <div class="hidden sm:flex sm:items-center sm:gap-8">
                        <a href="#fitur" class="text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 transition">{{ __('Fitur') }}</a>
                        <a href="#harga" class="text-sm font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 transition">{{ __('Harga') }}</a>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-theme-toggle />

                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
                                {{ __('Buka Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="hidden sm:inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100 transition">
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
                <div class="absolute -top-32 left-1/2 h-96 w-[48rem] -translate-x-1/2 rounded-full bg-brand-200/40 dark:bg-brand-900/30 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,theme(colors.slate.300)_1px,transparent_0)] dark:bg-[radial-gradient(circle_at_1px_1px,theme(colors.slate.700)_1px,transparent_0)] [background-size:28px_28px] opacity-[0.15]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                        {{ __('Coba gratis :days hari, tanpa kartu kredit', ['days' => \App\Models\Store::TRIAL_DAYS]) }}
                    </span>

                    <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ __('Kelola Kasir, Stok, dan Karyawan') }}
                        {{ __('Dalam Satu Aplikasi') }}
                    </h1>

                    <p class="mt-5 text-lg text-slate-500 dark:text-slate-400 max-w-xl mx-auto lg:mx-0">
                        {{ __('NichmattPOS membantu toko dan resto Anda mengelola transaksi kasir, self-order pelanggan, stok gudang, hingga izin akses karyawan — semua dari satu dashboard yang rapi dan mudah dipakai.') }}
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
                        <a href="#fitur" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-medium text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition w-full sm:w-auto">
                            {{ __('Lihat Fitur') }}
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-brand-100/60 dark:bg-brand-500/10"></div>
                    <img
                        src="{{ asset('images/landing-cashier.jpg') }}"
                        alt="{{ __('Kasir menggunakan layar sentuh untuk memproses transaksi') }}"
                        class="w-full aspect-[4/3] object-cover rounded-2xl shadow-soft border border-white/50 dark:border-slate-800"
                    >
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="fitur" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Semua yang toko Anda butuhkan') }}</h2>
                <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('Dari kasir harian sampai laporan bulanan, satu aplikasi untuk semuanya.') }}</p>
            </div>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018.75 4.5H5.25A2.25 2.25 0 003 6.75v10.5A2.25 2.25 0 005.25 19.5z', 'title' => 'Kasir Cepat', 'desc' => 'Scan barcode, cari produk berdasarkan kategori atau tag, dan proses pembayaran tunai, QRIS, maupun kartu dalam hitungan detik.'],
                        ['icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25', 'title' => 'Self-Order Pelanggan', 'desc' => 'Pelanggan pesan sendiri lewat QR code tanpa antre, dapat kode pembayaran, dan kasir tinggal konfirmasi bayar.'],
                        ['icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z', 'title' => 'Produk, Kategori & Tag', 'desc' => 'Kelola produk dengan foto, SKU/barcode otomatis, kategori, dan multi-tag supaya pelanggan gampang cari menu.'],
                        ['icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25-3v3m-3-3h6a2.25 2.25 0 012.25 2.25v.75H6.75v-.75A2.25 2.25 0 019 4.5z', 'title' => 'Inventory & Stock Opname', 'desc' => 'Catat stok bahan baku gudang secara terpisah dari produk jual, lalu rekonsiliasi cepat lewat sesi hitung stok fisik.'],
                        ['icon' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z', 'title' => 'Karyawan & Izin Akses', 'desc' => 'Undang karyawan dan atur izin akses per fitur secara fleksibel — bukan sekadar peran tetap seperti kasir atau admin.'],
                        ['icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'title' => 'Ganti Akun Cepat', 'desc' => 'Kelola lebih dari satu toko dengan akun terpisah, dan pindah antar akun dalam satu klik tanpa perlu login ulang.'],
                        ['icon' => 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z', 'title' => 'Laporan Penjualan', 'desc' => 'Pantau omzet, transaksi, dan performa produk secara real-time untuk pengambilan keputusan yang lebih cepat.'],
                        ['icon' => 'M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658l.26-1.477m2.605-14.772l.26-1.477m0 17.726l-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205L12 12m6.894 5.785l-1.149-.964M6.256 7.178l-1.15-.964m15.352 8.864l-1.41-.513M4.954 9.435l-1.41-.514M12.002 12l-3.75 6.495', 'title' => 'Dark Mode & Fullscreen', 'desc' => 'Tampilan mengikuti tema terang/gelap pilihan Anda, dan mode layar penuh untuk kasir yang lebih fokus.'],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" /></svg>
                        </span>
                        <h3 class="mt-4 font-semibold text-slate-900 dark:text-slate-100">{{ __($feature['title']) }}</h3>
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ __($feature['desc']) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Stock Opname showcase -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="relative order-2 lg:order-1">
                    <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-brand-100/60 dark:bg-brand-500/10"></div>
                    <img
                        src="{{ asset('images/landing-stock-opname.jpg') }}"
                        alt="{{ __('Staf melakukan stock opname di gudang dengan clipboard') }}"
                        class="w-full aspect-[4/3] object-cover rounded-2xl shadow-soft border border-white/50 dark:border-slate-800"
                    >
                </div>

                <div class="order-1 lg:order-2 text-center lg:text-left">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                        {{ __('Inventory & Stock Opname') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                        {{ __('Hitung stok fisik tanpa Excel dan tanpa pusing') }}
                    </h2>
                    <p class="mt-4 text-slate-500 dark:text-slate-400">
                        {{ __('Catat stok bahan baku gudang terpisah dari produk jual, lalu mulai sesi stock opname kapan saja. Sistem otomatis mencatat stok saat ini sebagai acuan, staf tinggal isi hasil hitung fisik, dan selisihnya langsung terlihat serta diterapkan begitu sesi diselesaikan.') }}
                    </p>
                    <ul class="mt-6 space-y-2.5 text-sm text-slate-600 dark:text-slate-300 text-left inline-block">
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
        <section id="harga" class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center max-w-xl mx-auto">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Satu harga, semua fitur') }}</h2>
                <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('Tidak ada paket rumit. Coba dulu gratis, berlangganan kalau sudah cocok.') }}</p>
            </div>

            <div class="mt-10 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-soft rounded-2xl p-8 sm:p-10">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Langganan Bulanan') }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Akses penuh semua fitur, per toko') }}</p>
                    </div>
                    <p class="text-4xl font-extrabold text-brand-600 dark:text-brand-400">
                        Rp {{ number_format(\App\Models\Store::SUBSCRIPTION_MONTHLY_PRICE, 0, ',', '.') }}
                        <span class="text-base font-medium text-slate-400 dark:text-slate-500">/{{ __('bulan') }}</span>
                    </p>
                </div>

                <ul class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300">
                    @foreach ([
                        'Kasir & Self-Order tanpa batas transaksi',
                        'Produk, kategori, dan tag tanpa batas',
                        'Inventory & Stock Opname',
                        'Manajemen karyawan & izin akses',
                        'Ganti akun cepat tanpa login ulang',
                        'Laporan penjualan real-time',
                    ] as $item)
                        <li class="flex items-start gap-2">
                            <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ __($item) }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    @auth
                        <a href="{{ route('billing.subscribe') }}" wire:navigate class="flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 rounded-lg font-medium text-white shadow-sm hover:bg-brand-700 transition">
                            {{ __('Kelola Langganan') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" wire:navigate class="flex items-center justify-center gap-2 px-6 py-3 bg-brand-600 rounded-lg font-medium text-white shadow-sm hover:bg-brand-700 transition">
                            {{ __('Mulai Trial Gratis :days Hari', ['days' => \App\Models\Store::TRIAL_DAYS]) }}
                        </a>
                    @endauth
                    <p class="mt-3 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('Tanpa kartu kredit. Batal kapan saja.') }}</p>
                </div>
            </div>
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
        <footer class="border-t border-slate-200/70 dark:border-slate-800/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="/" class="flex items-center gap-2">
                    <x-application-logo class="h-7 w-auto" />
                </a>
                <p class="text-xs text-slate-400 dark:text-slate-500">&copy; {{ now()->year }} {{ config('app.name') }}. {{ __('Semua hak dilindungi.') }}</p>
            </div>
        </footer>
    </body>
</html>
