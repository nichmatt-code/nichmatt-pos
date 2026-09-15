<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $store->name }} - Order</title>

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

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 dark:text-slate-100 antialiased">
        <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
            <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200/70 dark:border-slate-800/70">
                <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
                    <x-application-logo class="h-8 w-auto" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $store->name }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('Self Order') }}</p>
                    </div>
                    <x-fullscreen-toggle />
                    <x-theme-toggle />
                </div>
            </header>

            <main class="max-w-3xl mx-auto px-4 py-6">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
