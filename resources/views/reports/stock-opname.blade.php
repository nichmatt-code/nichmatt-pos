<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Laporan Stock Opname') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Riwayat selisih hasil hitung fisik vs sistem.') }}</p>
    </x-slot>

    <x-reports-tabs active="stock-opname" />

    <livewire:reports.stock-opname />
</x-app-layout>
