<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Developer — Harga & Promo') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola paket langganan dan kode promo untuk semua toko.') }}</p>
    </x-slot>

    <livewire:developer.pricing />
</x-app-layout>
