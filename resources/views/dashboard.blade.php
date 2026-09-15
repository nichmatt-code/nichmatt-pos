<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Dashboard') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Ringkasan performa toko Anda hari ini.') }}</p>
    </x-slot>

    <livewire:dashboard />
</x-app-layout>
