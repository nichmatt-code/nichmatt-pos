<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Kasir') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Buat transaksi baru untuk pelanggan.') }}</p>
    </x-slot>

    <livewire:pos.terminal />
</x-app-layout>
