<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Produk') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola daftar produk dan stok toko Anda.') }}</p>
    </x-slot>

    <livewire:products.index />
</x-app-layout>
