<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Monitor Inventory') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Pemakaian, selisih, dan nilai kerugian bahan baku dalam rentang waktu tertentu.') }}</p>
    </x-slot>

    <x-reports-tabs active="inventory-monitor" />

    <livewire:reports.inventory-monitor />
</x-app-layout>
