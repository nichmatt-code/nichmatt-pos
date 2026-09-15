<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 dark:text-slate-100 antialiased">
        <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
            <livewire:layout.navigation />

            @if (auth()->user()?->store?->onTrial())
                <div class="bg-amber-50 dark:bg-amber-950/40 border-b border-amber-100 dark:border-amber-900/60">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 text-center text-sm text-amber-800 dark:text-amber-300">
                        {{ __('Masa trial tersisa :days hari.', ['days' => auth()->user()->store->trialDaysLeft()]) }}
                        <a href="{{ route('billing.subscribe') }}" wire:navigate class="ml-1 font-semibold underline decoration-amber-400 underline-offset-2 hover:text-amber-900 dark:hover:text-amber-200">{{ __('Berlangganan sekarang') }}</a>
                    </div>
                </div>
            @endif

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b border-slate-200/70 dark:border-slate-800/70 bg-white/60 dark:bg-slate-900/40">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
