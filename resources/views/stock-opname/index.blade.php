<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Stock Opname') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Hitung stok fisik dan rekonsiliasi dengan catatan sistem.') }}</p>
    </x-slot>

    <livewire:stock-opname.index />
</x-app-layout>
