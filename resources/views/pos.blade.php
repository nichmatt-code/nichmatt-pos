<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Kasir') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Buat transaksi baru untuk pelanggan.') }}</p>
    </x-slot>

    <livewire:pos.terminal />
</x-app-layout>
