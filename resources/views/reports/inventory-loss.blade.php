<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Inventory Hilang') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Daftar bahan yang hilang berdasarkan hasil Stock Opname.') }}</p>
    </x-slot>

    <x-reports-tabs active="inventory-loss" />

    <livewire:reports.inventory-loss />
</x-app-layout>
