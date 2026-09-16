<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Developer — Harga & Promo') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola paket langganan dan kode promo untuk semua toko.') }}</p>
    </x-slot>

    <livewire:developer.pricing />
</x-app-layout>
