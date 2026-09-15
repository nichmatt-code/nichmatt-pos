<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden bg-slate-50 px-4 py-10">
            <!-- Decorative backdrop -->
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-32 left-1/2 h-96 w-[36rem] -translate-x-1/2 rounded-full bg-brand-200/40 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 h-72 w-72 translate-x-1/4 translate-y-1/4 rounded-full bg-brand-100/60 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,theme(colors.slate.300)_1px,transparent_0)] [background-size:28px_28px] opacity-[0.15]"></div>
            </div>

            <div class="w-full sm:max-w-md">
                <div class="flex justify-center">
                    <a href="/" wire:navigate>
                        <x-application-logo class="h-16 w-auto" />
                    </a>
                </div>

                <div class="mt-8 bg-white/90 backdrop-blur border border-slate-200/80 shadow-soft rounded-2xl px-6 py-8 sm:px-10">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    &copy; {{ now()->year }} {{ config('app.name') }}. Semua hak dilindungi.
                </p>
            </div>
        </div>
    </body>
</html>
