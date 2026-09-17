<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Monitor Inventory') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Pemakaian, selisih, dan nilai kerugian bahan baku dalam rentang waktu tertentu.') }}</p>
    </x-slot>

    <div class="mb-5 flex gap-1.5">
        <a href="{{ route('reports.sales') }}" wire:navigate class="px-3.5 py-2 rounded-lg text-sm font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
            {{ __('Penjualan') }}
        </a>
        <a href="{{ route('reports.stock-opname') }}" wire:navigate class="px-3.5 py-2 rounded-lg text-sm font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
            {{ __('Stock Opname') }}
        </a>
        <a href="{{ route('reports.inventory-monitor') }}" wire:navigate class="px-3.5 py-2 rounded-lg text-sm font-semibold bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
            {{ __('Monitor Inventory') }}
        </a>
    </div>

    <livewire:reports.inventory-monitor />
</x-app-layout>
