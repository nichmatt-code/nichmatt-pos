<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Stock Opname') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Hitung stok fisik dan rekonsiliasi dengan catatan sistem.') }}</p>
    </x-slot>

    <livewire:stock-opname.index />
</x-app-layout>
