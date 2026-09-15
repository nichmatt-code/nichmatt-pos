<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Inventory') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Stok bahan baku dan barang gudang yang belum diolah menjadi produk.') }}</p>
    </x-slot>

    <livewire:inventory.index />
</x-app-layout>
