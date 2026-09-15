<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 tracking-tight">{{ __('Kasir') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Buat transaksi baru untuk pelanggan.') }}</p>
    </x-slot>

    <livewire:pos.terminal />
</x-app-layout>
